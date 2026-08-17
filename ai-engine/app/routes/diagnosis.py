"""Diagnosis workflow endpoints.

Symptom search (``/symptoms``) returns candidate illnesses extracted by the
LLM from vector-search hits. ``/diagnosis/start`` opens a session and the
``/follow-up/*`` and ``/symptom/select`` endpoints drive the SOCRATES +
Bayesian question loop. Responses use a flat ``{"status", "data" | "detail"}``
shape for easy consumption from the Laravel side.
"""
from fastapi import APIRouter, Form, Query, Header
from app.state import get_store, get_embedder, get_session_manager, get_llm
from app.services.logger import log
from app.services.socrates import build_extract_prompt, parse_llm_response
from app.services.i18n import detect_lang, translate_batch, to_english

router = APIRouter()


# ── Idempotency store (in-memory, per-process) ──
# Keyed by "{session_id}:{idempotency_key}".  Values are the full response
# dicts.  Entries expire after 10 minutes via TTL wrapper.
import time as _time

_idempotency_store: dict[str, tuple[float, dict]] = {}
_IDEMPOTENCY_TTL = 600  # 10 minutes


def _check_idempotency(session_id: str, key: str | None) -> dict | None:
    """Return cached response if this idempotency key was seen recently."""
    if not key:
        return None
    cache_key = f"{session_id}:{key}"
    entry = _idempotency_store.get(cache_key)
    if entry is None:
        return None
    ts, data = entry
    if _time.time() - ts > _IDEMPOTENCY_TTL:
        _idempotency_store.pop(cache_key, None)
        return None
    log("IDEMPOTENCY", f"Cache hit for key={key[:16]}… session={session_id[:8]}")
    return data


def _save_idempotency(session_id: str, key: str | None, result: dict):
    """Store a response for idempotency replay."""
    if not key:
        return
    cache_key = f"{session_id}:{key}"
    _idempotency_store[cache_key] = (_time.time(), result)
    # Evict expired entries opportunistically (max 50 per call)
    now = _time.time()
    evicted = 0
    for k in list(_idempotency_store):
        if evicted >= 50:
            break
        if now - _idempotency_store[k][0] > _IDEMPOTENCY_TTL:
            _idempotency_store.pop(k, None)
            evicted += 1


@router.get("/symptoms")
async def search_symptoms(
    q: str = Query(default="", description="Search query for symptoms or illnesses"),
    model_name: str = Query(default="@cf/meta/llama-3.2-3b-instruct", description="LLM model to use for extraction"),
):
    """Search symptoms/illnesses: vector-search chunks, then LLM-extract candidates."""
    store = get_store()
    embedder = get_embedder()
    llm = get_llm()
    lang = detect_lang(q)

    if not q:
        return {"status": "success", "data": {"query": q, "results": []}}

    query_en = to_english(q.strip()).lower()
    query_vector = embedder.encode_query(query_en)
    results = store.search(query_vector, limit=20) or []
    log("SYMPTOMS", f"Vector search: {len(results)} results for '{query_en[:50]}' (original: '{q[:50]}')")

    if not results:
        return {"status": "success", "data": {"query": q, "results": []}}

    # Build context from PDF chunks
    context_blocks = []
    for i, r in enumerate(results):
        parts = []
        ne = (r.get("name_en") or "").strip()
        if ne:
            parts.append(f"name: {ne}")
        se = (r.get("symptoms_en") or "").strip()
        if se:
            parts.append(f"symptoms: {se}")
        doc = (r.get("document") or "").strip()[:200]
        if doc and not any('\u0600' <= c <= '\u06ff' for c in doc):
            parts.append(f"text: {doc}")
        if not parts:
            parts.append(f"text: Chunk-{i+1}")
        context_blocks.append(f"[PASSAGE {i+1}]\n" + "; ".join(parts))
    context = "\n\n".join(context_blocks)

    system_prompt = build_extract_prompt(query_en, context, lang)
    items = []
    try:
        raw = llm.ask([
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": f"Extract relevant illnesses/symptoms for the search: {query_en}"},
        ], temperature=0, max_tokens=4096, model=model_name)
        log("SYMPTOMS", f"LLM raw response ({len(raw)} chars): {raw[:200]}")
        parsed = parse_llm_response(raw)
        results_val = parsed.get("results") if isinstance(parsed, dict) else None
        items = results_val if isinstance(results_val, list) else []
        log("SYMPTOMS", f"LLM parsed: {len(items)} items")
    except Exception as e:
        log("SYMPTOMS", f"LLM extraction failed: {str(e)[:80]}")

    if not items:
        return {"status": "success", "data": {"query": q, "results": []}}

    name_en_list = []
    summary_en_list = []
    for it in items:
        if not isinstance(it, dict):
            continue
        name_en = (it.get("name_en") or "").strip()
        if not name_en:
            continue
        summary = (it.get("summary") or "").strip()[:200]
        name_en_list.append(name_en)
        summary_en_list.append(summary)

    if lang != "en":
        names_local = translate_batch(name_en_list, lang)
        summaries_local = translate_batch(summary_en_list, lang)
    else:
        names_local = name_en_list
        summaries_local = summary_en_list

    cleaned = []
    for idx, ne in enumerate(name_en_list):
        cleaned.append({
            "id": idx,
            "name_en": ne,
            "name_local": names_local[idx],
            "type": "illness",
            "summary": summaries_local[idx],
            "source_id": "",
            "similarity": 1.0,
        })

    return {"status": "success", "data": {"query": q, "results": cleaned}}


@router.post("/diagnosis/start")
async def start_diagnosis(
    user_id: str = Form(...),
    patient_name: str = Form(default=None, description="Optional display name for the patient"),
    gender: str = Form(default=""),
    age: int = Form(default=None),
    is_smoker: bool = Form(default=False),
    has_diabetes: bool = Form(default=False),
    has_hypertension: bool = Form(default=False),
    is_pregnant: bool = Form(default=None),
    activity_level: str = Form(default="moderate"),
    assessment_for: str = Form(default="myself"),
    model_name: str = Form(default=None),
    x_user_id: str = Header(default="anonymous"),
):
    """Create a diagnosis session from the patient baseline form data."""
    try:
        svc = _get_svc()
        baseline = {
            "patient_name": patient_name or "",
            "gender": gender,
            "age": age,
            "is_smoker": is_smoker,
            "has_diabetes": has_diabetes,
            "has_hypertension": has_hypertension,
            "is_pregnant": is_pregnant,
            "activity_level": activity_level,
            "assessment_for": assessment_for,
        }
        session_id = svc.create_session(baseline, user_id, model_name=model_name)
        return {"status": "success", "data": {"session_id": session_id}}
    except Exception as e:
        import traceback
        return {"status": "error", "detail": str(e), "traceback": traceback.format_exc()}


@router.post("/symptom/select")
async def select_symptom(
    session_id: str = Form(...),
    name: str = Form(..., description="Name of the chosen symptom/illness"),
    idempotency_key: str = Header(default=None, alias="Idempotency-Key"),
):
    """Select a symptom/illness; returns the first SOCRATES follow-up question."""
    cached = _check_idempotency(session_id, idempotency_key)
    if cached:
        return cached

    try:
        svc = _get_svc()
        parsed = {"name_en": name, "search_query": name}
        out = svc.select_symptom(session_id, parsed)
        if "error" in out:
            result = {"status": "error", "detail": out["error"]}
        else:
            result = {"status": "success", "data": out}

        _save_idempotency(session_id, idempotency_key, result)
        return result
    except Exception as e:
        import traceback
        return {"status": "error", "detail": str(e), "traceback": traceback.format_exc()}


@router.get("/follow-up/next")
async def get_next_question(session_id: str = Query(...)):
    """Return the next question in the flow (SOCRATES axis, Bayesian, or diagnosis)."""
    try:
        svc = _get_svc()
        result = svc.get_current_question(session_id)
        return {"status": "success", "data": result}
    except Exception as e:
        import traceback
        return {"status": "error", "detail": str(e), "traceback": traceback.format_exc()}


@router.post("/follow-up/answer")
async def submit_follow_up_answer(
    session_id: str = Form(...),
    question_id: str = Form(...),
    answer: str = Form(...),
    force_diagnosis: bool = Form(False, description="When true, force the engine to produce a diagnosis now"),
    idempotency_key: str = Header(default=None, alias="Idempotency-Key"),
):
    """Submit an answer for a question; returns the next question or final diagnosis."""
    cached = _check_idempotency(session_id, idempotency_key)
    if cached:
        return cached

    try:
        svc = _get_svc()
        result = svc.submit_follow_up_answer(
            session_id,
            question_id,
            answer,
            force_diagnosis=force_diagnosis,
        )
        if "error" in result:
            response = {"status": "error", "detail": result["error"]}
        else:
            response = {"status": "success", "data": result}

        _save_idempotency(session_id, idempotency_key, response)
        return response
    except Exception as e:
        import traceback
        return {"status": "error", "detail": str(e), "traceback": traceback.format_exc()}


@router.get("/report")
async def get_report(session_id: str = Query(...)):
    """Return the current diagnosis results/JSON report for a session."""
    try:
        svc = _get_svc()
        result = svc.get_report(session_id)
        return {"status": "success", "data": result}
    except Exception as e:
        import traceback
        return {"status": "error", "detail": str(e), "traceback": traceback.format_exc()}


def _get_svc():
    """Build a DiagnosisService wired to the app-wide singletons (store/embedder/session/LLM)."""
    from app.services.diagnosis_service import DiagnosisService
    from app.services.llm_service import LLMService
    store = get_store()
    embedder = get_embedder()
    session_mgr = get_session_manager()
    llm = get_llm()
    return DiagnosisService(store, embedder, session_mgr, llm)

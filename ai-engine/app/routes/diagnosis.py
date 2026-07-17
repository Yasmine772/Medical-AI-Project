import json
from fastapi import APIRouter, Form, Query, Header
from app.state import get_store, get_embedder, get_session_manager, get_llm
from app.services.logger import log
from app.services.socrates import build_extract_prompt, parse_llm_response
from app.services.i18n import from_english, detect_lang, translate_batch

router = APIRouter()


@router.get("/symptoms")
async def search_symptoms(q: str = Query(default="", description="Search query for symptoms or illnesses")):
    store = get_store()
    embedder = get_embedder()
    llm = get_llm()

    if not q:
        return {"status": "success", "data": {"query": q, "results": []}}

    query_vector = embedder.encode(q.strip())
    results = store.search(query_vector, limit=10)

    if not results:
        return {"status": "success", "data": {"query": q, "results": []}}

    context_blocks = []
    for i, r in enumerate(results):
        doc = (r.get("document") or "").strip()
        if not doc:
            doc = (r.get("name_en") or r.get("name_ar") or "")
        context_blocks.append(f"[PASSAGE {i+1}]\n{doc[:1200]}")

    context = "\n\n".join(context_blocks)
    lang = detect_lang(q)

    system_prompt = build_extract_prompt(q, context, lang)
    items = []
    for attempt, mdl in enumerate(["llama-3.3-70b-versatile", "llama-3.1-8b-instant"]):
        try:
            raw = llm.ask([
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": f"Extract relevant illnesses/symptoms for the search: {q}"},
            ], temperature=0, max_tokens=1024, model=mdl)
            parsed = parse_llm_response(raw)
            items = parsed.get("results", []) if isinstance(parsed, dict) else []
            if items:
                log("SYMPTOMS", f"Extraction succeeded model={mdl} items={len(items)}")
                break
        except Exception as e:
            err = str(e)
            log("SYMPTOMS", f"Extraction attempt {attempt+1} model={mdl} failed: {err[:80]}")
            if "429" not in err and "quota" not in err.lower() and "rate" not in err.lower():
                break  # non-rate-limit error, don't retry

    # Build a name→result lookup from the search results for source_id matching
    result_by_name = {}
    for r in results:
        rname = (r.get("name_en") or "").strip().lower()
        if rname and rname not in result_by_name:
            result_by_name[rname] = r
        for word in rname.split()[:3]:
            if word and word not in result_by_name:
                result_by_name[word] = r

    def _find_src(name_en: str, fallback_chunk: int = 1) -> dict:
        key = name_en.strip().lower()
        if key in result_by_name:
            return result_by_name[key]
        for r in results:
            rname = (r.get("name_en") or "").strip().lower()
            if key in rname or rname in key:
                return r
        tokens = set(key.split())
        best, best_score = None, 0
        for r in results:
            rname = (r.get("name_en") or "").strip().lower()
            if not rname:
                continue
            overlap = len(tokens & set(rname.split()))
            if overlap > best_score:
                best, best_score = r, overlap
        if best and best_score > 0:
            return best
        chunk_idx = max(0, min(fallback_chunk - 1, len(results) - 1))
        return results[chunk_idx]

    name_en_list = []
    summary_en_list = []
    for it in items:
        if not isinstance(it, dict):
            continue
        name_en = (it.get("name_en") or "").strip()
        if not name_en:
            continue
        kind = (it.get("type") or "").strip().lower()
        if kind not in ("illness", "symptom"):
            kind = "illness"
        fb = int(it.get("source_chunk", 1))
        src = _find_src(name_en, fb)
        summary_text = (it.get("summary") or "").strip()
        if not summary_text:
            summary_text = (
                src.get("symptoms_en")
                or src.get("document")
                or src.get("name_en")
                or ""
            ).strip()
        if len(summary_text) > 200:
            summary_text = summary_text[:200]
        name_en_list.append(name_en)
        summary_en_list.append(summary_text)

    if lang != "en":
        names_ar = translate_batch(name_en_list, lang)
        summaries_ar = translate_batch(summary_en_list, lang)
    else:
        names_ar = name_en_list
        summaries_ar = summary_en_list

    cleaned = []
    for idx, it in enumerate(items):
        if not isinstance(it, dict):
            continue
        name_en = (it.get("name_en") or "").strip()
        if not name_en:
            continue
        kind = (it.get("type") or "").strip().lower()
        if kind not in ("illness", "symptom"):
            kind = "illness"
        fb = int(it.get("source_chunk", 1))
        src = _find_src(name_en, fb)
        cleaned.append({
            "name_en": name_en,
            "name_local": names_ar[idx] if lang != "en" else name_en,
            "type": kind,
            "summary": summaries_ar[idx] if lang != "en" else summary_en_list[idx],
            "source_id": src.get("id", ""),
            "similarity": round(float(src.get("similarity", 0) or 0), 3),
        })

    return {"status": "success", "data": {"query": q, "results": cleaned}}


@router.post("/diagnosis/start")
async def start_diagnosis(
    user_id: str = Form(...),
    gender: str = Form(default=""),
    is_smoker: bool = Form(default=False),
    has_diabetes: bool = Form(default=False),
    has_hypertension: bool = Form(default=False),
    is_pregnant: bool = Form(default=None),
    activity_level: str = Form(default="moderate"),
    assessment_for: str = Form(default="myself"),
    x_user_id: str = Header(default="anonymous"),
):
    try:
        svc = _get_svc()
        baseline = {
            "gender": gender,
            "is_smoker": is_smoker,
            "has_diabetes": has_diabetes,
            "has_hypertension": has_hypertension,
            "is_pregnant": is_pregnant,
            "activity_level": activity_level,
            "assessment_for": assessment_for,
        }
        session_id = svc.create_session(baseline, user_id)
        return {"status": "success", "data": {"session_id": session_id}}
    except Exception as e:
        import traceback
        return {"status": "error", "detail": str(e), "traceback": traceback.format_exc()}


@router.post("/symptom/select")
async def select_symptom(
    session_id: str = Form(...),
    name: str = Form(..., description="Name of the chosen symptom/illness"),
):
    try:
        svc = _get_svc()
        parsed = {"name_en": name, "search_query": name}
        out = svc.select_symptom(session_id, parsed)
        if "error" in out:
            return {"status": "error", "detail": out["error"]}
        return {"status": "success", "data": out}
    except Exception as e:
        import traceback
        return {"status": "error", "detail": str(e), "traceback": traceback.format_exc()}


@router.get("/follow-up/next")
async def get_next_question(session_id: str = Query(...)):
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
):
    try:
        svc = _get_svc()
        result = svc.submit_follow_up_answer(session_id, question_id, answer)
        if "error" in result:
            return {"status": "error", "detail": result["error"]}
        return {"status": "success", "data": result}
    except Exception as e:
        import traceback
        return {"status": "error", "detail": str(e), "traceback": traceback.format_exc()}


@router.get("/report")
async def get_report(session_id: str = Query(...)):
    try:
        svc = _get_svc()
        result = svc.get_report(session_id)
        return {"status": "success", "data": result}
    except Exception as e:
        import traceback
        return {"status": "error", "detail": str(e), "traceback": traceback.format_exc()}


def _get_svc():
    from app.services.diagnosis_service import DiagnosisService
    from app.services.llm_service import LLMService
    store = get_store()
    embedder = get_embedder()
    session_mgr = get_session_manager()
    llm = get_llm()
    return DiagnosisService(store, embedder, session_mgr, llm)

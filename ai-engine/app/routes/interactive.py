import json
import os
from openai import OpenAI
from fastapi import APIRouter, Form, Header, HTTPException, Query

from app.state import get_store, get_embedder, get_session_manager

router = APIRouter()

API_KEY = os.environ.get("GROQ_KEY")
if API_KEY:
    _client = OpenAI(
        base_url="https://api.groq.com/openai/v1",
        api_key=API_KEY,
    )
else:
    _client = None

GROQ_MODEL = "llama-3.3-70b-versatile"
MAX_QUESTIONS = 10
CONFIDENCE_THRESHOLD = 0.70
TOP3_THRESHOLD = 0.85


def _llm_call(messages: list, temperature: float = 0.2, max_tokens: int = 1024) -> str:
    resp = _client.chat.completions.create(
        model=GROQ_MODEL,
        messages=messages,
        temperature=temperature,
        max_tokens=max_tokens,
        response_format={"type": "json_object"},
    )
    return resp.choices[0].message.content or ""


SOCRATES_AXES = [
    "Site — Where exactly is the symptom located?",
    "Onset — When did it start? Sudden or gradual?",
    "Character — Describe the quality (sharp, dull, burning, etc.)",
    "Radiation — Does it spread to other areas?",
    "Associated symptoms — Any other symptoms accompanying it?",
    "Timing — Constant or comes and goes? Any pattern?",
    "Exacerbating / relieving factors — What makes it better or worse?",
    "Severity — How severe is it on a scale of 0-10?",
]

# ── Prior probability mapping from likelihoods text ──

_PRIOR_KEYWORDS = [
    (["شائع جداً", "شائع جدا", "very common", "very severe"], 0.30),
    (["شائع", "common"], 0.20),
    (["شائع نسبياً", "شائع نسبيا", "relatively common"], 0.15),
    (["أقل شيوعاً", "أقل شيوعا", "less common", "moderate"], 0.08),
    (["نادر", "rare"], 0.03),
    (["نادر نسبياً", "نادر نسبيا", "relatively rare"], 0.015),
]

def _compute_priors(diseases: list) -> dict:
    scores = {}
    for d in diseases:
        doc = d.get("document") or ""
        likelihoods = ""
        for line in doc.split("\n"):
            if "الاحتمالية:" in line or "likelihood" in line.lower():
                likelihoods = line.split(":", 1)[-1].strip()
                break
        label = ((d.get("name_en") or "") + " " + likelihoods).lower()
        score = 0.05
        for keywords, val in _PRIOR_KEYWORDS:
            if any(kw in label for kw in keywords):
                score = val
                break
        name_en = d.get("name_en") or d.get("id") or "?"
        scores[name_en] = score
    total = sum(scores.values())
    if total > 0:
        for k in scores:
            scores[k] /= total
    return scores


def _bayes_update(priors: dict, probs_per_option: dict, options: list, answer: str) -> dict:
    # Find which option index the user picked
    chosen_idx = None
    answer_clean = answer.strip()
    for i, opt in enumerate(options):
        if opt.strip() == answer_clean:
            chosen_idx = i
            break
    if chosen_idx is None:
        return priors  # couldn't match answer to options, skip

    posteriors = {}
    evidence = 0.0
    for disease, prior in priors.items():
        probs = probs_per_option.get(disease)
        if not probs or chosen_idx >= len(probs):
            likelihood = 1.0 / len(options)  # uniform fallback
        else:
            likelihood = probs[chosen_idx]
            likelihood = max(0.01, min(0.99, likelihood))  # clamp extremes
        posteriors[disease] = prior * likelihood
        evidence += posteriors[disease]
    if evidence > 0:
        for d in posteriors:
            posteriors[d] /= evidence
    else:
        return priors
    return posteriors


def _check_stopping(probs: dict, socrates_axis: int = 0) -> bool:
    if socrates_axis < 3:  # require at least 3 axes covered before early stop
        return False
    sorted_probs = sorted(probs.items(), key=lambda x: -x[1])
    top1 = sorted_probs[0][1] if sorted_probs else 0
    top3_sum = sum(p for _, p in sorted_probs[:3])
    return top1 >= CONFIDENCE_THRESHOLD or top3_sum >= TOP3_THRESHOLD


def _force_top3(probs: dict, diseases: list = None) -> list:
    disease_map = {}
    if diseases:
        for d in diseases:
            name = d.get("name_en") or ""
            if name:
                disease_map[name] = d

    sorted_probs = sorted(probs.items(), key=lambda x: -x[1])
    result = []
    for i, (name, prob) in enumerate(sorted_probs[:3]):
        if i == 0:
            conf = "Strong"
        elif i == 1:
            conf = "Moderate"
        else:
            conf = "Less Likely"
        info = disease_map.get(name, {})
        result.append({
            "disease_name": name,
            "disease_name_ar": info.get("name_ar") or "",
            "confidence": conf,
            "probability": round(prob, 2),
            "specialist": info.get("specialist") or "",
            "specialist_ar": info.get("specialist_ar") or "",
            "advice": info.get("advice") or "",
            "reasoning": "Auto-diagnosed based on gathered information.",
        })
    return result


def _re_search(embedder, store, conversation: list, existing_diseases: list, existing_probs: dict) -> tuple:
    user_texts = [m.get("content", "") for m in conversation if m.get("role") == "user"]
    if not user_texts or len(user_texts) < 2:
        return existing_diseases, existing_probs

    query = " | ".join(user_texts[-5:])
    query_vector = embedder.encode(query)
    results = store.search(query_vector, limit=5)

    existing_names = {d.get("name_en", "") for d in existing_diseases}
    merged = list(existing_diseases)
    added = False
    for r in results:
        name = r.get("name_en", "")
        if name and name not in existing_names:
            merged.append(r)
            existing_probs[name] = 0.01
            existing_names.add(name)
            added = True

    if added:
        total = sum(existing_probs.values()) or 1
        for k in existing_probs:
            existing_probs[k] /= total

    return merged, existing_probs


def _format_candidates(results: list) -> str:
    lines = []
    for r in results:
        lines.append(
            f"- {r.get('name_en') or '?'} / {r.get('name_ar') or '?'} "
            f"(id: '{r.get('name_en') or r.get('id')}', similarity: {r.get('similarity', 0):.2f})\n"
            f"  Symptoms: {r.get('symptoms_en') or '?'}\n"
            f"  الأعراض: {r.get('symptoms_ar') or '?'}\n"
            f"  Specialist: {r.get('specialist') or '?'} / {r.get('specialist_ar') or '?'}"
        )
    return "\n\n".join(lines)


def _parse_llm_response(content: str) -> dict:
    content = content.strip()
    start = content.find("{")
    end = content.rfind("}")
    if start != -1 and end != -1 and end > start:
        content = content[start : end + 1]
    try:
        return json.loads(content)
    except (json.JSONDecodeError, TypeError):
        return {"type": "error", "raw": content[:300]}


def _build_system_prompt(
    candidates_text: str,
    socrates_axis: int,
    probs_text: str,
) -> str:
    axis_label = SOCRATES_AXES[socrates_axis] if socrates_axis < len(SOCRATES_AXES) else "Any remaining clarifying questions"
    covered = SOCRATES_AXES[:socrates_axis]
    covered_text = "\n".join(f"- {a}" for a in covered) if covered else "None yet"

    return f"""You are a medical diagnosis assistant. Your job is to diagnose the patient by asking targeted follow-up questions based on the possible diseases retrieved from the database.

Possible diseases from database:
{candidates_text}

Current probability estimates:
{probs_text}

SOCRATES framework — axes covered so far:
{covered_text}

Current axis to ask about:
{axis_label}

Rules:
- Respond ONLY with valid JSON, no other text.
- Ask ONE question about the current axis only
- After the patient answers, the system will update probabilities automatically — you do NOT need to output updated probabilities
- Only provide a final diagnosis when you are confident (probability > 70%)
- If you feel confident enough before all axes are covered, you may output a diagnosis early
- Questions must be specific and in Arabic
- You may ask multiple questions on the same axis if needed

IMPORTANT field rules:
- "disease_name" MUST be in English only
- "disease_name_ar" MUST be in Arabic only
- "specialist" MUST be in English only
- "specialist_ar" MUST be in Arabic only
- "advice" MUST be in Arabic only
- "question" MUST be in Arabic only

When asking a question, you MUST include per-option probabilities for each candidate disease.
The "probs_per_option" values MUST sum to ~1.0 for each disease (they are P(option_i | disease)):
{{"type": "question", "question": "question in Arabic", "options": ["option1", "option2", "option3"], "probs_per_option": {{"DiseaseName1": [0.7, 0.2, 0.1], "DiseaseName2": [0.3, 0.4, 0.3]}}}}

When providing a diagnosis, output the top 3 most likely conditions:
{{"type": "diagnosis", "diagnoses": [{{"disease_name": "English name", "disease_name_ar": "اسم المرض", "confidence": "Strong", "probability": 0.72, "specialist": "English specialist", "specialist_ar": "التخصص", "advice": "نصيحة", "reasoning": "explanation"}}, {{"disease_name": "...", "confidence": "Moderate", "probability": 0.18}}, {{"disease_name": "...", "confidence": "Less Likely", "probability": 0.10}}]}}"""


# ── Endpoints ──

@router.get("/symptoms")
async def search_symptoms(q: str = Query(default="", description="Search query")):
    store = get_store()
    embedder = get_embedder()

    if not q:
        return {"results": []}

    query_vector = embedder.encode(q.strip())
    results = store.search(query_vector, limit=10)

    formatted = []
    for r in results:
        formatted.append({
            "key": r.get("name_en") or r.get("id", ""),
            "en": r.get("name_en") or "",
            "ar": r.get("name_ar") or "",
            "symptoms_en": r.get("symptoms_en") or "",
            "symptoms_ar": r.get("symptoms_ar") or "",
            "specialist": r.get("specialist") or "",
        })

    return {"results": formatted}


@router.post("/diagnose/start")
async def diagnose_start(
    symptom: str = Form(..., description="Symptom or disease key from search results"),
    past_diagnoses: str = Form(default=""),
    x_user_id: str = Header(default="anonymous"),
):
    try:
        return await _diagnose_start_impl(symptom, past_diagnoses, x_user_id)
    except Exception as e:
        import traceback
        return {"type": "error", "detail": str(e), "traceback": traceback.format_exc()}


async def _diagnose_start_impl(
    symptom: str,
    past_diagnoses: str = "",
    x_user_id: str = "anonymous",
):
    if not _client:
        raise HTTPException(500, "OPENROUTER_KEY not set")

    store = get_store()
    embedder = get_embedder()
    sm = get_session_manager()

    symptom_en = symptom.replace("_", " ").title()
    query_vector = embedder.encode(symptom)
    results = store.search(query_vector, limit=5)

    priors = _compute_priors(results) if results else {}

    # Build a rich initial message with the chosen disease info + its symptoms
    chosen_info = {}
    for r in (results or []):
        if r.get("name_en", "").lower() == symptom.lower():
            chosen_info = r
            break

    if chosen_info:
        initial_msg = (
            f"I have been experiencing: {chosen_info.get('symptoms_en', symptom_en)}. "
            f"I suspect it might be {chosen_info.get('name_en', symptom_en)}."
        )
    else:
        initial_msg = f"I have symptoms related to {symptom_en}."

    session_data = {
        "diseases": results,
        "socrates_axis": 1,  # start asked axis 0, so next continue asks axis 1
        "probabilities": priors,
    } if results else None

    diseases_text = _format_candidates(results) if results else "No matching diseases found."
    if past_diagnoses:
        diseases_text += f"\n\nPatient's past diagnoses:\n{past_diagnoses}"

    probs_text = "\n".join(f"  {k}: {v*100:.0f}%" for k, v in sorted(priors.items(), key=lambda x: -x[1])) if priors else "  Equal priors"

    system_prompt = _build_system_prompt(diseases_text, 0, probs_text)

    session_id = sm.create_session(x_user_id, symptom, session_data)

    conversation = [{"role": "user", "content": initial_msg}]

    messages = [{"role": "system", "content": system_prompt}, *conversation]

    content = _llm_call(messages)
    parsed = _parse_llm_response(content)

    # If parsing failed, try once more with a stronger hint
    if parsed.get("type") == "error":
        messages.append({"role": "assistant", "content": content})
        messages.append({"role": "user", "content": "Please respond with valid JSON using the exact format specified. You MUST output a JSON object."})
        content = _llm_call(messages, temperature=0.1)
        parsed = _parse_llm_response(content)

    conversation.append({"role": "assistant", "content": content})

    if parsed.get("type") == "diagnosis":
        sm.update_conversation(session_id, conversation, status="completed")
    else:
        sm.update_conversation(session_id, conversation)

    return {
        "session_id": session_id,
        **parsed,
    }


@router.post("/diagnose/continue")
async def diagnose_continue(
    session_id: str = Form(...),
    answer: str = Form(...),
):
    try:
        return await _diagnose_continue_impl(session_id, answer)
    except Exception as e:
        import traceback
        return {"type": "error", "detail": str(e), "traceback": traceback.format_exc()}


async def _diagnose_continue_impl(session_id: str, answer: str):
    if not _client:
        raise HTTPException(500, "OPENROUTER_KEY not set")

    sm = get_session_manager()
    session = sm.get_session(session_id)
    if not session:
        raise HTTPException(404, "Session not found")
    if session.get("status") == "completed":
        raise HTTPException(400, "Session already completed")

    conversation = session.get("conversation", [])
    candidates_raw = session.get("candidates") or {}
    if isinstance(candidates_raw, list):
        candidates_raw = {"diseases": candidates_raw, "socrates_axis": 0, "probabilities": {}}

    diseases = candidates_raw.get("diseases", [])
    socrates_axis = candidates_raw.get("socrates_axis", 0)
    probabilities = candidates_raw.get("probabilities", {})

    question_count = sum(1 for m in conversation if m.get("role") == "assistant")

    # ── Bayesian update from the previous LLM response ──
    last_assistant = None
    for m in reversed(conversation):
        if m.get("role") == "assistant":
            last_assistant = m
            break
    if last_assistant:
        prev = _parse_llm_response(last_assistant.get("content", ""))
        probs_per_option = prev.get("probs_per_option", {})
        options = prev.get("options", [])
        if probs_per_option and options and probabilities:
            probabilities = _bayes_update(probabilities, probs_per_option, options, answer)

    # ── Dynamic re-search: every 3 axes, re-embed conversation to catch new candidates ──
    if socrates_axis >= 3 and socrates_axis % 3 == 0:
        embedder = get_embedder()
        store = get_store()
        diseases, probabilities = _re_search(embedder, store, conversation, diseases, probabilities)

    # ── Check stopping criteria (backend-enforced) ──
    can_stop = _check_stopping(probabilities, socrates_axis)

    # Build prompt
    diseases_text = _format_candidates(diseases) if diseases else "No matching diseases found."
    probs_text = "\n".join(f"  {k}: {v*100:.0f}%" for k, v in sorted(probabilities.items(), key=lambda x: -x[1])) if probabilities else ""

    next_axis = socrates_axis + 1
    force = question_count >= MAX_QUESTIONS or can_stop

    if force:
        system_prompt = _build_system_prompt(diseases_text, socrates_axis, probs_text)
        system_prompt += f"\n\nYou MUST output a diagnosis NOW based on all the information gathered. Output the top 3 most likely conditions."
    else:
        system_prompt = _build_system_prompt(diseases_text, socrates_axis, probs_text)

    conversation.append({"role": "user", "content": answer})
    messages = [{"role": "system", "content": system_prompt}, *conversation]

    content = _llm_call(messages)
    parsed = _parse_llm_response(content)

    # Retry once if JSON parsing failed
    if parsed.get("type") == "error":
        messages.append({"role": "assistant", "content": content})
        messages.append({"role": "user", "content": "Please respond with valid JSON using the exact format specified. You MUST output a JSON object."})
        content = _llm_call(messages, temperature=0.1)
        parsed = _parse_llm_response(content)

    # Detect if LLM output a diagnosis in any format
    llm_diagnosis = (
        parsed.get("type") == "diagnosis"
        or bool(parsed.get("diagnoses"))
        or bool(parsed.get("diagnosis"))
    )

    if force:
        if not llm_diagnosis:
            parsed = {"type": "diagnosis", "diagnoses": _force_top3(probabilities, diseases)}
        else:
            parsed["type"] = "diagnosis"
            if not parsed.get("diagnoses"):
                diag = parsed.pop("diagnosis", {})
                if isinstance(diag, dict) and "top_3" in diag:
                    parsed["diagnoses"] = diag["top_3"]
                else:
                    parsed["diagnoses"] = _force_top3(probabilities, diseases)
        conversation.append({"role": "assistant", "content": json.dumps(parsed)})
        sm.update_conversation(session_id, conversation, status="completed")
        return {
            "session_id": session_id,
            "probabilities": {k: round(v, 2) for k, v in probabilities.items()} if probabilities else {},
            "type": "diagnosis",
            "diagnoses": parsed["diagnoses"],
        }

    # Not forcing — if LLM tried to diagnose, reject and re-prompt
    if llm_diagnosis:
        messages.append({"role": "assistant", "content": content})
        messages.append({"role": "user", "content": "Do NOT diagnose yet. Ask a SOCRATES question with options and probs_per_option."})
        content = _llm_call(messages, temperature=0.1)
        parsed = _parse_llm_response(content)

    conversation.append({"role": "assistant", "content": content})
    candidates_raw["socrates_axis"] = next_axis
    candidates_raw["probabilities"] = probabilities
    candidates_raw["diseases"] = diseases
    sm.update_conversation(session_id, conversation, candidates=candidates_raw)

    return {
        "session_id": session_id,
        "probabilities": {k: round(v, 2) for k, v in probabilities.items()} if probabilities else {},
        **parsed,
    }

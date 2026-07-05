import json
import os
from openai import OpenAI
from fastapi import APIRouter, Form, Header, HTTPException, Query

from app.state import get_store, get_embedder, get_session_manager

router = APIRouter()

API_KEY = os.environ.get("OPENROUTER_KEY")
if API_KEY:
    _client = OpenAI(
        base_url="https://openrouter.ai/api/v1",
        api_key=API_KEY,
    )
else:
    _client = None

INTERACTIVE_MODEL = "openrouter/free"
MAX_QUESTIONS = 10


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


def _build_system_prompt(candidates_text: str) -> str:
    return f"""You are a medical diagnosis assistant. Your job is to diagnose the patient by asking targeted follow-up questions based on the possible diseases retrieved from the database.

Possible diseases from database:
{candidates_text}

Rules:
- Ask ONE question at a time
- Follow SOCRATES framework order: Site → Onset → Character → Radiation → Associated symptoms → Timing / frequency → Exacerbating / relieving factors → Severity
- After each answer, update probability estimates for each candidate disease
- Only provide a final diagnosis when you are confident (probability > 70%)
- If confidence is low, continue asking questions
- Questions must be specific and in Arabic
- Provide answer options ["نعم", "لا"] or specific choices

IMPORTANT field rules:
- "disease_name" MUST be in English only
- "disease_name_ar" MUST be in Arabic only
- "specialist" MUST be in English only
- "specialist_ar" MUST be in Arabic only
- "advice" MUST be in Arabic only
- "question" MUST be in Arabic only

When asking a question, use this format with probability estimates for each candidate disease (values must sum to 1.0):
{{"type": "question", "question": "question in Arabic", "options": ["نعم", "لا"], "probabilities": {{"DiseaseName1": 0.45, "DiseaseName2": 0.30, "DiseaseName3": 0.15, "DiseaseName4": 0.10}}}}

When providing a diagnosis, output the top 3 most likely conditions:
{{"type": "diagnosis", "diagnoses": [{{"disease_name": "English name", "disease_name_ar": "اسم المرض", "confidence": "Strong", "probability": 0.72, "specialist": "English specialist", "specialist_ar": "التخصص", "advice": "نصيحة", "reasoning": "explanation"}}, {{"disease_name": "...", "confidence": "Moderate", "probability": 0.18}}, {{"disease_name": "...", "confidence": "Less Likely", "probability": 0.10}}]}}"""


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
    if not _client:
        raise HTTPException(500, "OPENROUTER_KEY not set")

    store = get_store()
    embedder = get_embedder()
    sm = get_session_manager()

    symptom_en = symptom.replace("_", " ").title()
    query_vector = embedder.encode(symptom)
    results = store.search(query_vector, limit=5)

    formatted = _format_candidates(results) if results else "No matching diseases found."

    if past_diagnoses:
        formatted += f"\n\nPatient's past diagnoses:\n{past_diagnoses}"

    system_prompt = _build_system_prompt(formatted)

    session_id = sm.create_session(x_user_id, symptom, results if results else None)

    conversation = [{"role": "user", "content": f"My symptom: {symptom_en}"}]

    messages = [{"role": "system", "content": system_prompt}, *conversation]

    response = _client.chat.completions.create(
        model=INTERACTIVE_MODEL,
        messages=messages,
        temperature=0.2,
        max_tokens=512,
        response_format={"type": "json_object"},
    )

    content = response.choices[0].message.content
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
    if not _client:
        raise HTTPException(500, "OPENROUTER_KEY not set")

    sm = get_session_manager()
    session = sm.get_session(session_id)
    if not session:
        raise HTTPException(404, "Session not found")
    if session.get("status") == "completed":
        raise HTTPException(400, "Session already completed")

    conversation = session.get("conversation", [])
    candidates = session.get("candidates")

    question_count = sum(1 for m in conversation if m.get("role") == "assistant")

    formatted = _format_candidates(candidates) if candidates else "No matching diseases found."
    system_prompt = _build_system_prompt(formatted)

    conversation.append({"role": "user", "content": answer})

    if question_count >= MAX_QUESTIONS:
        system_prompt += f"\n\nMaximum of {MAX_QUESTIONS} questions reached. You MUST output a diagnosis NOW based on the information gathered. Output the top 3 most likely conditions."

    messages = [{"role": "system", "content": system_prompt}, *conversation]

    response = _client.chat.completions.create(
        model=INTERACTIVE_MODEL,
        messages=messages,
        temperature=0.2,
        max_tokens=512,
        response_format={"type": "json_object"},
    )

    content = response.choices[0].message.content
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

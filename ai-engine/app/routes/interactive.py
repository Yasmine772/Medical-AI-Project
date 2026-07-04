import json
import os
from openai import OpenAI
from fastapi import APIRouter, Form, Header, HTTPException, Depends

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


def _format_candidates(results: list) -> str:
    lines = []
    for r in results:
        lines.append(
            f"- {r.get('name_en') or '?'} / {r.get('name_ar') or '?'} "
            f"(similarity: {r.get('similarity', 0):.2f})\n"
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


SYSTEM_PROMPT = """You are a medical diagnosis assistant. Your job is to diagnose the patient by asking targeted follow-up questions based on the possible diseases retrieved from the database.

Possible diseases from database:
{candidates}

Rules:
- Ask ONE question at a time
- After each answer, narrow down the possibilities
- Only provide a final diagnosis when you are confident (High or Medium confidence)
- If Low confidence, continue asking questions
- Questions must be specific ("Do you have a fever?" not "Tell me more about your symptoms")
- Provide answer options when appropriate ["Yes", "No"]
- Questions should be in Arabic

IMPORTANT field rules:
- "disease_name" MUST be in English only
- "disease_name_ar" MUST be in Arabic only
- "specialist" MUST be in English only
- "specialist_ar" MUST be in Arabic only
- "advice" MUST be in Arabic only
- "question" MUST be in Arabic only

When asking a question, respond with:
{{"type": "question", "question": "question in Arabic", "options": ["نعم", "لا"]}}

When providing a diagnosis, respond with:
{{"type": "diagnosis", "disease_name": "English disease name", "disease_name_ar": "اسم المرض بالعربية", "confidence": "High/Medium/Low", "specialist": "English specialist name", "specialist_ar": "اسم التخصص بالعربية", "advice": "نصيحة علاجية بالعربية", "reasoning": "explanation"}}"""


@router.post("/diagnose/start")
async def diagnose_start(
    symptoms: str = Form(...),
    x_user_id: str = Header(default="anonymous"),
):
    if not _client:
        raise HTTPException(500, "OPENROUTER_KEY not set")

    store = get_store()
    embedder = get_embedder()
    sm = get_session_manager()

    query_vector = embedder.encode(symptoms)
    results = store.search(query_vector, limit=5)

    formatted = _format_candidates(results) if results else "لا توجد أمراض مطابقة في قاعدة البيانات."

    system_prompt = SYSTEM_PROMPT.format(candidates=formatted)

    session_id = sm.create_session(x_user_id, symptoms, results if results else None)

    conversation = [{"role": "user", "content": f"أعاني من الأعراض التالية: {symptoms}"}]

    messages = [{"role": "system", "content": system_prompt}, *conversation]

    response = _client.chat.completions.create(
        model=INTERACTIVE_MODEL,
        messages=messages,
        temperature=0.2,
        max_tokens=512,
    )

    content = response.choices[0].message.content
    parsed = _parse_llm_response(content)

    conversation.append({"role": "assistant", "content": content})
    sm.update_conversation(session_id, conversation)

    if parsed.get("type") == "diagnosis":
        sm.complete_session(session_id, conversation, parsed)
    elif parsed.get("type") == "error":
        conversation.append({"role": "assistant", "content": content})

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

    formatted = _format_candidates(candidates) if candidates else "لا توجد أمراض مطابقة في قاعدة البيانات."
    system_prompt = SYSTEM_PROMPT.format(candidates=formatted)

    conversation.append({"role": "user", "content": answer})

    messages = [{"role": "system", "content": system_prompt}, *conversation]

    response = _client.chat.completions.create(
        model=INTERACTIVE_MODEL,
        messages=messages,
        temperature=0.2,
        max_tokens=512,
    )

    content = response.choices[0].message.content
    parsed = _parse_llm_response(content)

    conversation.append({"role": "assistant", "content": content})
    sm.update_conversation(session_id, conversation)

    if parsed.get("type") == "diagnosis":
        sm.complete_session(session_id, conversation, parsed)

    return {
        "session_id": session_id,
        **parsed,
    }


@router.get("/diagnose/history")
async def diagnose_history(
    x_user_id: str = Header(default="anonymous"),
):
    sm = get_session_manager()
    history = sm.get_user_history(x_user_id)
    return {"history": history}

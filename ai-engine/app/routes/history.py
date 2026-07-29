import json

from fastapi import APIRouter, Query

from app.state import get_session_manager
from app.services.i18n import from_english

router = APIRouter()


@router.get("/diagnosis-history")
async def diagnosis_history(
    user_id: str = Query(default="anonymous"),
    limit: int = Query(default=50, ge=1, le=200),
    language_code: str = Query(default="en", description="Language code for localizing names (e.g. 'ar', 'en')"),
):
    sm = get_session_manager()
    rows = sm.list_sessions_by_user(user_id)
    if not rows:
        return {"status": "success", "data": []}

    history = []
    for row in rows[:limit]:
        candidates = row.get("candidates")
        if isinstance(candidates, str):
            try:
                candidates = json.loads(candidates)
            except (json.JSONDecodeError, TypeError):
                candidates = {}
        probs = (candidates or {}).get("probabilities") or {}
        diseases_list = (candidates or {}).get("diseases") or []
        conversation = (candidates or {}).get("conversation") or []

        id_to_name = {}
        for d in diseases_list:
            did = (d.get("id") or "").strip()
            name = (d.get("name_en") or "").strip()
            if did and name:
                id_to_name[did] = name

        top_name = ""
        top_prob = 0.0
        if probs:
            top_key, top_prob = max(probs.items(), key=lambda x: x[1])
            if len(top_key) > 10 and all(c in "0123456789abcdef" for c in top_key.lower()):
                top_name = id_to_name.get(top_key, "")
            else:
                top_name = top_key

        if not top_name:
            for msg in reversed(conversation):
                if msg.get("role") == "assistant":
                    raw = msg.get("content") or msg.get("text") or ""
                    if not raw:
                        continue
                    try:
                        parsed = json.loads(raw) if isinstance(raw, str) else raw
                        diagnoses = (parsed if isinstance(parsed, dict) else {}).get("diagnoses") or []
                        if diagnoses:
                            top_name = diagnoses[0].get("disease_name") or diagnoses[0].get("name_en") or ""
                            top_prob = diagnoses[0].get("probability", top_prob)
                            break
                    except (json.JSONDecodeError, TypeError, AttributeError):
                        continue

        if top_name and language_code != "en":
            top_name = from_english(top_name, language_code) or top_name

        history.append({
            "session_id": row.get("id"),
            "created_at": row.get("created_at"),
            "status": row.get("status", "unknown"),
            "top_disease": top_name,
            "top_probability": round(float(top_prob), 2),
        })

    return {"status": "success", "data": history}

import json

from fastapi import APIRouter, Query

from app.state import get_session_manager

router = APIRouter()


@router.get("/diagnosis-history")
async def diagnosis_history(
    user_id: str = Query(default="anonymous"),
    limit: int = Query(default=50, ge=1, le=200),
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
        top_name = ""
        top_prob = 0.0
        if probs:
            top_name, top_prob = max(probs.items(), key=lambda x: x[1])
        history.append({
            "session_id": row.get("id"),
            "created_at": row.get("created_at"),
            "status": row.get("status", "unknown"),
            "top_disease": top_name,
            "top_probability": round(float(top_prob), 2),
        })

    return {"status": "success", "data": history}

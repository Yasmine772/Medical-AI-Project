import json
from fastapi import APIRouter, Form, Query, Header, HTTPException
from app.state import get_store, get_embedder, get_session_manager, get_llm
from app.services.socrates import format_candidates

router = APIRouter()


@router.get("/symptoms")
async def search_symptoms(q: str = Query(default="", description="Search query")):
    store = get_store()
    embedder = get_embedder()

    if not q:
        return {"results": []}

    query_vector = embedder.encode_query(q.strip())
    results = store.search(query_vector, limit=10)

    formatted = []
    for r in results:
        rtype = r.get("type", "")
        if rtype == "disease":
            label_en = r.get("name_en") or ""
            label_ar = r.get("name_ar") or ""
            snippet = r.get("symptoms_en") or ""
        else:
            label_en = (r.get("document") or "")[:120]
            label_ar = (r.get("document") or "")[:120]
            snippet = r.get("document") or ""
        formatted.append({
            "key": r.get("id", ""),
            "type": rtype,
            "en": label_en,
            "ar": label_ar,
            "symptoms_en": snippet,
            "symptoms_ar": r.get("symptoms_ar") or "",
            "specialist": r.get("specialist") or "",
        })

    return {"results": formatted}


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


@router.get("/symptoms/questions")
async def get_symptom_questions(
    session_id: str = Query(...),
    symptom_name: str = Query(..., description="Symptom name from search results"),
):
    try:
        svc = _get_svc()
        result = svc.get_symptom_questions(session_id, symptom_name)
        if "error" in result:
            return {"status": "error", "detail": result["error"]}
        return {"status": "success", "data": result}
    except Exception as e:
        import traceback
        return {"status": "error", "detail": str(e), "traceback": traceback.format_exc()}


@router.post("/symptoms/answers")
async def submit_symptom_answers(
    session_id: str = Form(...),
    symptom_name: str = Form(...),
    answers: str = Form(..., description="JSON array of {question_id, answer}"),
    symptoms_complete: bool = Form(default=False),
):
    try:
        svc = _get_svc()
        parsed_answers = json.loads(answers) if isinstance(answers, str) else answers
        result = svc.submit_symptom_answers(session_id, symptom_name, parsed_answers, symptoms_complete)
        if "error" in result:
            return {"status": "error", "detail": result["error"]}
        return {"status": "success", "data": result}
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

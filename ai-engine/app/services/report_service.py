import os
import json
from datetime import datetime
from pathlib import Path
from jinja2 import Environment, FileSystemLoader

from app.state import get_session_manager
from app.services.i18n import from_english

REPORTS_DIR = Path(__file__).resolve().parent.parent.parent / "reports"
TEMPLATES_DIR = Path(__file__).resolve().parent.parent / "templates"

env = Environment(loader=FileSystemLoader(str(TEMPLATES_DIR)))


def _parse_json_fields(data: dict) -> dict:
    for key in ("conversation", "candidates"):
        val = data.get(key)
        if isinstance(val, str):
            try:
                data[key] = json.loads(val)
            except (json.JSONDecodeError, TypeError):
                pass
    return data


def _extract_diagnoses(data: dict) -> list:
    candidates = data.get("candidates") or {}
    if isinstance(candidates, str):
        try:
            candidates = json.loads(candidates)
        except (json.JSONDecodeError, TypeError):
            candidates = {}
    conversation = data.get("conversation") or []
    if isinstance(conversation, str):
        try:
            conversation = json.loads(conversation)
        except (json.JSONDecodeError, TypeError):
            conversation = []

    diagnoses = None
    for msg in reversed(conversation):
        if msg.get("role") == "assistant":
            content = msg.get("content", "")
            try:
                parsed = json.loads(content)
            except (json.JSONDecodeError, TypeError):
                continue
            if parsed.get("type") == "diagnosis":
                diagnoses = parsed.get("diagnoses")
                if diagnoses:
                    break
            diag = parsed.get("diagnosis") or {}
            if isinstance(diag, dict) and "top_3" in diag:
                diagnoses = diag["top_3"]
                if diagnoses:
                    break
            if parsed.get("diagnoses"):
                diagnoses = parsed["diagnoses"]
                break

    if not diagnoses and candidates:
        probs = candidates.get("probabilities") or {}
        diseases = candidates.get("diseases") or []
        if probs:
            sorted_d = sorted(probs.items(), key=lambda x: -x[1])
            disease_map = {}
            for d in diseases:
                name = d.get("name_en") or ""
                if name:
                    disease_map[name] = d
            diagnoses = []
            for i, (name, prob) in enumerate(sorted_d[:3]):
                info = disease_map.get(name, {})
                diagnoses.append({
                    "disease_name": name,
                    "disease_name_ar": info.get("name_ar") or "",
                    "confidence": "Strong" if i == 0 else "Moderate" if i == 1 else "Less Likely",
                    "probability": prob,
                    "specialist": info.get("specialist") or "",
                    "specialist_ar": info.get("specialist_ar") or "",
                    "advice": info.get("advice") or "",
                    "reasoning": "Based on gathered information and Bayesian inference.",
                })

    return diagnoses or []


def _extract_advice(diagnoses: list) -> str:
    if not diagnoses:
        return ""
    for diag in diagnoses:
        advice = diag.get("advice") or ""
        if advice:
            return advice
    return diagnoses[0].get("advice", "") if diagnoses else ""


def _format_timestamp(ts: str | None) -> str:
    if not ts:
        return "—"
    try:
        dt = datetime.fromisoformat(ts.replace("Z", "+00:00"))
        return dt.strftime("%Y-%m-%d %H:%M")
    except (ValueError, AttributeError):
        return ts[:19] if ts else "—"


def _extract_patient_info(data: dict) -> dict:
    """Extract patient demographics from session data."""
    candidates = data.get("candidates") or {}
    if isinstance(candidates, str):
        try:
            candidates = json.loads(candidates)
        except (json.JSONDecodeError, TypeError):
            candidates = {}
    baseline = candidates.get("baseline") or {}
    display_name = baseline.get("patient_name") or data.get("user_id", "—")
    return {
        "display_name": display_name,
        "user_id": data.get("user_id", "—"),
        "gender": baseline.get("gender", ""),
        "age": baseline.get("age"),
        "is_smoker": baseline.get("is_smoker", False),
        "has_diabetes": baseline.get("has_diabetes", False),
        "has_hypertension": baseline.get("has_hypertension", False),
        "is_pregnant": baseline.get("is_pregnant"),
        "activity_level": baseline.get("activity_level", ""),
        "assessment_for": baseline.get("assessment_for", ""),
    }


def build_report_json(session_id: str, language_code: str = "en") -> dict:
    sm = get_session_manager()
    data = sm.get_session(session_id)
    if not data:
        raise ValueError(f"Session {session_id} not found")

    data = _parse_json_fields(data)
    diagnoses = _extract_diagnoses(data)
    advice = _extract_advice(diagnoses)

    for d in diagnoses:
        if language_code != "en":
            d["disease_name_local"] = from_english(d.get("disease_name", ""), language_code) or d.get("disease_name", "")
            d["specialist_local"] = from_english(d.get("specialist", ""), language_code) or d.get("specialist", "")
            d["advice_local"] = from_english(d.get("advice", ""), language_code) or d.get("advice", "")
        else:
            d["disease_name_local"] = d.get("disease_name_local") or d.get("disease_name_ar") or d.get("disease_name", "")
            d["specialist_local"] = d.get("specialist_local") or d.get("specialist_ar") or d.get("specialist", "")
            d["advice_local"] = d.get("advice_local") or d.get("advice", "")

    conversation = data.get("conversation") or []
    if isinstance(conversation, str):
        try:
            conversation = json.loads(conversation)
        except (json.JSONDecodeError, TypeError):
            conversation = []

    transcript = []
    for m in conversation:
        role = m.get("role")
        content = m.get("content", "")
        if role == "assistant":
            try:
                parsed = json.loads(content)
                if parsed.get("type") == "question":
                    content = parsed.get("question", "")
                elif parsed.get("type") == "diagnosis":
                    content = "Final diagnosis provided."
            except (json.JSONDecodeError, TypeError):
                pass
        transcript.append({"role": role, "text": content})

    patient_info = _extract_patient_info(data)
    return {
        "session_id": session_id,
        "patient_name": patient_info["display_name"],
        "user_id": patient_info["user_id"],
        "patient_info": {
            "gender": patient_info["gender"],
            "age": patient_info["age"],
            "is_smoker": patient_info["is_smoker"],
            "has_diabetes": patient_info["has_diabetes"],
            "has_hypertension": patient_info["has_hypertension"],
            "is_pregnant": patient_info["is_pregnant"],
            "activity_level": patient_info["activity_level"],
        },
        "started_at": _format_timestamp(data.get("created_at")),
        "completed_at": _format_timestamp(data.get("updated_at")),
        "status": data.get("status", "COMPLETED"),
        "diagnoses": diagnoses,
        "advice": advice,
        "conversation": transcript,
    }


def generate_report_html(session_id: str, language_code: str = "en") -> str:
    sm = get_session_manager()
    data = sm.get_session(session_id)
    if not data:
        raise ValueError(f"Session {session_id} not found")

    data = _parse_json_fields(data)
    diagnoses = _extract_diagnoses(data)
    advice = _extract_advice(diagnoses)

    candidates = data.get("candidates") or {}
    if isinstance(candidates, str):
        try:
            candidates = json.loads(candidates)
        except (json.JSONDecodeError, TypeError):
            candidates = {}

    # Translate diagnoses if requested
    if language_code != "en":
        for d in diagnoses:
            d["disease_name_local"] = from_english(d.get("disease_name", ""), language_code) or d.get("disease_name", "")
            d["disease_name_ar"] = d["disease_name_local"]
            d["specialist_local"] = from_english(d.get("specialist", ""), language_code) or d.get("specialist", "")
            d["advice_local"] = from_english(d.get("advice", ""), language_code) or d.get("advice", "")
            d["reasoning_local"] = from_english(d.get("reasoning", ""), language_code) or d.get("reasoning", "")

    # Resolve real initial symptom from selected_symptoms
    selected = candidates.get("selected_symptoms") or []
    initial_symptom = "—"
    if selected:
        first = selected[0]
        initial_symptom = (first.get("name_local") or first.get("name_en") or "—")

    # Localize advice string if requested
    advice_local = from_english(advice, language_code) if language_code != "en" and advice else advice

    conversation = data.get("conversation") or []
    if isinstance(conversation, str):
        try:
            conversation = json.loads(conversation)
        except (json.JSONDecodeError, TypeError):
            conversation = []

    patient_info = _extract_patient_info(data)

    # Translate patient-facing fields if requested
    if language_code != "en":
        if patient_info["gender"]:
            patient_info["gender"] = from_english(patient_info["gender"], language_code) or patient_info["gender"]
        if patient_info["activity_level"]:
            patient_info["activity_level"] = from_english(patient_info["activity_level"], language_code) or patient_info["activity_level"]
        patient_info["is_smoker_label"] = from_english("Yes" if patient_info["is_smoker"] else "No", language_code)
        patient_info["has_diabetes_label"] = from_english("Yes" if patient_info["has_diabetes"] else "No", language_code)
        patient_info["has_hypertension_label"] = from_english("Yes" if patient_info["has_hypertension"] else "No", language_code)
        if patient_info["is_pregnant"] is not None:
            patient_info["is_pregnant_label"] = from_english("Yes" if patient_info["is_pregnant"] else "No", language_code)
    else:
        patient_info["is_smoker_label"] = "Yes" if patient_info["is_smoker"] else "No"
        patient_info["has_diabetes_label"] = "Yes" if patient_info["has_diabetes"] else "No"
        patient_info["has_hypertension_label"] = "Yes" if patient_info["has_hypertension"] else "No"
        if patient_info["is_pregnant"] is not None:
            patient_info["is_pregnant_label"] = "Yes" if patient_info["is_pregnant"] else "No"

    template = env.get_template("report.html")
    html = template.render(
        generation_date=datetime.now().strftime("%Y-%m-%d %H:%M"),
        patient_name=patient_info["display_name"],
        user_id=patient_info["user_id"],
        patient_info=patient_info,
        language_code=language_code,
        started_at=_format_timestamp(data.get("created_at")),
        completed_at=_format_timestamp(data.get("updated_at")),
        initial_symptoms=initial_symptom,
        session_status=data.get("status", "COMPLETED"),
        top_diagnoses=diagnoses,
        advice=advice,
        advice_local=advice_local,
        conversation=conversation,
    )
    return html


def generate_pdf(session_id: str, language_code: str = "en") -> bytes:
    html = generate_report_html(session_id, language_code)
    from playwright.sync_api import sync_playwright
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.set_content(html, wait_until="networkidle")
        pdf_bytes = page.pdf(format="A4", print_background=True)
        browser.close()
    return pdf_bytes


def save_pdf(session_id: str, pdf_bytes: bytes) -> str:
    REPORTS_DIR.mkdir(parents=True, exist_ok=True)
    filepath = REPORTS_DIR / f"{session_id}.pdf"
    filepath.write_bytes(pdf_bytes)
    return str(filepath)


def get_pdf_path(session_id: str) -> str | None:
    filepath = REPORTS_DIR / f"{session_id}.pdf"
    if filepath.exists():
        return str(filepath)
    return None

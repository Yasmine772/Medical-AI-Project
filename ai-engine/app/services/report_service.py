import os
import json
from datetime import datetime
from pathlib import Path
from jinja2 import Environment, FileSystemLoader

from app.state import get_session_manager

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


def build_report_json(session_id: str) -> dict:
    sm = get_session_manager()
    data = sm.get_session(session_id)
    if not data:
        raise ValueError(f"Session {session_id} not found")

    data = _parse_json_fields(data)
    diagnoses = _extract_diagnoses(data)
    advice = _extract_advice(diagnoses)

    for d in diagnoses:
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

    return {
        "session_id": session_id,
        "patient_name": data.get("user_id", "—"),
        "started_at": _format_timestamp(data.get("created_at")),
        "completed_at": _format_timestamp(data.get("updated_at")),
        "status": data.get("status", "COMPLETED"),
        "diagnoses": diagnoses,
        "advice": advice,
        "conversation": transcript,
    }


def generate_report_html(session_id: str) -> str:
    sm = get_session_manager()
    data = sm.get_session(session_id)
    if not data:
        raise ValueError(f"Session {session_id} not found")

    data = _parse_json_fields(data)
    diagnoses = _extract_diagnoses(data)
    advice = _extract_advice(diagnoses)

    conversation = data.get("conversation") or []
    if isinstance(conversation, str):
        try:
            conversation = json.loads(conversation)
        except (json.JSONDecodeError, TypeError):
            conversation = []

    template = env.get_template("report.html")
    html = template.render(
        generation_date=datetime.now().strftime("%Y-%m-%d %H:%M"),
        patient_name=data.get("user_id", "—"),
        started_at=_format_timestamp(data.get("created_at")),
        completed_at=_format_timestamp(data.get("updated_at")),
        initial_symptoms=data.get("initial_symptoms", "—"),
        session_status=data.get("status", "COMPLETED"),
        top_diagnoses=diagnoses,
        advice=advice,
        conversation=conversation,
    )
    return html


def generate_pdf(session_id: str) -> bytes:
    html = generate_report_html(session_id)
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

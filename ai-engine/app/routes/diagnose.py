import os
import json
from openai import OpenAI
from fastapi import APIRouter, Form, HTTPException

from app.models.schemas import DiagnoseResponse
from app.state import get_store, get_embedder

router = APIRouter()

API_KEY = os.environ.get("GROQ_KEY")
if API_KEY:
    _client = OpenAI(
        base_url="https://api.groq.com/openai/v1",
        api_key=API_KEY,
    )
else:
    _client = None


def _format_results(results: list) -> str:
    lines = []
    for r in results:
        name_en = r.get('name_en') or ''
        name_ar = r.get('name_ar') or ''
        spec_en = r.get('specialist') or ''
        spec_ar = r.get('specialist_ar') or ''
        symp_en = r.get('symptoms_en') or ''
        symp_ar = r.get('symptoms_ar') or ''
        severity_en = r.get('severity') or ''
        severity_ar = r.get('severity_ar') or ''

        lines.append(
            f"- {name_en} / {name_ar}\n"
            f"  Symptoms: {symp_en}\n"
            f"  الأعراض: {symp_ar}\n"
            f"  Specialist: {spec_en} / {spec_ar}\n"
            f"  Severity: {severity_en} / {severity_ar}"
        )
    return "\n\n".join(lines)


@router.post("/diagnose")
async def diagnose(symptoms: str = Form(...)):
    if not _client:
        raise HTTPException(500, "OPENROUTER_KEY not set in .env")

    store = get_store()
    embedder = get_embedder()

    query_vector = embedder.encode(symptoms)
    results = store.search(query_vector, limit=5)

    if not results:
        return DiagnoseResponse(
            disease_name="Unknown",
            disease_name_ar="غير معروف",
            confidence="Low",
            specialist="General Physician",
            specialist_ar="طبيب عام",
            advice="Please consult a doctor for proper diagnosis.",
            reasoning="No matching diseases found in the database.",
        )

    context = _format_results(results)
    prompt = f"""You are a medical diagnosis assistant. Based on the patient's symptoms and the possible diseases retrieved from the medical database, provide a diagnosis.

Patient symptoms: {symptoms}

Possible diseases from database:
{context}

Respond ONLY with valid JSON, no other text.
IMPORTANT: The "disease_name" field MUST be in English only.
IMPORTANT: The "specialist" field MUST be in English only.
IMPORTANT: The "advice" field MUST be in Arabic only.

{{
  "disease_name": "English disease name",
  "disease_name_ar": "اسم المرض بالعربية",
  "confidence": "High/Medium/Low",
  "specialist": "English specialist name",
  "specialist_ar": "اسم التخصص بالعربية",
  "advice": "نصيحة علاجية بالعربية",
  "reasoning": "brief explanation in English or Arabic of why this diagnosis matches"
}}"""

    response = _client.chat.completions.create(
        model="llama-3.3-70b-versatile",
        messages=[{"role": "user", "content": prompt}],
        temperature=0.2,
        max_tokens=512,
        response_format={"type": "json_object"},
    )

    content = response.choices[0].message.content

    try:
        data = json.loads(content)
        return DiagnoseResponse(**data)
    except (json.JSONDecodeError, TypeError):
        return DiagnoseResponse(
            disease_name="Error",
            disease_name_ar="خطأ",
            confidence="Low",
            specialist="General Physician",
            specialist_ar="طبيب عام",
            advice="Please consult a doctor.",
            reasoning=content[:500],
        )

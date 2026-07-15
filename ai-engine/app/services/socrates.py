import json

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


def detect_lang(text: str) -> str:
    for ch in text:
        if '\u0600' <= ch <= '\u06ff' or '\u0750' <= ch <= '\u077f':
            return "ar"
    return "en"


def format_candidates(results: list) -> str:
    lines = []
    for r in results:
        lines.append(
            f"- {r.get('name_en') or '?'} / {r.get('name_ar') or '?'} "
            f"(id: '{r.get('name_en') or r.get('id')}', similarity: {r.get('similarity', 0):.2f})\n"
            f"  Symptoms: {r.get('symptoms_en') or '?'}\n"
            f"  \u0627\u0644\u0623\u0639\u0631\u0627\u0636: {r.get('symptoms_ar') or '?'}\n"
            f"  Specialist: {r.get('specialist') or '?'} / {r.get('specialist_ar') or '?'}"
        )
    return "\n\n".join(lines)


def parse_llm_response(content: str) -> dict:
    content = content.strip()
    start = content.find("{")
    end = content.rfind("}")
    if start != -1 and end != -1 and end > start:
        content = content[start : end + 1]
    try:
        return json.loads(content)
    except (json.JSONDecodeError, TypeError):
        return {"type": "error", "raw": content[:300]}


def build_system_prompt(
    candidates_text: str,
    socrates_axis: int,
    probs_text: str,
    language: str = "ar",
    force: bool = False,
) -> str:
    axis_label = SOCRATES_AXES[socrates_axis] if socrates_axis < len(SOCRATES_AXES) else "Any remaining clarifying questions"
    covered = SOCRATES_AXES[:socrates_axis]
    covered_text = "\n".join(f"- {a}" for a in covered) if covered else "None yet"
    lang_label = "Arabic" if language == "ar" else "English"

    prompt = f"""You are a medical diagnosis assistant. Your job is to diagnose the patient by asking targeted follow-up questions based on the possible diseases retrieved from the database.

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
- Questions and options MUST be in {lang_label}
- You may ask multiple questions on the same axis if needed

IMPORTANT field rules:
- "disease_name" MUST be in English only
- "disease_name_ar" MUST be in Arabic only
- "specialist" MUST be in English only
- "specialist_ar" MUST be in Arabic only
- "advice" MUST be in {lang_label}
- "question" MUST be in {lang_label}

When asking a question, you MUST include per-option probabilities for each candidate disease.
The "probs_per_option" values MUST sum to ~1.0 for each disease (they are P(option_i | disease)):
{{"type": "question", "question": "question", "options": ["option1", "option2", "option3"], "probs_per_option": {{"DiseaseName1": [0.7, 0.2, 0.1], "DiseaseName2": [0.3, 0.4, 0.3]}}}}

When providing a diagnosis, output the top 3 most likely conditions:
{{"type": "diagnosis", "diagnoses": [{{"disease_name": "English name", "disease_name_ar": "اسم المرض", "confidence": "Strong", "probability": 0.72, "specialist": "English specialist", "specialist_ar": "التخصص", "advice": "advice", "reasoning": "explanation"}}, {{"disease_name": "...", "confidence": "Moderate", "probability": 0.18}}, {{"disease_name": "...", "confidence": "Less Likely", "probability": 0.10}}]}}"""

    if force:
        prompt += "\n\nYou MUST output a diagnosis NOW based on all the information gathered. Output the top 3 most likely conditions."

    return prompt


def build_symptom_questions_prompt(symptom_name: str, candidates_text: str, language: str) -> str:
    lang_label = "Arabic" if language == "ar" else "English"
    return f"""You are a medical assistant. A patient reports the symptom: "{symptom_name}".

Based on the following possible diseases that match this symptom, generate up to 3 short clarifying questions. Each question should help differentiate between these diseases.

Possible diseases:
{candidates_text}

Respond ONLY with valid JSON, no other text.
Return a JSON object:
{{"questions": [
  {{"id": "q0", "text": "question text", "type": "single_choice" | "yes_no", "options": [{{"id": "opt0", "label": "option1"}}, {{"id": "opt1", "label": "option2"}}]}},
  {{"id": "q1", "text": "...", "type": "...", "options": [...]}}
]}}

- Keep questions short and focused on the symptom
- Use "yes_no" type when the answer is simply yes/no (options: [{{"id": "y", "label": "Yes"}}, {{"id": "n", "label": "No"}}])
- Use "single_choice" when there are multiple possible answers
- Questions and labels MUST be in {lang_label}
- Generate up to 3 questions, but fewer is fine if the symptom is simple"""


def build_conclusion_prompt(symptom_name: str, qa_text: str, candidates_text: str, language: str) -> str:
    lang_label = "Arabic" if language == "ar" else "Arabic"
    return f"""Based on the patient's symptom and their answers, write a brief medical conclusion.

Symptom: {symptom_name}
Questions and answers:
{qa_text}

Possible diseases:
{candidates_text}

Respond ONLY with valid JSON, no other text.
Return:
{{"conclusion": "brief conclusion text in {lang_label}"}}"""

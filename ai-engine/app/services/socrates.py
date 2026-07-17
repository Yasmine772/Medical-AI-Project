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

    prompt = f"""You are a medical diagnosis assistant. All output MUST be in English only — the system translates for the patient.

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
- After the patient answers, the system will update probabilities automatically
- Only provide a final diagnosis when you are confident (probability > 70%)
- You may ask multiple questions on the same axis if needed
- ALL text fields (question, options, message, disease_name, specialist, advice) MUST be in English only

You MUST respond with ONE of these three JSON shapes:

1) Ask a SOCRATES question:
{{"type": "question", "question": "question in English", "options": ["option1", "option2"], "probs_per_option": {{"DiseaseName1": [0.7, 0.3], "DiseaseName2": [0.4, 0.6]}}}}

2) If you need more symptoms:
{{"type": "need_more_symptoms", "message": "instruction in English"}}

3) Final diagnosis (English only):
{{"type": "diagnosis", "diagnoses": [{{"disease_name": "English name", "probability": 0.72, "confidence": "Strong", "specialist": "English specialist", "advice": "advice in English"}}]}}"""

    if force:
        prompt += "\n\nYou MUST output a diagnosis NOW based on all the information gathered."

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
    lang_label = "Arabic" if language == "ar" else "English"
    return f"""Based on the patient's symptom and their answers, write a brief medical conclusion.

Symptom: {symptom_name}
Questions and answers:
{qa_text}

Possible diseases:
{candidates_text}

    Respond ONLY with valid JSON, no other text.
    Return:
    {{"conclusion": "brief conclusion text in {lang_label}"}}"""


def build_extract_prompt(query: str, context_blocks: str, language: str) -> str:
    return f"""You are a medical information extractor. A user searched for the medical term: "{query}".

Below are excerpted passages from a medical knowledge base (PDF documents) that matched the search.

{context_blocks}

Your job: extract a clean, human-readable list of distinct medical items mentioned in the passages that are relevant to the search. Each item must be classified as exactly one of:
- "illness": a disease, disorder, or medical condition (e.g. Migraine, Tension-type headache)
- "symptom": a sign, complaint, or manifestation (e.g. nausea, photophobia)

For each item provide:
- "name_en": the English name (required)
- "name_ar": an EMPTY string (the system will translate later — do NOT write Arabic yourself)
- "type": "illness" or "symptom"
- "summary": one short sentence (<= 20 words) describing it, IN ENGLISH
- "source_chunk": the 1-based index of the passage it came from

Rules:
- Do NOT invent items not supported by the passages.
- Deduplicate; merge the same illness/symptom mentioned in multiple passages.
- Prefer specific illness names over vague ones.
- ALL text fields (name_en, summary) MUST be in English only. Never output Arabic or any non-English text.
-     Respond ONLY with valid JSON, no other text, of this exact shape:
{{"results": [{{"name_en": string, "name_ar": string, "type": "illness"|"symptom", "summary": string, "source_chunk": int}}]}}
- If a passage is irrelevant, ignore it. If nothing relevant is found, return {{"results": []}}."""


def build_diagnosis_naming_prompt(candidates_text: str, probs_text: str, language: str) -> str:
    lang_label = "Arabic" if language == "ar" else "English"
    return f"""You are a medical diagnosis assistant. Below are the top retrieved medical-text passages (evidence) and the current Bayesian probability estimates for each passage's associated condition.

Retrieved evidence passages:
{candidates_text}

Current probability estimates (per passage id):
{probs_text}

Your job: produce the top 3 most likely specific ILLNESSES (named conditions) supported by the evidence. Different passages may point to different illnesses — do NOT collapse them into one name.

Respond ONLY with valid JSON, no other text:
{{"diagnoses": [
  {{"disease_name": "English illness name", "probability": <number 0-1 matching the evidence weight>, "confidence": "Strong"|"Moderate"|"Less Likely", "specialist": "English specialist", "advice": "brief advice in English"}}
]}}

Rules:
- disease_name MUST be English only.
- specialist MUST be English only.
- advice MUST be English only.
- The system translates all user-facing text into the patient's language afterwards — do NOT output any non-English text (no Arabic, no other languages).
- Give 3 distinct named illnesses when the evidence supports them.
- probability values should reflect the relative Bayesian weights above (top one highest), and the three should sum to ~1.0.
- Do not invent illnesses not supported by the passages."""

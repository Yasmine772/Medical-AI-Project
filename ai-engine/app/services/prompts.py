"""Single source of truth for every LLM prompt used by the diagnosis flow.

All prompt text and prompt-building functions live here so the wording is
centralized and easy to audit. The sibling :mod:`app.services.socrates`
re-exports the builders it still owns (parsing/formatting helpers) for
backward compatibility.
"""

SOCRATES_AXES = [
    "Site — Where exactly is the symptom located?",
    "Onset — When did it start? Sudden or gradual?",
    "Character — Describe the quality (sharp, dull, burning, etc.)",
    "Associated symptoms — Any other symptoms accompanying it?",
    "Timing — Constant or comes and goes? Any pattern?",
    "Exacerbating / relieving factors — What makes it better or worse?",
    "Severity — How severe is it on a scale of 0-10?",
]


# ---------------------------------------------------------------------------
# Conversation / retry messages surfaced to (and from) the model.
# ---------------------------------------------------------------------------

# Sent in the chat when the user says they have no more symptoms to report.
NO_MORE_SYMPTOMS_MSG = "Patient reports no more symptoms."

# Shown to the patient when the model asks for another symptom.
NEED_MORE_SYMPTOMS_MSG = (
    "To narrow down the diagnosis, please search for an additional symptom "
    "you are experiencing."
)

# Default message shown to the patient when the model asks for another symptom
# but provides no message text of its own.
DEFAULT_NEED_MORE_MSG = "Please search for another symptom."

# System message for the lightweight disease-name extraction calls.
DISEASE_EXTRACTION_SYSTEM_MSG = (
    "You extract disease names from medical text. Output ONLY valid JSON."
)

# Deterministic fallback follow-up questions, used only when the model fails to
# produce a usable question (no LLM re-call — these are generated locally).
FALLBACK_QUESTIONS = [
    ("Where exactly is the symptom located?", ["Chest", "Abdomen", "Head", "Other"]),
    ("When did the symptom start? Was it sudden or gradual?", ["Sudden", "Gradual", "Not sure"]),
    ("How would you describe the quality of the symptom (sharp, dull, burning, etc.)?", ["Sharp", "Dull", "Burning", "Pressure"]),
    ("Does the symptom spread or radiate to other areas?", ["Yes", "No", "Not sure"]),
    ("Are there any other symptoms accompanying it?", ["Yes", "No", "Not sure"]),
    ("Is the symptom constant or does it come and go?", ["Constant", "Comes and goes", "Not sure"]),
    ("What makes the symptom better or worse?", ["Better with rest", "Worse with activity", "No difference"]),
    ("On a scale of 0 to 10, how severe is the symptom?", ["0-3", "4-6", "7-10"]),
]


def build_related_diseases_prompt(name_en: str, context: str) -> str:
    """Prompt that extracts diseases for which a symptom is a recognized feature."""
    return f"""You are given passages retrieved by searching for the patient's reported symptom: "{name_en}".

TASK: Extract ONLY the specific medical conditions/diseases for which "{name_en}" is a recognized symptom or feature. Do NOT list unrelated rare or severe diseases (e.g. cancers, kidney failure) unless the passage explicitly links them to "{name_en}".

For each disease provide its name, a brief description that mentions the symptom, and the relevant medical specialist.

Passages:
{context}

Respond ONLY with valid JSON:
{{"results": [{{"name_en": "Disease Name", "type": "illness", "summary": "brief description mentioning {name_en}", "specialist": "Specialist type"}}]}}"""


def build_disease_names_prompt(context: str) -> str:
    """Prompt that extracts disease names (with specialists) from passages."""
    return f"""Extract specific medical condition/disease names mentioned in these passages. For each, provide the relevant medical specialist.

Passages:
{context}

Respond ONLY with: {{"diseases": [{{"name_en": "disease", "specialist": "Specialist"}}]}}"""


# ---------------------------------------------------------------------------
# Prompt builders.
# ---------------------------------------------------------------------------

def build_system_prompt(
    candidates_text: str,
    socrates_axis: int,
    probs_text: str,
    language: str = "ar",
    force: bool = False,
    baseline: dict | None = None,
    asked_questions: list | None = None,
    no_more_symptoms: bool = False,
    symptoms_text: str = "",
    summary_text: str = "",
) -> str:
    """Build the system prompt for the SOCRATES follow-up loop.

    Args:
        candidates_text: Rendered disease candidates (see :func:`format_candidates`).
        socrates_axis: Index of the next SOCRATES axis to ask about.
        probs_text: Current Bayesian probability estimates (pre-formatted).
        language: ISO code of the patient's language (prompt stays English).
        force: If True, instruct the model to output a diagnosis immediately.
        baseline: Patient demographics dict (age, gender, chronic conditions...).
        asked_questions: List of questions already asked — the model must NOT re-ask these topics.

    Returns:
        str: The system prompt.
    """
    axis_label = SOCRATES_AXES[socrates_axis] if socrates_axis < len(SOCRATES_AXES) else "Any remaining clarifying questions"
    covered = SOCRATES_AXES[:socrates_axis]
    covered_text = "\n".join(f"- {a}" for a in covered) if covered else "None yet"

    symptoms_block = ""
    if symptoms_text:
        symptoms_block = f"\nReported symptom(s): {symptoms_text}\n"

    summary_block = ""
    if summary_text:
        summary_block = f"\nPatient summary so far:\n{summary_text}\n"

    no_more_text = ""
    if no_more_symptoms:
        no_more_text = (
            "\n\nIMPORTANT: The patient has explicitly stated they have NO MORE symptoms to "
            "report. Do NOT ask for additional symptoms and do NOT ask questions that presuppose "
            "a specific new symptom (e.g. 'rate this symptom'). Ask ONLY clarifying questions "
            "about the ALREADY-REPORTED symptom(s) BY NAME (severity, duration, impact on daily "
            "life) — never about generic or unmentioned 'symptoms'. If you already have enough "
            "information, provide the final diagnosis now; otherwise ask at most one or two such "
            "questions and then diagnose."
        )

    # Build patient context from baseline — include ALL values so the LLM
    # does not re-ask questions already answered by the patient.
    ctx_parts = []
    if baseline:
        if baseline.get("age") is not None:
            ctx_parts.append(f"{baseline['age']} years old")
        if baseline.get("gender"):
            ctx_parts.append(baseline["gender"])
        ctx_parts.append("smoker" if baseline.get("is_smoker") else "non-smoker")
        ctx_parts.append("diabetic" if baseline.get("has_diabetes") else "no diabetes")
        ctx_parts.append("hypertensive" if baseline.get("has_hypertension") else "no hypertension")
        if baseline.get("is_pregnant") is True:
            ctx_parts.append("pregnant")
        elif baseline.get("gender") == "female":
            ctx_parts.append("not pregnant")
        if baseline.get("activity_level"):
            ctx_parts.append(f"activity level: {baseline['activity_level']}")
    patient_context = ", ".join(ctx_parts) if ctx_parts else "No patient context provided"

    # Build list of already-asked questions so the LLM avoids repeating topics
    asked_text = ""
    if asked_questions:
        items = "\n".join(f"- {q}" for q in asked_questions)
        asked_text = f"\n\nQuestions ALREADY asked — DO NOT re-ask these topics:\n{items}\n"

    prompt = f"""You are a medical diagnosis assistant. All output MUST be in English only — the system translates for the patient.

Patient context: {patient_context}
{symptoms_block}{summary_block}
Possible diseases from database:
{candidates_text}

Current probability estimates:
{probs_text}

SOCRATES framework — axes covered so far:
{covered_text}

Suggested focus (optional — you may ask a more useful question instead):
{axis_label}
{asked_text}{no_more_text}
Rules:
- Respond ONLY with valid JSON, no other text.
- Ask EXACTLY ONE follow-up question that adds NEW information.
- INTEGRATE, DON'T ISOLATE: the patient reported MULTIPLE symptoms and has specific risk factors (smoking, alcohol, age, etc.). Never question a symptom in isolation. Ask questions that connect them — e.g. temporal order ("Did the vomiting start after the fever appeared?"), shared mechanisms, or risk-factor links ("Given your alcohol use, have you noticed blood in the vomit?").
- DISAMBIGUATE VAGUE SYMPTOMS: if a reported symptom is non-specific (e.g. "pain", "discomfort", "spells", "feeling unwell"), ask ONE quick clarifying question (location or nature) before the detailed loop, so later questions are specific.
- BE CREATIVE, NOT MECHANICAL: do NOT robotically cycle every SOCRATES axis in order, and do NOT ask the same generic question for each symptom. Ask only what is still unknown. You MAY combine several axes into one natural question (e.g. "How severe is the vomiting, and is it worse after eating or when lying down?").
- AVOID REDUNDANCY: the conversation and the "Questions ALREADY asked" list show what is covered. Never repeat or near-duplicate a topic already asked or answered. If an axis was already covered for a symptom, skip it.
- USE THE PATIENT PROFILE: reference age, smoking, alcohol, pregnancy, and chronic conditions when they make a question more relevant.
- ALL text fields (question, options, message, disease_name, specialist, advice) MUST be in English only.
- CLINICAL COHERENCE: every question MUST be specific to the patient's reported symptom(s); never ask about body areas or features that are not clinically plausible.
- NAME THE SYMPTOM: every question MUST explicitly name the reported symptom(s); never use vague "the symptoms".
- NEVER ask about the ABSENCE/NEGATION of a symptom. To check whether another symptom exists, ask a POSITIVE question.
- REPORTED SYMPTOM(S): {symptoms_text}. Do NOT ask "do you have it?" and do NOT list it among associated symptoms; associated-symptom questions must be about OTHER symptoms only.
- For "Associated symptoms", ask ONLY about plausible POSITIVE other symptoms (e.g. fever, dizziness, abdominal pain) — never the reported symptom, never its absence.
- USE THE CONVERSATION: build each question on what the patient already said; do not repeat answered topics.
- For "Severity", ask how much the symptom BOTHERS or LIMITS the patient (0-10), not the disease's medical seriousness.
- REASON BEFORE ASKING: decide whether a question is actually meaningful. "Site" applies ONLY to LOCALIZED symptoms (pain, swelling, rash, lump, soreness). For SYSTEMIC symptoms — vomiting, nausea, fever, fatigue, dizziness, chills, sweating, breathlessness, palpitations — DO NOT ask "where is it located?"; ask about Timing, Character, Associated symptoms, or Exacerbating/relieving factors instead.
- Only provide a final diagnosis when you are confident (probability > 70%).
- NEW SYMPTOMS FROM ANSWERS: if the patient's most recent answer revealed a NEW symptom (e.g. they answered "sweating" to "do you have chills or sweating?"), list those symptom names under "new_symptoms" as short English names. Only list symptoms genuinely revealed by the answer; otherwise omit the field.

You MUST respond with ONE of these three JSON shapes:

1) Ask a SOCRATES question:
{{"type": "question", "question": "question in English", "options": ["option1", "option2"], "probs_per_option": {{"DiseaseName1": [0.7, 0.3], "DiseaseName2": [0.4, 0.6]}}, "new_symptoms": ["English symptom name"]}}

2) If you need more symptoms:
{{"type": "need_more_symptoms", "message": "instruction in English"}}

3) Final diagnosis (English only):
{{"type": "diagnosis", "diagnoses": [{{"disease_name": "English name", "probability": 0.72, "confidence": "Strong", "specialist": "English specialist", "advice": "advice in English"}}]}}"""

    if force:
        prompt += "\n\nYou MUST output a diagnosis NOW based on all the information gathered."

    return prompt


def build_extract_prompt(query: str, context_blocks: str, language: str) -> str:
    """Build the prompt that extracts illnesses/symptoms from PDF passages."""
    return f"""You are a medical information extractor. Below are excerpted passages from a medical knowledge base (PDF documents).

{context_blocks}

Extract a clean, human-readable list of distinct medical items mentioned in the passages. Each item must be classified as exactly one of:
- "illness": a disease, disorder, or medical condition (e.g. Migraine, Tension-type headache)
- "symptom": a sign, complaint, or manifestation (e.g. nausea, photophobia)

For each item provide:
- "name_en": the English name (required)
- "type": "illness" or "symptom"
- "summary": one short sentence (<= 20 words) describing it, IN ENGLISH
- "source_chunk": the 1-based index of the passage it came from

Rules:
- Extract from ALL passages. Do NOT filter by relevance to the search term.
- Do NOT invent items not supported by the passages.
- Deduplicate; merge the same illness/symptom mentioned in multiple passages.
- Prefer specific illness names over vague ones.
- Return AT MOST 20 items. If there are more, keep the most relevant/distinct ones.
- CRITICAL: each item MUST be a SINGLE, specific symptom or condition. NEVER concatenate
  multiple findings into one name (e.g. do NOT produce "coughing up frothy phlegm and thick
  mucus and blood" — emit "Coughing up blood", "Productive cough", etc. as SEPARATE items).
- ALL text fields (name_en, summary) MUST be in English only. Never output Arabic or any non-English text.
- Respond ONLY with valid JSON, no other text, of this exact shape:
{{"results": [{{"name_en": string, "type": "illness"|"symptom", "summary": string, "source_chunk": int}}]}}"""


def build_diagnosis_naming_prompt(candidates_text: str, probs_text: str, language: str, priors_text: str = "") -> str:
    """Build the prompt that names the top-3 diagnosed illnesses."""
    lang_label = "Arabic" if language == "ar" else "English"
    priors_block = f"\nPatient risk factors: {priors_text}\n" if priors_text else ""
    return f"""You are a medical diagnosis assistant. Below are the top retrieved medical-text passages (evidence) and the current Bayesian probability estimates for each passage's associated condition.

Retrieved evidence passages:
{candidates_text}
{priors_block}
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
- Do not invent illnesses not supported by the passages.
- USE THE PATIENT'S RISK FACTORS: if provided above, factor them into the differential and the advice (e.g. a smoker with a cough should prioritize respiratory conditions and advise smoking cessation; alcohol use should raise GI/liver considerations). Do not ignore them.
- CRITICAL for advice: each diagnosis MUST have unique, disease-specific advice. Never copy the same advice text across multiple diagnoses.
- The advice must:
  - Name the specific condition (e.g., "Migraine: rest in a quiet dark room, avoid triggers...").
  - Give concrete next steps, common treatments, and red flags / when to seek urgent care for THAT illness.
  - Reference the patient's context (age, gender, pregnancy, chronic conditions, risk factors) where relevant.
  - Be 2-3 sentences, actionable, and tailored to the illness — NOT generic like "seek medical attention"."""

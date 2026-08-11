"""Prompt construction and LLM-response parsing for the diagnosis flow.

Responsibilities:
  - Build the system prompts that drive the SOCRATES questioning loop,
    symptom extraction, and diagnosis naming.
  - Parse raw LLM output (possibly malformed or wrapped in markdown) into
    a dict via :func:`parse_llm_response`.
"""
import json
import re


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


def format_candidates(results: list) -> str:
    """Render a list of disease candidates as a bulleted block for a prompt."""
    lines = []
    for r in results:
        lines.append(
            f"- {r.get('name_en') or '?'} "
            f"(id: '{r.get('name_en') or r.get('id')}', similarity: {r.get('similarity', 0):.2f})\n"
            f"  Symptoms: {r.get('symptoms_en') or '?'}\n"
            f"  Specialist: {r.get('specialist') or '?'}"
        )
    return "\n\n".join(lines)


def _repair_json(text: str) -> str:
    """Fix common LLM JSON errors: missing commas, trailing commas, unclosed brackets."""
    # Close any unclosed brackets at the end
    opens = text.count("{")
    closes = text.count("}")
    for _ in range(opens - closes):
        text += "}"
    opens = text.count("[")
    closes = text.rstrip().count("]")
    for _ in range(opens - closes):
        text += "]"
    # Add missing commas between a closing quote and a new opening quote on next line
    text = re.sub(r'"\s*\n\s*"', r'",\n"', text)
    # Remove trailing commas before closing brackets
    text = re.sub(r",\s*([}\]])", r"\1", text)
    return text


def _salvage_json_objects(text: str) -> list:
    """Extract every complete top-level JSON object embedded in ``text``.

    Used when the LLM output was truncated (e.g. hits ``max_tokens`` mid-JSON
    after enumerating many array entries). Each fully-formed ``{...}`` block is
    decoded independently; incomplete trailing objects are skipped.

    Args:
        text (str): Possibly truncated text containing JSON objects.

    Returns:
        list: The decoded dicts, in order of appearance.
    """
    decoder = json.JSONDecoder()
    objects = []
    i = 0
    while i < len(text):
        start = text.find("{", i)
        if start == -1:
            break
        try:
            obj, end = decoder.raw_decode(text, start)
            if isinstance(obj, dict):
                objects.append(obj)
            i = end
        except (json.JSONDecodeError, TypeError):
            i = start + 1
    return objects


def parse_llm_response(content) -> dict:
    """Extract the first JSON object from an LLM reply.

    Handles content wrapped in markdown fences and/or followed by prose.
    Falls back to :func:`_repair_json` for common malformations, and finally
    to :func:`_salvage_json_objects` so truncated-but-otherwise-valid replies
    (common when the model lists many entries past ``max_tokens``) still yield
    the completed items. As a last resort returns ``{"results": []}``.

    Args:
        content: The raw LLM output (string or already-parsed dict).

    Returns:
        dict: The parsed JSON object (or ``{"results": []}`` on failure).
    """
    if isinstance(content, dict):
        return content
    if not isinstance(content, str):
        return {"results": []}
    content = content.strip()
    start = content.find("{")
    if start == -1:
        return {"results": []}
    try:
        decoder = json.JSONDecoder()
        parsed, _ = decoder.raw_decode(content, start)
        return parsed if isinstance(parsed, dict) else {"results": []}
    except (json.JSONDecodeError, TypeError):
        pass
    try:
        repaired = _repair_json(content)
        return json.loads(repaired)
    except (json.JSONDecodeError, TypeError):
        pass
    # Truncated output: keep every complete object. Wrap them under "results",
    # which is the key symptom/illness extraction and diagnosis callers read.
    salvaged = _salvage_json_objects(content)
    if salvaged:
        return {"results": salvaged}
    return {"results": []}


def build_system_prompt(
    candidates_text: str,
    socrates_axis: int,
    probs_text: str,
    language: str = "ar",
    force: bool = False,
    baseline: dict | None = None,
    asked_questions: list | None = None,
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

Possible diseases from database:
{candidates_text}

Current probability estimates:
{probs_text}

SOCRATES framework — axes covered so far:
{covered_text}

Current axis to ask about:
{axis_label}
{asked_text}
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


def build_extract_prompt(query: str, context_blocks: str, language: str) -> str:
    """Build the prompt that extracts illnesses/symptoms from PDF passages.

    Args:
        query: The (English) search query, used only for context.
        context_blocks: Rendered PDF passages to extract from.
        language: ISO code of the patient's language (output stays English).

    Returns:
        str: The extraction prompt.
    """
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
- ALL text fields (name_en, summary) MUST be in English only. Never output Arabic or any non-English text.
- Respond ONLY with valid JSON, no other text, of this exact shape:
{{"results": [{{"name_en": string, "type": "illness"|"symptom", "summary": string, "source_chunk": int}}]}}"""


def build_diagnosis_naming_prompt(candidates_text: str, probs_text: str, language: str) -> str:
    """Build the prompt that names the top-3 diagnosed illnesses.

    Args:
        candidates_text: Rendered evidence passages.
        probs_text: Current Bayesian probability estimates (pre-formatted).
        language: ISO code of the patient's language (output stays English).

    Returns:
        str: The diagnosis-naming prompt.
    """
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
- Do not invent illnesses not supported by the passages.
- CRITICAL for advice: each diagnosis MUST have unique, disease-specific advice. Never copy the same advice text across multiple diagnoses.
- The advice must:
  - Name the specific condition (e.g., "Migraine: rest in a quiet dark room, avoid triggers...").
  - Give concrete next steps, common treatments, and red flags / when to seek urgent care for THAT illness.
  - Reference the patient's context (age, gender, pregnancy, chronic conditions) where relevant.
  - Be 2-3 sentences, actionable, and tailored to the illness — NOT generic like "seek medical attention"."""


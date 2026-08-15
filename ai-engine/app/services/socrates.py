"""Prompt construction and LLM-response parsing for the diagnosis flow.

Responsibilities:
  - Build the system prompts that drive the SOCRATES questioning loop,
    symptom extraction, and diagnosis naming.
  - Parse raw LLM output (possibly malformed or wrapped in markdown) into
    a dict via :func:`parse_llm_response`.
"""
import json
import re

# Prompt text and prompt-builders live in app.services.prompts; re-export them
# here so existing imports (e.g. `from app.services.socrates import build_system_prompt`)
# keep working.
from app.services.prompts import (  # noqa: F401
    SOCRATES_AXES,
    build_system_prompt,
    build_extract_prompt,
    build_diagnosis_naming_prompt,
)


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


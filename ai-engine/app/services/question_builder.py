"""Pure helpers for building/formatting follow-up questions.

No I/O here: these functions only shape question payloads and compute the
dynamic per-symptom question cap, so they stay trivially testable and are
shared by the diagnosis service without dragging in dependencies.
"""

MIN_PER_SYMPTOM = 2
DYNAMIC_EXTRA = 6

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


def build_fallback_question(socrates_axis: int) -> dict:
    """Return a deterministic follow-up question when the LLM insists on
    diagnosing too early, so we can keep the required minimum number of
    questions before ever emitting a diagnosis."""
    question, options = FALLBACK_QUESTIONS[
        socrates_axis % len(FALLBACK_QUESTIONS)
    ]
    return {
        "type": "question",
        "question": question,
        "options": options,
        "probs_per_option": {o: 1.0 / len(options) for o in options},
    }


def to_question_options(options_raw: list) -> list:
    """Convert raw option labels into ``{"id", "label"}`` entries."""
    if not options_raw:
        return []
    return [{"id": str(i + 1), "label": str(o)} for i, o in enumerate(options_raw)]


def infer_qtype(options_raw: list) -> str:
    """Guess a question type from its options (``yes_no`` vs ``single_choice``)."""
    if not options_raw:
        return "single_choice"
    n = [str(o).strip().lower() for o in options_raw]
    if n == ["yes", "no"] or n in (["y", "n"], ["نعم", "لا"]):
        return "yes_no"
    return "single_choice"


def per_symptom_cap(top1_prior: float, remaining: int, min_per: int) -> int:
    """Dynamic cap on questions per symptom, bounded by the remaining budget.

    The more uncertain we are (low top-1 probability) the more questions we
    allow, but never more than ``remaining - min_per`` and never less than
    ``min_per``.
    """
    uncertainty = 1.0 - float(top1_prior)
    requested = min_per + round(uncertainty * DYNAMIC_EXTRA)
    budget_room = remaining - min_per
    cap = min(requested, budget_room) if budget_room > 0 else min_per
    return max(min_per, cap)


def short_probs(probs: dict) -> dict:
    """Trim a probabilities dict to its top-5 entries, rounded, for logs."""
    return {k: round(v, 2) for k, v in sorted(probs.items(), key=lambda x: -x[1])[:5]}

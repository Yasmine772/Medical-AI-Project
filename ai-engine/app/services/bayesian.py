"""Bayesian reasoning for the diagnosis engine.

Priors are derived from keyword-based likelihood labels in the source docs
(or vector similarity as a fallback), then updated with each patient answer
via naive Bayes. Stopping and top-3 selection are threshold-driven.
"""
_PRIOR_KEYWORDS = [
    (["شائع جداً", "شائع جدا", "very common", "very severe"], 0.30),
    (["شائع", "common"], 0.20),
    (["شائع نسبياً", "شائع نسبيا", "relatively common"], 0.15),
    (["أقل شيوعاً", "أقل شيوعا", "less common", "moderate"], 0.08),
    (["نادر", "rare"], 0.03),
    (["نادر نسبياً", "نادر نسبيا", "relatively rare"], 0.015),
]

CONFIDENCE_THRESHOLD = 0.70
TOP3_THRESHOLD = 0.85
MAX_QUESTIONS = 25


def compute_priors(diseases: list) -> dict:
    """Compute normalized prior probabilities for a list of candidate diseases.

    Each disease's prior is read from a "likelihood" label found in its
    document text, or falls back to its vector similarity score.

    Args:
        diseases (list): Disease dicts (with ``name_en``, ``document``, ``similarity``).

    Returns:
        dict: ``{name_en: probability}`` summing to 1.0.
    """
    scores = {}
    used_similarity = False
    for d in diseases:
        doc = d.get("document") or ""
        likelihoods = ""
        for line in doc.split("\n"):
            if "الاحتمالية:" in line or "likelihood" in line.lower():
                likelihoods = line.split(":", 1)[-1].strip()
                break
        label = ((d.get("name_en") or "") + " " + likelihoods).lower()
        score = None
        for keywords, val in _PRIOR_KEYWORDS:
            if any(kw in label for kw in keywords):
                score = val
                break
        if score is None:
            sim = d.get("similarity")
            if isinstance(sim, (int, float)) and sim > 0:
                score = max(0.05, min(0.95, float(sim)))
                used_similarity = True
            else:
                score = 0.05
        name_en = d.get("name_en") or d.get("id") or "?"
        scores[name_en] = score
    total = sum(scores.values())
    if total > 0:
        for k in scores:
            scores[k] /= total
    return scores


def bayes_update(priors: dict, probs_per_option: dict, options: list, answer: str) -> dict:
    """Apply a Bayes update given the answer a patient chose.

    Args:
        priors (dict): Current ``{disease: probability}``.
        probs_per_option (dict): ``{disease: [p(opt0), p(opt1), ...]}`` from the LLM.
        options (list): Option labels the patient could pick.
        answer (str): The chosen option label.

    Returns:
        dict: Updated posterior ``{disease: probability}``.
    """
    chosen_idx = None
    answer_clean = answer.strip()
    for i, opt in enumerate(options):
        if opt.strip() == answer_clean:
            chosen_idx = i
            break
    if chosen_idx is None:
        return priors

    posteriors = {}
    evidence = 0.0
    for disease, prior in priors.items():
        probs = probs_per_option.get(disease)
        if not probs or chosen_idx >= len(probs):
            likelihood = 1.0 / len(options)
        else:
            likelihood = probs[chosen_idx]
            likelihood = max(0.01, min(0.99, likelihood))
        posteriors[disease] = prior * likelihood
        evidence += posteriors[disease]
    if evidence > 0:
        for d in posteriors:
            posteriors[d] /= evidence
    else:
        return priors
    return posteriors


def check_stopping(probs: dict, socrates_axis: int = 0) -> bool:
    """Return True when the top candidate is confident enough to stop asking.

    Requires at least 3 SOCRATES axes answered, then checks the top-1
    probability against ``CONFIDENCE_THRESHOLD`` or the top-3 sum against
    ``TOP3_THRESHOLD``.
    """
    if socrates_axis < 3:
        return False
    sorted_probs = sorted(probs.items(), key=lambda x: -x[1])
    top1 = sorted_probs[0][1] if sorted_probs else 0
    top3_sum = sum(p for _, p in sorted_probs[:3])
    return top1 >= CONFIDENCE_THRESHOLD or top3_sum >= TOP3_THRESHOLD


def force_top3(probs: dict, diseases: list = None, labels: dict = None) -> list:
    """Return the top-3 diseases as user-facing diagnosis dicts.

    Used when the LLM path fails or to backfill an incomplete naming result.

    Args:
        probs (dict): ``{disease: probability}``.
        diseases (list, optional): Candidate disease dicts for metadata lookup.
        labels (dict, optional): ``{disease: {name_en, ...}}`` overrides.

    Returns:
        list: Up to 3 dicts with ``disease_name``, ``confidence``,
              ``probability``, ``specialist``, ``advice``, ``reasoning``.
    """
    disease_map = {}
    if diseases:
        for d in diseases:
            name = d.get("name_en") or ""
            if name:
                disease_map[name] = d

    sorted_probs = sorted(probs.items(), key=lambda x: -x[1])
    result = []
    for i, (name, prob) in enumerate(sorted_probs[:3]):
        conf = "Strong" if i == 0 else "Moderate" if i == 1 else "Less Likely"
        info = disease_map.get(name, {})
        label = (labels or {}).get(name, {})
        display_en = label.get("name_en") or info.get("name_en") or name
        result.append({
            "disease_name": display_en,
            "confidence": conf,
            "probability": round(prob, 2),
            "specialist": info.get("specialist") or "",
            "specialist_ar": info.get("specialist_ar") or "",
            "advice": info.get("advice") or "",
            "reasoning": "Auto-diagnosed based on gathered information.",
        })
    return result

"""Extract medical specializations mentioned across the Supabase ``embeddings``
table's PDF rows, with NO fixed list of names and NO LLM calls.

Free-text suffix matching is far too noisy (it catches "Magnevist", "Twist",
"Action By The Physician", ...). So we anchor every match to REFERRAL CONTEXT —
the way a real specialty is actually named in clinical text:

    "refer (to|red) a cardiologist", "consult an orthopedic surgeon",
    "under the care of a neurologist", "skin specialist", "see a dentist" ...

Only specialty-shaped words that follow one of those triggers are kept. We also
fold in the disease table's own ``specialist`` column (real specializations that
exist in the DB rows).

Deterministic and credit-free.

Usage (from the ai-engine folder, venv active):
    .venv\\Scripts\\python.exe extract_specializations.py
"""
import os
import re
import json

from dotenv import load_dotenv

load_dotenv()

from app.services.pgvector_client import PgVectorClient

# Clinical-specialty word-formation suffixes (a RULE, not a name list).
SUFFIX = r"(?:ologist|iatrist|surgeon|physician|specialist|practitioner|therapist|technician)"

# Referral / attribution context that precedes a real specialty mention.
TRIGGERS = (
    r"refer(?:red)? to|referral to|consult(?:ed)?|see|seen by|visit|"
    r"specialist in|specialist for|under (?:the )?care of|treated by|"
    r"managed by|advised by|examination by|reviewed by|assessed by|"
    r"care of|attending|by a|by an|by the"
)

REF = re.compile(
    r"\b(?:" + TRIGGERS + r")\s+(?:a|an|the|your|their|our|his|her)?\s*"
    r"((?:[A-Za-z]+(?:\s+[A-Za-z]+){0,2}\s+)?" + SUFFIX + r")\b",
    re.I,
)

IST = re.compile(
    r"\b(?:" + TRIGGERS + r")\s+(?:a|an|the|your|their|our|his|her)?\s*([A-Za-z]*ist)\b",
    re.I,
)
# Negative filter: obviously non-clinical "...ist" words (a stop-list only).
NON_CLINICAL_IST = {
    "scientist", "artist", "tourist", "journalist", "feminist", "optimist",
    "pessimist", "lobbyist", "cyclist", "motorist", "typist", "novelist",
    "violinist", "chemist", "physicist", "geologist", "archaeologist",
    "anthropologist", "philologist", "etymologist", "chronologist",
    "astrologist", "futurologist", "methodologist", "terminologist",
    "lexicologist", "sexologist", "thanatologist", "ontologist", "cosmologist",
    "mycologist", "ecologist", "zoologist", "botanist", "ornithologist",
    "palaeontologist", "embryologist", "histologist", "cytologist", "geneticist",
    "biologist", "physiologist", "pharmacologist", "toxicologist",
    "epidemiologist", "virologist", "bacteriologist", "microbiologist",
    "parasitologist", "agonist", "antagonist", "frequentist", "receptionist",
    "entomologist", "neuroscientist", "list",
}

STOPWORDS = {
    "a", "an", "the", "this", "that", "these", "those", "your", "our", "my",
    "his", "her", "their", "each", "such", "any", "some", "is", "are", "was",
    "were", "be", "and", "or", "of", "in", "on", "for", "with", "to", "at",
    "by", "as", "if", "when", "which", "who", "from", "into", "about",
    "between", "patient", "patients",
}

# Leading adjectives / descriptors that prefix a specialty but are not part of
# the name (e.g. "Experienced Specialist", "Primary Health Care Physician").
ADJ_NOISE = {
    "experienced", "qualified", "appropriate", "mental", "primary", "pioneering",
    "russian", "chief", "senior", "junior", "old", "young", "new", "leading",
    "training", "trained", "resident", "attending", "clinical",
}


def _clean(phrase: str) -> str:
    phrase = phrase.strip().strip("/|,-").strip()
    words = phrase.split()
    # Drop a single-letter leading fragment (OCR artifact, e.g. "N Allergy Specialist").
    if words and len(words[0]) == 1:
        words.pop(0)
    while words and words[0].lower() in STOPWORDS:
        words.pop(0)
    while words and words[0].lower() in ADJ_NOISE:
        words.pop(0)
    while words and words[-1].lower() in STOPWORDS:
        words.pop()
    if not words:
        return ""
    return re.sub(r"\s+", " ", " ".join(words)).title()


def main():
    client = PgVectorClient()
    client.connect()
    sb = client._supabase

    found = set()

    # 1) Disease table's own specialist values (real specializations in rows).
    start = 0
    page = 1000
    while True:
        rows = (
            sb.table("embeddings")
            .select("specialist")
            .eq("type", "disease")
            .range(start, start + page - 1)
            .execute()
            .data
        )
        if not rows:
            break
        for r in rows:
            spec = (r.get("specialist") or "").strip()
            if spec:
                for part in re.split(r"\s*[/&,]\s*", spec):
                    c = _clean(part)
                    if c:
                        found.add(c)
        start += page
        if len(rows) < page:
            break

    # 2) Scan every PDF document, anchored to referral context (no LLM).
    scanned = 0
    start = 0
    while True:
        rows = (
            sb.table("embeddings")
            .select("document")
            .eq("type", "pdf")
            .range(start, start + page - 1)
            .execute()
            .data
        )
        if not rows:
            break
        for r in rows:
            scanned += 1
            doc = r.get("document") or ""
            if not doc:
                continue
            for m in REF.findall(doc):
                c = _clean(m)
                if c:
                    found.add(c)
            for m in IST.findall(doc):
                if m.lower() in NON_CLINICAL_IST:
                    continue
                c = _clean(m)
                if c:
                    found.add(c)
        start += page
        if len(rows) < page:
            break

    out = sorted(found)
    print(f"Scanned PDF rows : {scanned}")
    print(f"Distinct specializations found: {len(out)}")
    for s in out:
        print(" -", s)

    with open("specializations.json", "w", encoding="utf-8") as f:
        json.dump(out, f, ensure_ascii=False, indent=2)
    with open("specializations.txt", "w", encoding="utf-8") as f:
        f.write("\n".join(out))
    print("\nWrote specializations.json and specializations.txt")


if __name__ == "__main__":
    main()

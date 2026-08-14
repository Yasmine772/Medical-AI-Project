# MediScan AI Engine — Full Tutorial for the Review Panel

This guide lets you explain **and demonstrate** the entire AI engine to the
professors in under two minutes. It covers:

- the architecture & how it fits with the Laravel backend + mobile/web frontends
- **the diagnosis flow end-to-end** (start → search → select → SOCRATES questions → Bayesian update → diagnosis → PDF)
- a ready-to-run live demo command block (copy‑paste)
- the key files to point at while talking

---

## 1. Big picture (architecture, 1 slide)

```
            ┌──────────────────┐        form/JSON         ┌────────────────────────┐
   Patient  │  Web / Mobile   │ ───────────────────────▶ │ Laravel API (api-     │
   Doctor   │  (React, etc.)  │  REST + Sanctum token    │ backend, PHP 8.x)      │
            └──────────────────┘                          │                        │
                                                         │  orchestrator:         │
                                                         │  auth, payments,       │
                                                         │  doctor assignment,    │
                                                         │  Stripe webhook        │
                                                         └───────────┬────────────┘
                                                                     │ HTTP form / JSON
                                                  ┌──────────────────┴──────────────────┐
                                                  │  MediScan AI Engine (FastAPI)        │
                                                  │  ai-engine/, Python 3.10+, port 5000 │
                                                  │                                      │
                                                  │   ┌─ store  PgVectorClient  (Supabase)│
                                                  │   ├─ embedder EmbeddingService (E5)  │
                                                  │  ├─ LLM  LLMService (Gemini/CF)     │
                                                  │  ├─ SM   SessionManager (Postgres)  │
                                                  │  ├─ Bayes bayesian.py               │
                                                  │  ├─ SOCRATES socrates.py            │
                                                  │  └─ Report report_service.py (PDF)   │
                                                  └──────────────────────────────────────┘
```

- **ai-engine** owns *all medical reasoning*: vector search, LLM prompts, Bayes updating, question generation, PDF building.
- **api-backend** owns *business*: auth, billing, doctor assignment, notifications, and simply proxies requests to the engine.
- **web frontend** & **mobile** are thin clients that only render JSON from Laravel.
- They are deployed **independently** (Docker) and connected by a contract of routes (see `gen_diagram.php` for the official map).

---

## 2. Boot sequence (so nothing looks "magic")

File → `app/main.py` and `app/state.py`.
- `lifespan` connects to **PgVector** (`PgVectorClient.connect`) and builds three shared singletons:
  - store (embeddings), embedder (multilingual E5), SessionManager, and LLM client.
- `app/state.py` keeps these as module globals (`_store`, `_embedder`, …) returned by `get_*()` functions — so every request handler uses the *same* connection pool + LLM client (no per-request cost).
- CORS is open — production should restrict that.

---

## 3. The diagnosis flow (the heart of the demo) — step by step

### Step 0 — start a session  →  `POST /diagnosis/start`
File → `app/routes/diagnosis.py:110` + `app/services/diagnosis_service.py:48`.
- The Laravel side calls this with the patient **baseline** (gender, age, smoker, diabetes, hypertension, activity_level, …).
- The engine builds a fresh `candidates` dict containing the state machine fields:

```python
candidates = {
    "phase": "diagnosis",          # current phase
    "baseline": {...},
    "model_name": model or default,
    "language": detect_lang(...),
    "selected_symptoms": [],
    "socrates_axis": 0,            # which of the 8 SOCRATES axes we ask next
    "probabilities": {},           # {disease: 0.x}  ← Bayes output
    "diseases": [],                # candidate disease metadata
    "conversation": [],            # LLM transcript (system/user/assistant)
    "question_count": 0,
    "current_symptom_index": -1,
    "current_question": None,
}
```
- It assigns a UUID `session_id`, writes it to Supabase via RPC `create_diagnosis_session`, and returns it. This id is what the mobile/web app keeps and sends forever after.

### Step 1 — search symptoms  →  `GET /symptoms?q=...&model_name=...`
File → `diagnosis.py:18`, backed by `diagnosis_service` vector search.
- Translates the query to English, embeds it with the E5 model (`encode_query("query: ...")`).
- Runs a `store.search` (pgvector cosine) to the top 20 medical document chunks.
- Sends the chunks + query to the LLM (`build_extract_prompt`) which returns JSON `{"results":[{"name_en","summary","type":"illness",...}]}`.
- Returns the cleaned, optionally localized, list.

> Point this out: this is the **LLM as an extractor** in front of a vector DB — fast + grounded.

### Step 2 — select the symptom  →  `POST /symptom/select`  (`name=<illness>&session_id=...`)
File → `diagnosis_service.py:99` and the special sentinel `name=="no"`.

- **Normal case**: it vector-searches *that disease name*, extracts up to 3 fresh diseases via the LLM, **initializes the Bayesian priors** (`compute_priors` in `bayesian.py`), and immediately returns the **first SOCRATES follow-up question** (Site axis).
- **Sentinel case**: if the patient sends `name=no` (no more symptoms), the engine adds `"Patient reports no more symptoms."` to the conversation, sets `no_more_symptoms=True`, and *then* resumes normal questioning instead of diagnosing early — keeps the session going.

### Step 3+ — answer SOCRATES questions  →  `POST /follow-up/answer`
This is the loop. The state machine advances a single axis each time.

Inside (`diagnosis_service.py:315-622`, function `submit_follow_up_answer`):
1. Read current `socrates_axis` (0=Site,1=Onset,2=Character,3=Radiation,4=Associated symptoms,5=Timing,6=Factors,7=Severity — defined in `socrates.py:13`).
2. Bayesian update: the previous assistant answer listed each candidate disease and per‑option probabilities; `bayes_update(priors, probs_per_option, options, answer)` reweights the disease probabilities.
3. **Dynamic question cap**: `per_symptom_cap` (see `question_builder.py:55`) — if uncertain (low top‑1 probability) we allow more questions, if confident we cap at 2 (`MIN_PER_SYMPTOM`).
4. **Hard stops that FORCE a diagnosis** (`diagnosis_service.py:392`):

```python
force = (force_diagnosis or question_count >= MAX_QUESTIONS or remaining <= MIN_PER_SYMPTOM)
```
- `MAX_QUESTIONS` = 25 (`bayesian.py:18`).
- `force_diagnosis` is a query/form param — **we exposed it recently** so the frontend can "Give me my results now" even mid‑loop.
5. If a `need_more_symptoms` gate triggers, we ask the patient to search another symptom instead.
6. Otherwise we prompt the LLM (`build_system_prompt(socrates_axis, …)` + full conversation) for the next question. The parser `parse_llm_response` recovers JSON even if the LLM wraps it in markdown.

Stopping thresholds (`bayesian.py`):
- `CONFIDENCE_THRESHOLD = 0.70` (one disease clear winner), or
- `TOP3_THRESHOLD = 0.85` (top‑3 collectively dominant).

### Step 4 — emit the diagnosis
Reached via `force` or `check_stopping`. `_finalize(...)`:
- asks the LLM once (`build_diagnosis_naming_prompt`) to name the top disease + specialist + advice,
- backfills probabilities with `force_top3`,
- writes the transcript back to Supabase and marks the conversation with `{"type":"diagnosis","diagnoses":[…]}`.

### Step 5 — PDF report  →  `POST /reports/{session_id}/download`
File → `app/routes/report.py:65`, `app/services/report_service.py`.
- **4 cache conditions**:
  1. not reviewed + cached file → serves it
  2. not reviewed + missing → generates via **Playwright** (headless Chromium renders a Jinja2 HTML template) and caches
  3. reviewed + cached → serves the reviewed final PDF
  4. reviewed + missing → regenerates with doctor‑override block and caches
- The doctor‑review override (name, specialization, phone, notes, timestamp) is merged in `generate_report_html`.

> Point out: report uses **Playwright** for "exact same look for patient & doctor."

---

## 4. Where the Bayesian magic lives (good talking point)

`app/services/bayesian.py`:
- `compute_priors` — reads the `likelihood / probability` line from the medical document and maps to a keyword table (`شائع شدد`…). Falls back to vector similarity.
- `bayes_update` — pure naive Bayes: `posterior ∝ prior × likelihood`.
- `check_stopping` — threshold logic shown above.
- `force_top3` — the fallback that turns `{disease: prob}` into the patient-facing dict (confidence label → Strong/Moderate/Less Likely).

The probabilities dict (e.g. `{"Common Cold": 0.42, …}`) is stored in `candidates["probabilities"]` and logged to the `BAYES` channel on every update.

---

## 5. Live demo script (copy, paste, run — 7 commands)

Assumes the engine is reachable at `http://localhost:5000` (change `BASE`).

```bash
BASE=http://localhost:5000
SID=$(curl -s -X POST "$BASE/diagnosis/start" \
  -F "user_id=demo-$(date +%s)" \
  -F "gender=female" -F "age=34" \
  -F "is_smoker=false" -F "activity_level=moderate" \
  | jq -r '.data.session_id')
echo "session_id=$SID"

# 1) search, pick fever
SYM=$(curl -s "$BASE/symptoms?q=fever" | jq -r '.data.results[0].name_en')
curl -s -X POST "$BASE/symptom/select" -F "session_id=$SID" -F "name=$SYM" | jq .
# → you'll get the first SOCRATES question

# 2) answer it 5 times fast (force_diagnosis lets us skip the full loop for the demo)
for a in "Yes" "Mild" "Gradual" "No" ; do
  curl -s -X POST "$BASE/follow-up/answer" \
    -F "session_id=$SID" -F "question_id=NA" -F "answer=$a" | jq .
done

# 3) force the diagnosis instantly
curl -s -X POST "$BASE/follow-up/answer" \
  -F "session_id=$SID" -F "question_id=NA" -F "answer=done" -F "force_diagnosis=true" | jq '.data'
# → response_type == "diagnosis", diagnoses=[ {disease_name, confidence, probability, specialist, advice} ]

# 4) build + download the PDF
curl -s -X POST "$BASE/reports/$SID/download?language_code=en" -o diagnosis-demo.pdf
file diagnosis-demo.pdf        # → PDF document, N pages
```

You’ll get a real `diagnoses` array and a PDF in one terminal scroll. That’s the whole product on one screen.

---

## 6. “Where the doctors come from” (hand-off to api-backend)

The engine never assigns a doctor — that line stays in Laravel:
- Stripe webhook `payment_intent.succeeded` → `PaymentService::handlePaymentSucceeded` → `assignDoctorAfterPayment` (`PaymentService.php:84`).
- Specialist is read from the diagnosis’ first entry (`ai_result[0]['specialist']`), fallback to `Disease.specialist`.
- `DoctorAssignmentService::assign` matches `Doctor.specialization LIKE %specialist%`, picks the **least-loaded** active doctor, and stores `doctor_id` on the session. If none match it falls back to *any* active doctor (this is where a random-looking doctor can get assigned — worth flagging).

---

## 7. Files the professors will ask about (TL;DR cheat sheet)

| Concern | Point them to |
|---|---|
| “How does it learn diagnoses?” | `app/services/pgvector_client.py` (vector DB) + `app/services/socrates.py` (prompts) |
| “Where’s the reasoning?” | `app/services/diagnosis_service.py` (the state machine) |
| “How do probabilities move?” | `app/services/bayesian.py` |
| “How is the PDF made?” | `app/services/report_service.py` + `generate_pdf` (Playwright) |
| “Where do multilingual answers come from?” | `app/services/i18n.py` (from_english / to_english) |
| “How does it persist?” | `app/services/session_manager.py` (Supabase RPC) — sessions live in Postgres |
| “Is there a diagram?” | `gen_diagram.php` → renders the Mermaid flow used across the system |

---

## 8. Gotchas we hit recently (and fixed)

- **`report_service.py`** crashed with `'NoneType' object has no attribute 'get'` when `overrides` was `null` — fixed by `overrides = overrides or {}`.
- **`/follow-up/answer`** did **not** expose `force_diagnosis` on the route — frontend could never short-circuit. **Fixed**: route accepts `force_diagnosis: bool = Form(False)` and the Laravel `DiagnosisAnswerRequest` rules validated `force_diagnosis` (it now flows end‑to‑end).
- **`previewReport`** referenced an undefined `$lang` — fixed by renaming to the param `$languageCode` (`AiService.php:528`).
- The Laravel backend also had a `blood_type` validation gap (string vs the MySQL enum) — aligned the rule with the enum values and normalized case on save.

---

## 9. How to run it yourself (dev)

```bash
python -m venv venv && venv\Scripts\activate      # Windows
source venv/bin/activate                          # mac/Linux
pip install -r requirements.txt
# copy .env.example -> .env, fill SUPABASE_URL, SUPABASE_KEY, GOOGLE/CLOUDFLARE keys
uvicorn main:app --host 0.0.0.0 --port 5000       # has --reload during dev
```
Swagger UI is at `http://localhost:5000/docs` — click‑test every endpoint directly.

---

End of tutorial. Use the demo script in Section 5 for the live run.

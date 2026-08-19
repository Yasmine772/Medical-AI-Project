import json
import re
import uuid

from app.services.logger import log
from app.services.bayesian import (
    compute_priors,
    bayes_update,
    check_stopping,
    force_top3,
    MAX_QUESTIONS,
    CONFIDENCE_THRESHOLD,
)
from app.services.socrates import (
    format_candidates,
    parse_llm_response,
    build_system_prompt,
    build_diagnosis_naming_prompt,
)
from app.services.prompts import (
    NO_MORE_SYMPTOMS_MSG,
    NEED_MORE_SYMPTOMS_MSG,
    DEFAULT_NEED_MORE_MSG,
    DISEASE_EXTRACTION_SYSTEM_MSG,
    build_related_diseases_prompt,
    build_disease_names_prompt,
)

from app.services.i18n import (
    detect_lang,
    to_english,
    from_english,
    translate_list,
)
from app.services.question_builder import (
    MIN_PER_SYMPTOM,
    per_symptom_cap,
    short_probs,
    to_question_options,
    infer_qtype,
    build_fallback_question,
)

MIN_QUESTIONS_BEFORE_DIAGNOSIS = 6
MAX_TOTAL_DISEASES = 15
MAX_NEW_DISEASES = 3


# ── State-machine overview ──────────────────────────────────
# A diagnosis session lives in `candidates` (JSON column held in the
# diagnosis_sessions table) and walks these phases / counters:
#   phase == "diagnosis"          -> SOCRATES question loop active
#   phase == "completed"          -> final diagnosis emitted & report ready
#
#   socrates_axis                 -> index into the 8 SOCRATES axes
#                                    (Site, Onset, Character, Radiation,
#                                     Associated symptoms, Timing, Factors,
#                                     Severity)  — see socrates.py
#   selected_symptoms             -> list of chosen symptoms driving the priors
#   probabilities                 -> {disease: 0..1} Bayesian posterior
#   question_count                -> total assistant Qs asked this session
#
# `submit_follow_up_answer` advances exactly one axis per call and decides, via
# `check_stopping` + the question budget, whether to diagnose now or ask next.


class DiagnosisService:

    def __init__(self, store, embedder, session_mgr, llm):
        self.store = store
        self.embedder = embedder
        self.session_mgr = session_mgr
        self.llm = llm

    # ── Session management ──

    def create_session(
        self,
        baseline: dict | None = None,
        user_id: str = "anonymous",
        model_name: str = None,
    ) -> str:
        lang = detect_lang(json.dumps(baseline)) if baseline else "en"
        log("MODEL", f"create_session received model_name={model_name!r}")
        candidates = {
            "phase": "diagnosis",
            "baseline": baseline or {},
            "model_name": model_name if model_name else None,
            "language": lang,
            "selected_symptoms": [],
            "socrates_axis": 0,
            "probabilities": {},
            "diseases": [],
            "conversation": [],
            "question_count": 0,
            "questions_on_current": 0,
            "current_symptom_index": -1,
            "current_question": None,
        }
        session_id = str(uuid.uuid4())
        self.session_mgr._rpc(
            "create_diagnosis_session",
            {
                "p_id": session_id,
                "p_user_id": user_id,
                "p_initial_symptoms": "diagnosis_session",
                "p_candidates": json.dumps(candidates),
            },
        )
        log("SESSION", f"Created session {session_id[:8]} user={user_id} lang={lang}")
        return session_id

    # ── Symptom selection (replaces old collection sub-loop) ──

    def select_symptom(self, session_id: str, result: dict) -> dict:
        session = self._get_session(session_id)
        candidates = session.get("candidates", {})
        if candidates.get("phase") != "diagnosis":
            return {
                "error": "Session is not in diagnosis phase",
                "phase": candidates.get("phase"),
            }

        lang = candidates.get("language", "en")

        name_en = result.get("name_en") or result.get("name") or "unknown"

        # Special sentinel "no" = the patient has no more NEW symptoms to add.
        # Handled with a STATIC set of "no" tokens across languages — no
        # translation call, so it works whatever language the client uses.
        _NO_TOKENS = {
            "no", "nope", "nop", "nah", "nay",
            "لا", "non", "nein", "nee", "não", "nao",
            "нет", "hayır", "نه", "نہیں", "tidak",
            "不", "否", "いいえ", "아니", "नहीं",
        }

        def _is_no(val):
            return (val or "").strip().lower() in _NO_TOKENS

        if _is_no(name_en) or _is_no(result.get("name_local")):
            log("SELECT", f"No more symptoms (sentinel 'no') session={session_id[:8]}")
            conversation = candidates.get("conversation", [])
            # Let the LLM know the patient has nothing else to add, so it
            # continues with questions about existing symptoms (or diagnoses)
            # instead of asking for yet another symptom.
            if not any(
                m.get("role") == "user"
                and "no more symptoms" in (m.get("content") or "").lower()
                for m in conversation
            ):
                conversation.append(
                    {"role": "user", "content": NO_MORE_SYMPTOMS_MSG}
                )
            candidates["conversation"] = conversation
            candidates["current_question"] = None
            candidates["no_more_symptoms"] = True
            self._save_candidates(session_id, candidates)
            return self._ask_next(session_id, candidates)
        name_local = from_english(name_en, lang) if lang != "en" else name_en
        query_text = result.get("search_query") or name_en
        snippet = result.get("snippet") or result.get("document") or ""

        # Detect + store the user's input language, translate the query to English
        if not candidates.get("language_set"):
            detected = detect_lang(name_en)
            if detected != "en":
                candidates["language"] = detected
                lang = detected
            candidates["language_set"] = True
        query_text = to_english(query_text)

        log("SELECT", f"Symptom selected '{name_en}' session={session_id[:8]}")

        model_name = candidates.get("model_name")

        # Vector search anchored on the selected result, biased by patient priors
        prior_q = self._prior_query(candidates.get("baseline"))
        search_query = f"{prior_q} | {query_text}" if prior_q else query_text
        query_vector = self.embedder.encode_query(search_query)
        vector_results = self.store.search(query_vector, limit=10, filter_type=None)
        log(
            "VECTOR",
            f"'{name_en}' -> {len(vector_results)} results",
            [r.get("name_en") for r in vector_results],
        )

        if not vector_results:
            return {"error": "No matching diseases found for this symptom"}

        selected_entry = {
            "query": query_text,
            "name_en": name_en,
            "name_local": name_local,
            "snippet": snippet,
        }
        candidates.setdefault("selected_symptoms", []).append(selected_entry)
        candidates["current_symptom_index"] = len(candidates["selected_symptoms"]) - 1
        candidates["questions_on_current"] = 0
        candidates["need_more_count"] = 0

        # Extract disease names from PDF chunks via LLM
        results = []
        existing_names = {d.get("name_en") for d in candidates.get("diseases", [])}

        pdf_texts = []
        for r in vector_results[:5]:
            doc = (r.get("document") or "").strip()[:500]
            ne = (r.get("name_en") or "").strip()
            if doc and not any("\u0600" <= c <= "\u06ff" for c in doc):
                pdf_texts.append(f"[PASSAGE]\n{doc}")
            elif ne:
                pdf_texts.append(f"[PASSAGE]\nName: {ne}")

        if pdf_texts:
            context = "\n\n".join(pdf_texts)
            extract_prompt = build_related_diseases_prompt(name_en, context)
            try:
                raw = self.llm.ask(
                    [
                        {
                            "role": "system",
                            "content": DISEASE_EXTRACTION_SYSTEM_MSG,
                        },
                        {"role": "user", "content": extract_prompt},
                    ],
                    temperature=0,
                    max_tokens=512,
                    model=model_name,
                )
                start = raw.find("{")
                end = raw.rfind("}")
                parsed = {}
                if start != -1 and end != -1:
                    parsed = json.loads(raw[start : end + 1])
                items = parsed.get("results", []) if isinstance(parsed, dict) else []
                for it in items:
                    disease_name = (it.get("name_en") or "").strip()
                    if disease_name and disease_name not in existing_names:
                        results.append(
                            {
                                "name_en": disease_name,
                                "name_local": (
                                    from_english(disease_name, lang)
                                    if lang != "en"
                                    else disease_name
                                ),
                                "symptoms_en": (it.get("summary") or "").strip()[:500],
                                "specialist": (
                                    it.get("specialist") or "General"
                                ).strip(),
                                "similarity": 0.5,
                            }
                        )
                        existing_names.add(disease_name)
                log("SELECT", f"LLM extracted {len(items)} disease names from PDFs")
            except Exception as e:
                log("SELECT", f"LLM extraction failed: {str(e)[:60]}")

        # Grounding filter: keep only diseases whose description actually
        # mentions the selected symptom, so unrelated conditions (e.g. malaria
        # for a headache report) are not injected into the candidate pool.
        symptom_kw = (name_en or "").strip().lower()
        if symptom_kw:
            matched = [
                r
                for r in results
                if symptom_kw
                in ((r.get("symptoms_en") or "") + " " + (r.get("name_en") or "")).lower()
            ]
            if matched:
                results = matched

        if not results:
            results = [
                {
                    "name_en": name_en,
                    "name_local": name_local,
                    "symptoms_en": snippet[:500],
                    "specialist": "General",
                    "similarity": 0.5,
                }
            ]

        # First selection initializes; later selections accumulate evidence
        priors = compute_priors(results)
        if not candidates.get("probabilities"):
            candidates["probabilities"] = priors
            candidates["diseases"] = results
            log("BAYES", f"Priors initialized: {short_probs(priors)}")
        else:
            existing_names = {d.get("name_en") for d in candidates.get("diseases", [])}
            for r in results:
                name = r.get("name_en")
                if name and name not in existing_names:
                    candidates["diseases"].append(r)
                    candidates["probabilities"][name] = priors.get(name, 0.01)
                    existing_names.add(name)
            total = sum(candidates["probabilities"].values()) or 1
            for k in candidates["probabilities"]:
                candidates["probabilities"][k] /= total
            log(
                "BAYES",
                f"Priors merged (symptom added): {short_probs(candidates['probabilities'])}",
            )

        self._save_candidates(session_id, candidates)

        # Produce the first question via the follow-up engine
        return self._ask_next(session_id, candidates, initial_msg=query_text)

    # ── Follow-up loop (SOCRATES + Bayes) ──

    def get_current_question(self, session_id: str) -> dict:
        session = self._get_session(session_id)
        candidates = session.get("candidates", {})
        lang = candidates.get("language", "en")
        if candidates.get("phase") != "diagnosis":
            return {"response_type": "unknown", "question": None}

        # If a diagnosis was already produced, return it (last thing in the session).
        conversation = candidates.get("conversation", [])
        for m in reversed(conversation):
            if m.get("role") != "assistant":
                continue
            parsed = parse_llm_response(m.get("content", ""))
            if parsed.get("type") == "diagnosis" or parsed.get("diagnoses"):
                diags = parsed.get("diagnoses", [])
                if not any(d.get("disease_name_local") for d in diags):
                    diags = self._localize_diagnoses(diags, lang)
                return {
                    "response_type": "diagnosis",
                    "diagnosis_summary": {"diagnoses": diags},
                    "symptoms": self._get_symptoms(candidates, lang),
                    "total": MAX_QUESTIONS,
                }

        q = candidates.get("current_question")
        if isinstance(q, str):
            try:
                q = json.loads(q)
            except Exception:
                q = None
        if not q:
            return {"response_type": "unknown", "question": None}
        if q.get("type") == "need_more":
            msg = q.get("question") or DEFAULT_NEED_MORE_MSG
            return {
                "response_type": "need_more_symptoms",
                "question": {
                    "id": "need_more",
                    "text": msg,
                    "type": "info",
                    "options": [],
                },
                "total": MAX_QUESTIONS,
            }
        return {
            "response_type": "question",
            "question": self._format_question(q, lang),
            "total": MAX_QUESTIONS,
        }

    def submit_follow_up_answer(
        self, session_id: str, question_id: str, answer: str, force_diagnosis: bool = False
    ) -> dict:
        session = self._get_session(session_id)
        candidates = session.get("candidates", {})
        if candidates.get("phase") != "diagnosis":
            return {
                "error": "Session is not in diagnosis phase",
                "phase": candidates.get("phase"),
            }
        if session.get("status") == "completed":
            return {"error": "Session already completed"}

        lang = candidates.get("language", "en")
        conversation = candidates.get("conversation", [])
        diseases = candidates.get("diseases", [])
        probabilities = candidates.get("probabilities", {})
        socrates_axis = candidates.get("socrates_axis", 0)
        model_name = candidates.get("model_name")
        log("MODEL", f"submit_follow_up model_name={model_name!r}")

        # Translate the user's answer to English for LLM/Bayes processing
        answer_en = to_english(answer)

        question_count = sum(1 for m in conversation if m.get("role") == "assistant")
        log(
            "FOLLOWUP",
            f"Answer q{question_count+1}/{MAX_QUESTIONS} session={session_id[:8]} answer='{answer[:60]}' axis={socrates_axis}",
        )

        # Bayesian update from the previous LLM response
        last_assistant = None
        for m in reversed(conversation):
            if m.get("role") == "assistant":
                last_assistant = m
                break
        if last_assistant:
            prev = parse_llm_response(last_assistant.get("content", ""))
            probs_per_option = prev.get("probs_per_option", {})
            options = prev.get("options", [])
            if probs_per_option and options and probabilities:
                matched_idx = self._match_answer_index(
                    answer, answer_en, options, lang
                )
                if matched_idx is None:
                    log(
                        "BAYES",
                        f"No option matched answer '{answer[:40]}'; skipping update",
                    )
                else:
                    matched_option = options[matched_idx]
                    old_top = max(probabilities.items(), key=lambda x: x[1])
                    probabilities = bayes_update(
                        probabilities, probs_per_option, options, matched_option
                    )
                    new_top = max(probabilities.items(), key=lambda x: x[1])
                    log(
                        "BAYES",
                        f"Updated (matched '{matched_option}'): "
                        f"{old_top[0]}={old_top[1]:.2f} -> {new_top[0]}={new_top[1]:.2f}",
                    )

        # Dynamic re-search every 3 axes
        if socrates_axis >= 3 and socrates_axis % 3 == 0:
            log("VECTOR", f"Re-search at axis {socrates_axis}")
            probabilities = self._re_search(
                conversation, diseases, probabilities, lang, candidates, model_name=model_name
            )

        questions_on_current = candidates.get("questions_on_current", 0) + 1
        candidates["questions_on_current"] = questions_on_current
        question_count = question_count + 1
        candidates["question_count"] = question_count

        remaining = MAX_QUESTIONS - question_count
        top1 = max(probabilities.items(), key=lambda x: x[1])[1] if probabilities else 0
        sorted_p = sorted(probabilities.items(), key=lambda x: -x[1])
        top3_sum = sum(p for _, p in sorted_p[:3])

        # Convergence / info-gain aware stopping: stop when confident OR when the
        # top probability has barely moved for two consecutive questions (we are no
        # longer gaining useful information), once the minimum number of questions
        # has been asked. This avoids both dragging and cutting off too early.
        prev_top1 = candidates.get("prev_top1")
        stable = candidates.get("stable_count", 0)
        if prev_top1 is not None and abs(top1 - prev_top1) < 0.03:
            stable += 1
        else:
            stable = 0
        candidates["prev_top1"] = top1
        candidates["stable_count"] = stable
        converged = stable >= 2
        can_stop = check_stopping(probabilities, socrates_axis) or (
            question_count >= MIN_QUESTIONS_BEFORE_DIAGNOSIS
            and (top1 >= CONFIDENCE_THRESHOLD or top3_sum >= TOP3_THRESHOLD or converged)
        )
        symptom_cap = per_symptom_cap(top1, remaining, MIN_PER_SYMPTOM)
        log(
            "CAP",
            f"question_count={question_count} remaining={remaining} top1={top1:.2f} per_symptom_cap={symptom_cap} questions_on_current={questions_on_current} stop={can_stop} converged={converged}",
        )

        # Hard stops where we MUST diagnose (can't afford more questions):
        # out of question budget entirely, or too few questions left to ask
        # about another symptom meaningfully. Also force when the frontend
        # explicitly requests a diagnosis (force_diagnosis=True).
        force = (
            force_diagnosis
            or question_count >= MAX_QUESTIONS
            or remaining <= MIN_PER_SYMPTOM
            # Only auto-finalize on confidence/convergence once the patient has
            # explicitly said there are NO more symptoms. Otherwise we let the
            # "add another symptom" gate run so the engine gathers a fuller
            # picture (multiple symptoms) instead of diagnosing a single symptom
            # after just a few questions.
            or (can_stop and bool(candidates.get("no_more_symptoms")))
        )
        if force:
            log(
                "FOLLOWUP",
                f"Forcing diagnosis: count={question_count}/{MAX_QUESTIONS} remaining={remaining}",
            )

        conversation.append({"role": "user", "content": answer_en})
        self._save_candidates(session_id, candidates)

        if force:
            return self._finalize(
                session_id,
                candidates,
                conversation,
                probabilities,
                diseases,
                lang,
                forced=True,
            )

        # Exhausted the question cap for this symptom: ask the user to search
        # another symptom instead of diagnosing or re-asking the LLM about the
        # same one (which produced repetitive questions). This check deliberately
        # wins over the LLM returning a diagnosis early — UNLESS the patient
        # already said "no more symptoms", in which case we let the LLM continue
        # questioning until it is confident enough to diagnose.
        gated = (
            question_count >= MIN_QUESTIONS_BEFORE_DIAGNOSIS
            and questions_on_current >= symptom_cap
            and not candidates.get("no_more_symptoms")
        )
        # Stateless guard: only show the "add another symptom" prompt ONCE. We count
        # how many times we've already emitted it by scanning the conversation, so
        # the check is deterministic regardless of persisted session state. If the
        # patient keeps answering instead of adding a symptom, the gate stops
        # nagging and we fall through to the normal LLM questioning below.
        prior_need_more = sum(
            1
            for m in conversation
            if m.get("role") == "assistant"
            and "need_more_symptoms" in (m.get("content") or "")
        )
        if gated and prior_need_more < 1:
            log(
                "FOLLOWUP",
                f"Asking for more symptoms at q{question_count}/{MAX_QUESTIONS} (cap={symptom_cap})",
            )
            # Persist the need_more prompt in the conversation so the stateless
            # guard above detects it on the next turn (prevents an endless loop).
            conversation.append({
                "role": "assistant",
                "content": json.dumps({
                    "type": "need_more_symptoms",
                    "message": NEED_MORE_SYMPTOMS_MSG,
                }),
            })
            candidates["conversation"] = conversation
            msg = from_english(
                NEED_MORE_SYMPTOMS_MSG,
                lang,
            )
            candidates["current_question"] = {
                "type": "need_more",
                "question": msg,
                "options": [],
                "question_id": "need_more",
            }
            self._save_candidates(session_id, candidates)
            return {
                "response_type": "need_more_symptoms",
                "question": {
                    "id": "need_more",
                    "text": msg,
                    "type": "info",
                    "options": [],
                },
                "total": MAX_QUESTIONS,
            }

        # Build prompt and ask
        diseases_text = (
            format_candidates(diseases) if diseases else "No matching diseases found."
        )
        probs_text = (
            "\n".join(
                f"  {k}: {v*100:.0f}%"
                for k, v in sorted(probabilities.items(), key=lambda x: -x[1])
            )
            if probabilities
            else ""
        )

        baseline = candidates.get("baseline", {})
        summary_text, _ = self._patient_facts(candidates, conversation)
        system_prompt = build_system_prompt(
            diseases_text,
            socrates_axis,
            probs_text,
            language=lang,
            force=force_diagnosis,
            baseline=baseline,
            asked_questions=self._extract_asked_questions(conversation),
            no_more_symptoms=bool(candidates.get("no_more_symptoms")),
            symptoms_text=", ".join(self._get_symptoms(candidates, "en")),
            summary_text=summary_text,
        )
        messages = [{"role": "system", "content": system_prompt}, *conversation]
        content = self.llm.ask(messages, model=model_name)
        parsed = parse_llm_response(content)
        log(
            "LLM",
            f"Groq response type={parsed.get('type')} q{question_count}/{MAX_QUESTIONS}",
        )

        if parsed.get("type") == "error":
            log("LLM", "Parse error from model, using local fallback question")
            parsed = build_fallback_question(socrates_axis)
            content = json.dumps(parsed, ensure_ascii=False)

        llm_type = parsed.get("type")

        # Issue 3: if the LLM repeats a question it already asked, replace it with
        # a fresh fallback question on the current SOCRATES axis instead of looping.
        asked_qs = self._extract_asked_questions(conversation)
        new_q = parsed.get("question", "")
        if llm_type == "question" and new_q and self._is_repeated_question(new_q, asked_qs):
            log(
                "LLM",
                f"Repeated question detected q{question_count + 1}, using fallback",
            )
            parsed = build_fallback_question(socrates_axis)
            content = json.dumps(parsed, ensure_ascii=False)
            llm_type = parsed.get("type")

        # LLM wants more symptoms
        if llm_type == "need_more_symptoms":
            # Guard against the LLM asking for "more symptoms" on repeat: if we've
            # already asked once, ignore it and keep the SOCRATES loop going so the
            # session never stalls on an endless "add a symptom" loop.
            if prior_need_more < 1:
                conversation.append({"role": "assistant", "content": content})
                candidates["socrates_axis"] = socrates_axis + 1
                candidates["conversation"] = conversation
                msg = from_english(
                    parsed.get("message", DEFAULT_NEED_MORE_MSG), lang
                )
                candidates["current_question"] = {
                    "type": "need_more",
                    "question": msg,
                    "options": [],
                    "question_id": "need_more",
                }
                self._save_candidates(session_id, candidates)
                return {
                    "response_type": "need_more_symptoms",
                    "question": {
                        "id": "need_more",
                        "text": msg,
                        "type": "info",
                        "options": [],
                    },
                    "total": MAX_QUESTIONS,
                }
            # Otherwise fall through and treat the response as a normal question.

        # LLM decided diagnosis
        if (
            llm_type == "diagnosis"
            or parsed.get("diagnoses")
            or parsed.get("diagnosis")
        ):
            if question_count < MIN_QUESTIONS_BEFORE_DIAGNOSIS:
                log(
                    "LLM",
                    f"Premature diagnosis rejected at q{question_count}/{MIN_QUESTIONS_BEFORE_DIAGNOSIS}, using fallback",
                )
                parsed = build_fallback_question(socrates_axis)
                content = json.dumps(parsed, ensure_ascii=False)
                llm_type = parsed.get("type")
            else:
                conversation.append({"role": "assistant", "content": content})
                candidates["socrates_axis"] = socrates_axis + 1
                candidates["conversation"] = conversation
                candidates["current_question"] = None
                self._save_candidates(session_id, candidates)
                return self._finalize(
                    session_id,
                    candidates,
                    conversation,
                    probabilities,
                    diseases,
                    lang,
                    parsed_override=parsed,
                    forced=False,
                )

        # Issues 4/5: the patient explicitly reported no more symptoms. Once we
        # have gathered the minimum number of questions, stop asking altogether
        # (this also prevents "rate this symptom" prompts for a non-existent
        # symptom) and finalize the diagnosis.
        if (
            candidates.get("no_more_symptoms")
            and question_count >= MIN_QUESTIONS_BEFORE_DIAGNOSIS
        ):
            log(
                "LLM",
                f"No-more-symptoms guard: finalizing at q{question_count}",
            )
            conversation.append({"role": "assistant", "content": content})
            candidates["conversation"] = conversation
            candidates["current_question"] = None
            self._save_candidates(session_id, candidates)
            return self._finalize(
                session_id,
                candidates,
                conversation,
                probabilities,
                diseases,
                lang,
                forced=True,
            )

        # Question quality is now the responsibility of the reasoning model (70B),
        # guided by the SOCRATES + reasoning rules in the prompt. No hardcoded guards.
        q_index = question_count + 1
        if (
            not isinstance(parsed.get("question"), str)
            or not parsed.get("question").strip()
        ):
            log(
                "LLM",
                f"Empty question rejected q{question_count+1}/{MAX_QUESTIONS}, using fallback",
            )
            parsed = build_fallback_question(socrates_axis)
            content = json.dumps(parsed, ensure_ascii=False)
        self._absorb_new_symptoms(candidates, parsed)
        parsed = self._tag_question(session_id, parsed, q_index)
        conversation.append({"role": "assistant", "content": content})
        candidates["socrates_axis"] = socrates_axis + 1
        candidates["probabilities"] = probabilities
        candidates["diseases"] = diseases
        candidates["conversation"] = conversation
        candidates["current_question"] = parsed
        self._save_candidates(session_id, candidates)

        return {
            "response_type": "question",
            "question": self._format_question(parsed, lang),
            "total": MAX_QUESTIONS,
        }

    # ── Internal: ask the first question after a symptom selection ──

    def _ask_next(
        self, session_id: str, candidates: dict, initial_msg: str = None
    ) -> dict:
        lang = candidates.get("language", "en")
        diseases = candidates.get("diseases", [])
        probabilities = candidates.get("probabilities", {})
        socrates_axis = candidates.get("socrates_axis", 0)
        question_count = candidates.get("question_count", 0)
        conversation = candidates.get("conversation", [])
        model_name = candidates.get("model_name")
        log("MODEL", f"_ask_next model_name={model_name!r}")

        diseases_text = (
            format_candidates(diseases) if diseases else "No matching diseases found."
        )
        probs_text = (
            "\n".join(
                f"  {k}: {v*100:.0f}%"
                for k, v in sorted(probabilities.items(), key=lambda x: -x[1])
            )
            if probabilities
            else ""
        )

        baseline = candidates.get("baseline", {})
        summary_text, _ = self._patient_facts(candidates, conversation)
        system_prompt = build_system_prompt(
            diseases_text,
            socrates_axis,
            probs_text,
            language=lang,
            force=False,
            baseline=baseline,
            asked_questions=self._extract_asked_questions(conversation),
            no_more_symptoms=bool(candidates.get("no_more_symptoms")),
            symptoms_text=", ".join(self._get_symptoms(candidates, "en")),
            summary_text=summary_text,
        )
        if initial_msg and not conversation:
            conversation = [
                {"role": "user", "content": f"Patient reports: {initial_msg}"}
            ]

        messages = [{"role": "system", "content": system_prompt}, *conversation]
        content = self.llm.ask(messages, model=model_name)
        parsed = parse_llm_response(content)
        log("LLM", f"First question type={parsed.get('type')} session={session_id[:8]}")

        if parsed.get("type") == "error":
            log("LLM", "Parse error from model, using local fallback question")
            parsed = build_fallback_question(socrates_axis)
            content = json.dumps(parsed, ensure_ascii=False)

        # Question quality is now the responsibility of the reasoning model (70B),
        # guided by the SOCRATES + reasoning rules in the prompt. No hardcoded guards.
        llm_type = parsed.get("type")
        if (
            llm_type == "diagnosis"
            or parsed.get("diagnoses")
            or parsed.get("diagnosis")
        ):
            if question_count < MIN_QUESTIONS_BEFORE_DIAGNOSIS:
                log(
                    "LLM",
                    f"Premature diagnosis rejected in _ask_next at q{question_count}/{MIN_QUESTIONS_BEFORE_DIAGNOSIS}, using fallback",
                )
                parsed = build_fallback_question(socrates_axis)
                content = json.dumps(parsed, ensure_ascii=False)
                llm_type = parsed.get("type")
            else:
                conversation.append({"role": "assistant", "content": content})
                candidates["conversation"] = conversation
                candidates["current_question"] = None
                self._save_candidates(session_id, candidates)
                return self._finalize(
                    session_id,
                    candidates,
                    conversation,
                    probabilities,
                    diseases,
                    lang,
                    parsed_override=parsed,
                    forced=False,
                )

        # Remaining questions after the first count from socrates_axis
        q_index = candidates.get("question_count", 0) + 1
        if (
            not isinstance(parsed.get("question"), str)
            or not parsed.get("question").strip()
        ):
            log("LLM", "Empty first question rejected, using fallback")
            parsed = build_fallback_question(socrates_axis)
            content = json.dumps(parsed, ensure_ascii=False)
        self._absorb_new_symptoms(candidates, parsed)
        parsed = self._tag_question(session_id, parsed, q_index)
        conversation.append({"role": "assistant", "content": content})
        candidates["socrates_axis"] = socrates_axis + 1
        candidates["conversation"] = conversation
        candidates["current_question"] = parsed
        self._save_candidates(session_id, candidates)

        return {
            "response_type": "question",
            "question": self._format_question(parsed, lang),
            "total": MAX_QUESTIONS,
        }

    # ── Finalize diagnosis ──

    def _ground_probabilities_by_symptoms(
        self, probabilities: dict, diseases: list, candidates: dict
    ) -> dict:
        """Re-weight disease probabilities by how well each disease's documented
        symptoms overlap the patient's REPORTED symptoms.

        The Bayesian ``probabilities`` are seeded from retrieval popularity
        (how common a disease's source text is in the KB), which lets a
        retrieval-strong disease (e.g. Influenza) dominate even when it has
        nothing to do with the patient. This blends in a symptom-fit score so
        the actual reported picture drives the differential.

        Returns a normalized ``{disease: probability}`` dict.
        """
        # Collect the patient's REPORTED symptoms in ENGLISH (name_en), so we can
        # match them against the English symptom text of candidate diseases.
        # Must read name_en directly: the lang-aware _get_symptoms() returns the
        # localized (e.g. Arabic) name for a non-English patient, which never
        # matches the English disease text and silently disables grounding.
        # Absorbed answer-revealed symptoms live in selected_symptoms too.
        patient_syms = []
        for s in candidates.get("selected_symptoms", []) or []:
            ne = (s.get("name_en") or s.get("name_local") or "").strip()
            if ne:
                patient_syms.append(ne)
        tokens = set()
        for s in patient_syms:
            for w in re.split(r"\W+", s.lower()):
                if len(w) >= 3:
                    tokens.add(w)
        if not tokens:
            return probabilities  # nothing reported to ground on

        # Per-disease symptom-fit in [0, 1].
        fit = {}
        for d in diseases or []:
            name = d.get("name_en") or ""
            syms = d.get("symptoms_en") or ""
            doc = d.get("document") or ""
            text = ((syms if syms else doc) + " " + name).lower()
            if not text.strip():
                fit[name] = None  # no symptom info -> keep prior
                continue
            matched = sum(1 for t in tokens if t in text)
            fit[name] = min(1.0, matched / max(1, len(tokens)))

        w = 0.6  # weight given to symptom fit vs retrieval prior
        out = {}
        for name, p in probabilities.items():
            f = fit.get(name)
            if f is None:
                out[name] = p
            else:
                out[name] = (1 - w) * p + w * f
        total = sum(out.values()) or 1.0
        return {k: v / total for k, v in out.items()}

    def _finalize(
        self,
        session_id,
        candidates,
        conversation,
        probabilities,
        diseases,
        lang,
        parsed_override=None,
        forced=False,
    ) -> dict:
        labels = candidates.get("id_labels", {})
        model_name = candidates.get("model_name")

        # Ground the probabilities in the patient's REPORTED symptoms so a
        # retrieval-popular disease can't dominate an unrelated picture.
        probabilities = self._ground_probabilities_by_symptoms(
            probabilities, diseases, candidates
        )

        # The Bayesian posterior is the source of truth for WHICH diseases rank
        # top-3 and their probabilities. The LLM may only contribute names,
        # specialists, and advice; its self-reported probabilities are ignored
        # (they tend to be flat/wrong, e.g. everything at 0.01).
        #
        # The selected symptom is tracked as a candidate (to anchor the prior),
        # but a symptom is NOT a diagnosis. Drop it from the posterior before
        # picking the top-3 so we never answer "chest pain" when the patient
        # reported chest pain. The remaining probabilities are renormalized so
        # the real diseases get a fair share of the mass.
        symptom_names = set()
        for s in candidates.get("selected_symptoms", []) or []:
            for key in ("name_en", "name_local"):
                v = (s.get(key) or "").strip().lower()
                if v:
                    symptom_names.add(v)
        posterior = dict(probabilities)
        if symptom_names:
            for name in list(posterior):
                if name.strip().lower() in symptom_names:
                    posterior.pop(name, None)
        total = sum(posterior.values()) or 1
        if total != 1:
            for k in posterior:
                posterior[k] /= total

        backbone = force_top3(posterior, diseases, labels)

        # Gather LLM-named diagnoses (either from the loop override or the
        # dedicated naming call) to enrich the backbone with names/advice.
        named = []
        if parsed_override and (
            parsed_override.get("diagnoses") or parsed_override.get("diagnosis")
        ):
            if parsed_override.get("diagnosis") and not parsed_override.get("diagnoses"):
                diag = parsed_override.get("diagnosis")
                named = (
                    diag.get("top_3")
                    if isinstance(diag, dict) and "top_3" in diag
                    else []
                )
            else:
                named = parsed_override.get("diagnoses") or []
        if not named:
            summary_text, _ = self._patient_facts(candidates, conversation)
            named = self._name_diagnoses(
                probabilities, diseases, lang, model_name=model_name,
                baseline=candidates.get("baseline"),
                summary_text=summary_text,
            )

        # Normalize any LLM-supplied probabilities to a 0-1 fraction. The LLM
        # routinely emits percent-scale values (e.g. 80 meaning 80%) instead of
        # 0.8, which the template turns into 8000%. This protects every path
        # (loop override OR dedicated naming call) from absurd percentages.
        for d in named:
            p = d.get("probability")
            if isinstance(p, (int, float)):
                d["probability"] = p / 100.0 if p > 1 else float(p)
            else:
                d["probability"] = 0.0

        diagnoses = self._merge_diagnoses(backbone, named)
        parsed = {"type": "diagnosis", "diagnoses": diagnoses}

        parsed["type"] = "diagnosis"
        parsed["diagnoses"] = self._localize_diagnoses(parsed["diagnoses"], lang)
        conversation.append({"role": "assistant", "content": json.dumps(parsed)})
        candidates["conversation"] = conversation
        candidates["current_question"] = None
        self._save_candidates(session_id, candidates)
        self.session_mgr.update_conversation(
            session_id, conversation, status="completed"
        )

        return {
            "response_type": "diagnosis",
            "diagnosis_summary": {
                "diagnoses": parsed["diagnoses"],
            },
            "symptoms": self._get_symptoms(candidates, lang),
            "conversation": conversation,
            "total": MAX_QUESTIONS,
        }

    def _merge_diagnoses(self, backbone: list, named: list) -> list:
        """Overlay LLM-supplied names/specialists/advice onto the Bayesian top-3.

        The Bayesian ``backbone`` (from :func:`force_top3`) decides which
        diseases appear and at what probability/confidence. The LLM ``named``
        list is matched by name and its ``specialist``/``advice`` are copied
        over when available. The returned dicts keep exactly the same shape as
        before, so the response format is unchanged.
        """
        def norm(s: str) -> str:
            return (s or "").strip().lower()

        named_by_name = {}
        for d in named or []:
            key = norm(d.get("disease_name") or d.get("name_en"))
            if key:
                named_by_name[key] = d

        merged = []
        for entry in backbone or []:
            key = norm(entry.get("disease_name"))
            named_entry = named_by_name.get(key)
            merged.append({
                "disease_name": entry.get("disease_name") or (named_entry or {}).get("disease_name") or "",
                "probability": entry.get("probability"),
                "confidence": entry.get("confidence"),
                "specialist": (named_entry or {}).get("specialist") or entry.get("specialist") or "",
                "advice": (named_entry or {}).get("advice") or entry.get("advice") or "",
            })

        # If the posterior was empty (no evidence yet), fall back to the LLM names.
        if not merged:
            raw = []
            for d in (named or [])[:3]:
                p = d.get("probability")
                # LLMs routinely emit percent-scale values (e.g. 80 meaning 80%)
                # instead of 0-1 fractions. Convert anything > 1 to a fraction so
                # the downstream template (which multiplies by 100) shows 80%,
                # not 8000%.
                if isinstance(p, (int, float)):
                    p = p / 100.0 if p > 1 else float(p)
                else:
                    p = 0.0
                raw.append({
                    "disease_name": d.get("disease_name") or d.get("name_en") or "",
                    "probability": p,
                    "confidence": d.get("confidence") or "Less Likely",
                    "specialist": d.get("specialist") or "",
                    "advice": d.get("advice") or "",
                })
            # Renormalize so the displayed percentages sum to ~100%.
            total = sum(d["probability"] for d in raw) or 1.0
            for d in raw:
                d["probability"] = round(d["probability"] / total, 2)
            merged = raw

        # Final safety net: clamp every probability into a valid 0-1 fraction.
        # Guards against any path that emitted percent-scale values so the UI
        # never renders >100% (e.g. 33000%).
        for d in merged:
            p = d.get("probability")
            if isinstance(p, (int, float)):
                p = p / 100.0 if p > 1 else float(p)
                d["probability"] = round(max(0.0, min(1.0, p)), 2)
            else:
                d["probability"] = 0.0

        # No single diagnosis may claim 100%: a differential always carries some
        # uncertainty. Cap the top probability and renormalize the rest so the
        # engine can never report a literally certain (and often wrong) disease.
        MAX_SINGLE = 0.95
        top_p = max((d.get("probability") or 0) for d in merged)
        if top_p > MAX_SINGLE:
            for d in merged:
                d["probability"] = round(min(d["probability"], MAX_SINGLE), 2)
            total = sum(d["probability"] for d in merged) or 1.0
            for d in merged:
                d["probability"] = round(d["probability"] / total, 2)
        return merged

    def _name_diagnoses(
        self, probabilities, diseases, lang, model_name: str = None, baseline: dict = None, summary_text: str = ""
    ) -> list:
        try:
            top = sorted(probabilities.items(), key=lambda x: -x[1])[:5]
            chunks = []
            for i, (key, prob) in enumerate(top):
                d = next(
                    (x for x in diseases if (x.get("id") or x.get("name_en")) == key),
                    {},
                )
                text = (
                    d.get("document") or d.get("snippet") or d.get("name_en") or ""
                )[:800]
                chunks.append(f"[PASSAGE id={key} weight={round(prob,2)}]\n{text}")
            candidates_text = "\n\n".join(chunks)
            probs_text = "\n".join(f"  {k}: {v*100:.0f}%" for k, v in top)
            priors_text = self._prior_query(baseline or {})
            prompt = build_diagnosis_naming_prompt(
                candidates_text, probs_text, lang,
                priors_text=priors_text, summary_text=summary_text,
            )
            content = self.llm.ask(
                [{"role": "system", "content": prompt}],
                temperature=0,
                max_tokens=1024,
                model=model_name,
            )
            parsed = parse_llm_response(content)
            diags = parsed.get("diagnoses", []) if isinstance(parsed, dict) else []
            if diags:
                for d in diags:
                    d.setdefault("confidence", "Moderate")
                    # Normalize probabilities to a 0-1 fraction (LLMs often emit
                    # percent-scale numbers like 80 instead of 0.8).
                    p = d.get("probability")
                    if isinstance(p, (int, float)):
                        d["probability"] = p / 100.0 if p > 1 else float(p)
                    else:
                        d["probability"] = 0.0
                    # Translate user-facing English fields into the session language
                    # via the deterministic translator (don't trust the LLM's
                    # non-English output, which tends to transliterate).
                    en_name = d.get("disease_name") or d.get("name_en") or ""
                    en_spec = d.get("specialist") or ""
                    en_advice = d.get("advice") or ""
                    d["disease_name_local"] = (
                        from_english(en_name, lang) if lang != "en" else en_name
                    )
                    d["specialist_local"] = (
                        from_english(en_spec, lang) if lang != "en" else en_spec
                    )
                    d["advice_local"] = (
                        from_english(en_advice, lang) if lang != "en" else en_advice
                    )
                return diags
        except Exception as e:
            import traceback

            log("FINALIZE", f"Diagnosis naming failed: {e}\n{traceback.format_exc()}")
        return []

    # ── Report ──

    def get_report(self, session_id: str) -> dict:
        log("REPORT", f"Generating report session={session_id[:8]}")
        session = self._get_session(session_id)
        candidates = session.get("candidates", {})
        conversation = candidates.get("conversation", [])
        lang = candidates.get("language", "en")

        for m in reversed(conversation):
            if m.get("role") == "assistant":
                parsed = parse_llm_response(m.get("content", ""))
                if parsed.get("type") == "diagnosis" or parsed.get("diagnoses"):
                    diags = parsed.get("diagnoses", [])
                    if not any(d.get("disease_name_local") for d in diags):
                        diags = self._localize_diagnoses(diags, lang)
                    return {
                        "response_type": "diagnosis",
                        "diagnosis_summary": {
                            "diagnoses": diags,
                        },
                        "symptoms": self._get_symptoms(candidates, lang),
                        "total": MAX_QUESTIONS,
                    }

        probabilities = candidates.get("probabilities", {})
        diseases = candidates.get("diseases", [])
        diags = self._localize_diagnoses(
            force_top3(probabilities, diseases, candidates.get("id_labels", {})),
            lang,
        )
        return {
            "response_type": "diagnosis",
            "diagnosis_summary": {
                "diagnoses": diags,
            },
            "symptoms": self._get_symptoms(candidates, lang),
            "total": MAX_QUESTIONS,
        }

    # ── Internal helpers ──

    def _localize_diagnoses(self, diags: list, lang: str) -> list:
        if not lang or lang == "en":
            for d in diags:
                d["disease_name_local"] = d.get("disease_name_local") or d.get(
                    "disease_name", ""
                )
                d["specialist_local"] = d.get("specialist_local") or d.get(
                    "specialist", ""
                )
                d["advice_local"] = d.get("advice_local") or d.get("advice", "")
            return diags
        for d in diags:
            d["disease_name_local"] = from_english(d.get("disease_name", ""), lang)
            d["specialist_local"] = from_english(d.get("specialist", ""), lang)
            d["advice_local"] = from_english(d.get("advice", ""), lang)
        return diags

    def _format_question(self, parsed: dict, lang: str) -> dict:
        localized = self._localize_question(parsed, lang)
        opts_raw = localized.get("options", [])
        return {
            "id": localized.get("question_id") or localized.get("id", ""),
            "text": localized.get("question", ""),
            "type": infer_qtype(opts_raw),
            "options": to_question_options(opts_raw),
        }

    def _tag_question(self, session_id: str, parsed: dict, q_index: int) -> dict:
        """Assign a stable DB-backed id to a generated question."""
        parsed = dict(parsed)
        parsed["question_index"] = q_index
        qid = self.session_mgr.insert_question(session_id, q_index, parsed)
        if qid:
            parsed["question_id"] = str(qid)
        else:
            parsed["question_id"] = f"local-{q_index}"
        return parsed

    def _localize_question(self, parsed: dict, lang: str) -> dict:
        """Translate a question's user-facing text back to the user's language."""
        if not lang or lang == "en":
            return parsed
        parsed = dict(parsed)
        if isinstance(parsed.get("question"), str):
            parsed["question"] = from_english(parsed["question"], lang)
        if isinstance(parsed.get("options"), list):
            parsed["options"] = translate_list(
                [str(o) for o in parsed["options"]], lang
            )
        if isinstance(parsed.get("message"), str):
            parsed["message"] = from_english(parsed["message"], lang)
        return parsed

    def _get_symptoms(self, candidates: dict, lang: str) -> list:
        """Return the patient's selected symptoms as a list of strings."""
        symptoms = []
        for s in candidates.get("selected_symptoms", []) or []:
            name = s.get("name_en") or ""
            if lang and lang != "en":
                local = s.get("name_local") or ""
                if local:
                    name = local
            if name:
                symptoms.append(name)
        return symptoms

    def _prior_query(self, baseline: dict) -> str:
        """Turn patient risk factors into a short text fragment that, when
        embedded alongside the symptom query, biases vector retrieval toward
        diseases associated with those factors (smoking, alcohol, age, etc.)."""
        if not baseline:
            return ""
        parts = []
        if baseline.get("age") is not None:
            parts.append(f"age {baseline['age']}")
        if baseline.get("gender"):
            parts.append(str(baseline["gender"]))
        if baseline.get("is_smoker"):
            parts.append("smoking")
        if baseline.get("has_diabetes"):
            parts.append("diabetes")
        if baseline.get("has_hypertension"):
            parts.append("hypertension")
        if baseline.get("is_pregnant"):
            parts.append("pregnancy")
        if baseline.get("activity_level"):
            parts.append(f"activity {baseline['activity_level']}")
        return " ".join(parts)

    def _extract_question_text(self, content: str) -> str:
        """Pull the human-readable question text out of an assistant message
        (which may be a JSON blob carrying a 'question' field)."""
        content = content or ""
        try:
            p = parse_llm_response(content)
            if isinstance(p, dict):
                q = p.get("question") or ""
                if q:
                    return q
        except Exception:
            pass
        return content

    def _patient_facts(self, candidates: dict, conversation: list) -> tuple:
        """Build a compact, deterministic 'known facts' summary and a Q&A-only
        fragment from the conversation. Pure local computation (no LLM call),
        so it adds zero latency.

        Returns (summary_text, qa_text):
          - summary_text: reported symptoms + risk factors + recent Q&A, for the
            system prompt so the model integrates everything it already knows.
          - qa_text: just the "Q -> A" pairs, for vector retrieval so a re-search
            uses the full picture instead of only the last 3 answers.
        """
        symptoms = self._get_symptoms(candidates, "en")
        prior = self._prior_query(candidates.get("baseline") or {})

        qa = []
        n = len(conversation)
        for i, m in enumerate(conversation):
            if m.get("role") != "assistant":
                continue
            q = self._extract_question_text(m.get("content", ""))
            ans = ""
            for j in range(i + 1, n):
                if conversation[j].get("role") == "user":
                    ans = conversation[j].get("content", "")
                    break
            if q and ans:
                qa.append(f"{q} -> {ans}")
        qa_text = " | ".join(qa[-8:])

        parts = []
        if symptoms:
            parts.append("Reported symptoms: " + ", ".join(symptoms))
        if prior:
            parts.append("Risk factors: " + prior)
        if qa_text:
            parts.append("So far: " + qa_text)
        summary_text = "\n".join(parts)
        return summary_text, qa_text

    def _absorb_new_symptoms(self, candidates: dict, parsed: dict) -> None:
        """If the model surfaced newly-revealed symptoms in the question JSON
        ('new_symptoms'), add them to the session's selected symptoms so they
        drive later questions, retrieval, and priors. No extra LLM call — the
        field is read from the question we already generated."""
        raw = parsed.get("new_symptoms") if isinstance(parsed, dict) else None
        if not isinstance(raw, list):
            return
        symptoms = candidates.setdefault("selected_symptoms", [])
        existing = {(s.get("name_en") or "").strip().lower() for s in symptoms}
        for name in raw:
            nm = (name or "").strip()
            if not nm:
                continue
            key = nm.lower()
            if key in existing:
                continue
            symptoms.append({"name_en": nm, "name_local": nm, "snippet": ""})
            existing.add(key)
            log("SYMPTOMS", f"Absorbed new symptom from answer: {nm}")

    def _get_session(self, session_id: str) -> dict:
        # Load a session by FastAPI UUID (stored in Supabase → diagnosis_sessions.id).
        # The `candidates` and `conversation` columns are stored as JSON strings
        # in Postgres, so they are decoded back to Python objects here and cached
        # on the session dict so the rest of the engine can mutate them in place.
        session = self.session_mgr.get_session(session_id)
        if not session:
            raise ValueError(f"Session {session_id} not found")
        candidates = session.get("candidates", {})
        if isinstance(candidates, str):
            candidates = json.loads(candidates)
        if isinstance(candidates, list):
            candidates = {
                "diseases": candidates,
                "socrates_axis": 0,
                "probabilities": {},
            }
        conv = session.get("conversation", [])
        if isinstance(conv, str):
            conv = json.loads(conv)
        candidates["conversation"] = conv
        session["candidates"] = candidates
        return session

    def _save_candidates(self, session_id: str, candidates: dict):
        conversation = candidates.pop("conversation", [])
        self.session_mgr.update_conversation(
            session_id, conversation, candidates=candidates
        )

    @staticmethod
    def _extract_asked_questions(conversation: list) -> list:
        """Extract the text of questions already asked from the conversation."""
        asked = []
        for m in conversation or []:
            if m.get("role") != "assistant":
                continue
            try:
                parsed = parse_llm_response(m.get("content", ""))
            except Exception:
                continue
            if parsed.get("type") == "question":
                q = (parsed.get("question") or "").strip()
                if q:
                    asked.append(q)
        return asked

    @staticmethod
    def _is_repeated_question(new_q: str, asked: list) -> bool:
        """Detect whether the candidate question repeats one already asked.

        Matches exact normalized text and strong containment (rephrases), so the
        SOCRATES loop never gets stuck re-asking the same thing.
        """
        def normalize(s):
            s = (s or "").lower()
            s = re.sub(r"[^a-z0-9\u0600-\u06ff\s]", " ", s)
            return re.sub(r"\s+", " ", s).strip()

        target = normalize(new_q)
        if len(target) < 8:
            return False
        for a in asked or []:
            na = normalize(a)
            if not na:
                continue
            if target == na:
                return True
            if len(target) >= 12 and len(na) >= 12 and (target in na or na in target):
                return True
        return False

    def _match_answer_index(self, answer, answer_en, options, lang) -> int | None:
        """Map a free-text / translated user answer to the index of the option the
        model actually used, so the Bayesian update works regardless of language or
        small phrasing differences.

        Returns the matched option index, or None if nothing relates (in which
        case the caller should skip the update rather than silently mismatching).
        """
        if not options:
            return None
        ans = (answer or "").strip().lower()
        ans_en = (answer_en or "").strip().lower()
        if not ans and not ans_en:
            return None

        def _tokens(s):
            return set(re.findall(r"[A-Za-z0-9]+", s)) | set(
                re.findall(r"[\u0600-\u06ff]+", s)
            )

        ans_tok = _tokens(ans) | _tokens(ans_en)
        best_idx = None
        best_score = 0.0
        for i, opt in enumerate(options):
            o = (opt or "").strip().lower()
            if not o:
                continue
            o_local = (
                from_english(opt, lang).strip().lower()
                if lang and lang != "en"
                else ""
            )
            # Exact match on any representation (raw, English, or translated).
            if ans == o or ans_en == o or (o_local and ans == o_local):
                return i
            score = 0.0
            o_tok = _tokens(o) | _tokens(o_local)
            if ans_tok & o_tok:
                score = 0.6
            if ans and (
                ans in o
                or o in ans
                or (o_local and (ans in o_local or o_local in ans))
            ):
                score = max(score, 0.7)
            if score > best_score:
                best_score = score
                best_idx = i
        return best_idx if best_score >= 0.6 else None

    # NOTE: question-validity heuristics (negation / site / naming guards) were
    # removed. With the Llama 3.3 70B reasoning model, question quality is governed
    # entirely by the SOCRATES + reasoning rules in the prompt — no hardcoded checks.

    def _re_search(
        self,
        conversation: list,
        existing_diseases: list,
        existing_probs: dict,
        lang: str = "en",
        candidates: dict = None,
        model_name: str = None,
    ) -> dict:
        user_texts = [
            m.get("content", "") for m in conversation if m.get("role") == "user"
        ]
        if not user_texts or len(user_texts) < 2:
            return existing_probs

        # Ground the re-search to the PATIENT'S SELECTED SYMPTOMS so unrelated,
        # severe-sounding diseases (e.g. cancer, kidney failure) are not injected
        # into the candidate pool just because the conversation text is similar.
        selected = (candidates or {}).get("selected_symptoms", []) or []
        symptom_kw = set()
        for s in selected:
            for key in ("name_en", "name_local"):
                v = (s.get(key) or "").strip().lower()
                if v:
                    symptom_kw.add(v)
                    for w in re.split(r"\W+", v):
                        if len(w) >= 3:
                            symptom_kw.add(w)

        def _related_to_symptoms(r):
            if not symptom_kw:
                return True
            text = " ".join([
                (r.get("name_en") or ""),
                (r.get("symptoms_en") or ""),
                (r.get("document") or ""),
            ]).lower()
            return any(kw in text for kw in symptom_kw)

        symptom_query = " | ".join(sorted({
            s.get("name_en", "") for s in selected if s.get("name_en")
        }))
        prior_q = self._prior_query(candidates.get("baseline"))
        # Use the full patient Q&A picture (not just the last 3 answers) so the
        # re-search reflects everything the patient has revealed, not a sliding
        # window that forgets earlier context.
        _, qa_text = self._patient_facts(candidates or {}, conversation)
        query_parts = (
            ([prior_q] if prior_q else [])
            + ([symptom_query] if symptom_query else [])
            + ([qa_text] if qa_text else [])
        )
        query = " | ".join([p for p in query_parts if p])
        query_vector = self.embedder.encode_query(query)
        results = self.store.search(query_vector, limit=10) or []

        log(
            "RESEARCH",
            f"Re-search query ({len(user_texts)} user msgs) -> {len(results)} candidates",
            [r.get("name_en") for r in results[:3]],
        )

        existing_names = {d.get("name_en", "") for d in existing_diseases or []}
        found_any = False
        max_sim = 0.0
        combined_text = ""

        # Try vector results with name_en first, but never let the candidate
        # pool keep inflating past MAX_TOTAL_DISEASES (each new disease at 0.01
        # dilutes the posterior and flattens all probabilities).
        for r in results:
            if len(existing_diseases) >= MAX_TOTAL_DISEASES:
                break
            name = r.get("name_en", "")
            if name and name not in existing_names and _related_to_symptoms(r):
                existing_diseases.append(r)
                existing_probs[name] = 0.01
                existing_names.add(name)
                found_any = True

        # If nothing had name_en, use LLM to extract disease names from PDF text
        if not found_any and results:
            pdf_texts = []
            for r in results[:5]:
                doc = (r.get("document") or "").strip()[:500]
                if doc:
                    pdf_texts.append(f"[PASSAGE]\n{doc}")
                sim = r.get("similarity") or 0
                if isinstance(sim, (int, float)) and sim > max_sim:
                    max_sim = sim
            combined_text = " | ".join(
                (r.get("document") or "").strip()[:200]
                for r in results[:3]
                if r.get("document")
            )[:500]
            if pdf_texts:
                context = "\n\n".join(pdf_texts)
                prompt = build_disease_names_prompt(context)
                try:
                    raw = self.llm.ask(
                        [
                            {
                                "role": "system",
                                "content": DISEASE_EXTRACTION_SYSTEM_MSG,
                            },
                            {"role": "user", "content": prompt},
                        ],
                        temperature=0,
                        max_tokens=512,
                        model=model_name,
                    )
                    parsed = (
                        json.loads(raw[raw.find("{") : raw.rfind("}") + 1])
                        if "{" in raw
                        else {}
                    )
                    extracted = (
                        parsed.get("diseases", []) if isinstance(parsed, dict) else []
                    )
                    for item in extracted:
                        if len(existing_diseases) >= MAX_TOTAL_DISEASES:
                            break
                        if isinstance(item, str):
                            name = item.strip()
                            spec = "General"
                        else:
                            name = (item.get("name_en") or "").strip()
                            spec = (item.get("specialist") or "General").strip()
                        if name and name not in existing_names:
                            candidate = {
                                "name_en": name,
                                "name_local": (
                                    from_english(name, lang)
                                    if lang != "en"
                                    else name
                                ),
                                "symptoms_en": combined_text,
                                "specialist": spec,
                                "similarity": max_sim if max_sim > 0 else 0.5,
                            }
                            if not _related_to_symptoms(candidate):
                                continue
                            existing_diseases.append(candidate)
                            existing_probs[name] = 0.01
                            existing_names.add(name)
                            found_any = True
                    if found_any:
                        log(
                            "RESEARCH",
                            f"LLM extracted {len(extracted)} disease names from PDFs",
                        )
                except Exception as e:
                    log("RESEARCH", f"LLM extraction failed: {str(e)[:60]}")

        # If STILL nothing found, fall back to text-matching the selected symptom names
        if not found_any and candidates.get("selected_symptoms"):
            for sel in candidates["selected_symptoms"]:
                if len(existing_diseases) >= MAX_TOTAL_DISEASES:
                    break
                name = (sel.get("name_en") or "").strip()
                if name and name not in existing_names:
                    existing_diseases.append(
                        {
                            "name_en": name,
                            "name_local": (
                                from_english(name, lang) if lang != "en" else name
                            ),
                            "symptoms_en": combined_text,
                            "specialist": "General",
                            "similarity": max_sim if max_sim > 0 else 0.5,
                        }
                    )
                    existing_probs[name] = 0.01
                    existing_names.add(name)

        total = sum(existing_probs.values()) or 1
        for k in existing_probs:
            existing_probs[k] /= total
        return existing_probs

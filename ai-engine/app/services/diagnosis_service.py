import json
import uuid

from app.services.logger import log
from app.services.bayesian import (
    compute_priors,
    bayes_update,
    check_stopping,
    force_top3,
    MAX_QUESTIONS,
)
from app.services.socrates import (
    detect_lang,
    format_candidates,
    parse_llm_response,
    build_system_prompt,
    build_diagnosis_naming_prompt,
)
from app.services.i18n import (
    to_english,
    from_english,
    translate_list,
    translate_dict_values,
)

MIN_PER_SYMPTOM = 2
DYNAMIC_EXTRA = 6


def _to_qopts(options_raw: list) -> list:
    if not options_raw:
        return []
    return [{"id": str(i + 1), "label": str(o)} for i, o in enumerate(options_raw)]


def _infer_qtype(options_raw: list) -> str:
    if not options_raw:
        return "single_choice"
    n = [str(o).strip().lower() for o in options_raw]
    if n == ["yes", "no"] or n in (["y", "n"], ["نعم", "لا"]):
        return "yes_no"
    return "single_choice"


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

        # Vector search anchored on the selected result
        query_vector = self.embedder.encode_query(query_text)
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

        # Extract disease names from PDF chunks via LLM
        results = []
        existing_names = {d.get("name_en") for d in candidates.get("diseases", [])}

        # Always include the selected symptom as the primary candidate
        if name_en and name_en not in existing_names:
            results.append(
                {
                    "name_en": name_en,
                    "name_local": name_local,
                    "symptoms_en": snippet[:500],
                    "specialist": "General",
                    "similarity": 0.9,
                }
            )
            existing_names.add(name_en)

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
            extract_prompt = f"""Extract specific medical conditions/diseases from these passages. For each disease, provide its name and the relevant medical specialist.

Passages:
{context}

Respond ONLY with valid JSON:
{{"results": [{{"name_en": "Disease Name", "type": "illness", "summary": "brief description", "specialist": "Specialist type"}}]}}"""
            try:
                raw = self.llm.ask(
                    [
                        {
                            "role": "system",
                            "content": "You extract disease names from medical text. Output ONLY valid JSON.",
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
                                "name_local": from_english(disease_name, lang) if lang != "en" else disease_name,
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
            log("BAYES", f"Priors initialized: {_short(priors)}")
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
                f"Priors merged (symptom added): {_short(candidates['probabilities'])}",
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
        q = candidates.get("current_question")
        if not q:
            return {"response_type": "unknown", "question": None}
        return {
            "response_type": "question",
            "question": self._format_question(q, lang),
            "total": MAX_QUESTIONS,
        }

    def submit_follow_up_answer(
        self, session_id: str, question_id: str, answer: str
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
                old_top = max(probabilities.items(), key=lambda x: x[1])
                probabilities = bayes_update(
                    probabilities, probs_per_option, options, answer
                )
                new_top = max(probabilities.items(), key=lambda x: x[1])
                log(
                    "BAYES",
                    f"Updated: {old_top[0]}={old_top[1]:.2f} -> {new_top[0]}={new_top[1]:.2f}",
                )

        # Dynamic re-search every 3 axes
        if socrates_axis >= 3 and socrates_axis % 3 == 0:
            log("VECTOR", f"Re-search at axis {socrates_axis}")
            probabilities = self._re_search(
                conversation, diseases, probabilities, lang, model_name=model_name
            )

        questions_on_current = candidates.get("questions_on_current", 0) + 1
        candidates["questions_on_current"] = questions_on_current
        question_count = question_count + 1
        candidates["question_count"] = question_count

        can_stop = check_stopping(probabilities, socrates_axis)

        remaining = MAX_QUESTIONS - question_count
        top1 = max(probabilities.items(), key=lambda x: x[1])[1] if probabilities else 0
        per_symptom_cap = _per_symptom_cap(top1, remaining, MIN_PER_SYMPTOM)
        log(
            "CAP",
            f"question_count={question_count} remaining={remaining} top1={top1:.2f} per_symptom_cap={per_symptom_cap} questions_on_current={questions_on_current}",
        )

        force = (
            question_count >= MAX_QUESTIONS
            or (
                questions_on_current >= per_symptom_cap and remaining <= MIN_PER_SYMPTOM
            )
            or (questions_on_current >= per_symptom_cap and can_stop)
        )
        if force:
            log(
                "FOLLOWUP",
                f"Forcing diagnosis: stop={can_stop} count={question_count}/{MAX_QUESTIONS}",
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
        system_prompt = build_system_prompt(
            diseases_text,
            socrates_axis,
            probs_text,
            language=lang,
            force=False,
            baseline=baseline,
        )
        messages = [{"role": "system", "content": system_prompt}, *conversation]
        content = self.llm.ask(messages, model=model_name)
        parsed = parse_llm_response(content)
        log(
            "LLM",
            f"Groq response type={parsed.get('type')} q{question_count}/{MAX_QUESTIONS}",
        )

        if parsed.get("type") == "error":
            messages.append({"role": "assistant", "content": content})
            messages.append(
                {
                    "role": "user",
                    "content": "Please respond with valid JSON using the exact format specified.",
                }
            )
            content = self.llm.ask(messages, temperature=0.1, model=model_name)
            parsed = parse_llm_response(content)

        llm_type = parsed.get("type")

        # LLM wants more symptoms
        if llm_type == "need_more_symptoms":
            conversation.append({"role": "assistant", "content": content})
            candidates["socrates_axis"] = socrates_axis + 1
            candidates["conversation"] = conversation
            candidates["current_question"] = None
            self._save_candidates(session_id, candidates)
            msg = from_english(
                parsed.get("message", "Please search for another symptom."), lang
            )
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

        # LLM decided diagnosis
        if (
            llm_type == "diagnosis"
            or parsed.get("diagnoses")
            or parsed.get("diagnosis")
        ):
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

        # Normal question -> persist and continue
        q_index = question_count + 1
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
        system_prompt = build_system_prompt(
            diseases_text,
            socrates_axis,
            probs_text,
            language=lang,
            force=False,
            baseline=baseline,
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
            messages.append({"role": "assistant", "content": content})
            messages.append(
                {
                    "role": "user",
                    "content": "Please respond with valid JSON using the exact format specified.",
                }
            )
            content = self.llm.ask(messages, temperature=0.1, model=model_name)
            parsed = parse_llm_response(content)

        llm_type = parsed.get("type")
        if (
            llm_type == "diagnosis"
            or parsed.get("diagnoses")
            or parsed.get("diagnosis")
        ):
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

        # If the LLM already produced named diagnoses during the loop, keep them
        if parsed_override and (
            parsed_override.get("diagnoses") or parsed_override.get("diagnosis")
        ):
            parsed = parsed_override
            if parsed.get("diagnosis") and not parsed.get("diagnoses"):
                diag = parsed.get("diagnosis")
                parsed["diagnoses"] = (
                    diag.get("top_3")
                    if isinstance(diag, dict) and "top_3" in diag
                    else force_top3(probabilities, diseases, labels)
                )
        else:
            # Derive named diagnoses from the top evidence passages + Bayesian weights
            named = self._name_diagnoses(
                probabilities, diseases, lang, model_name=model_name
            )
            fallback = force_top3(probabilities, diseases, labels)
            if named and len(named) < 3:
                existing_names = {d.get("disease_name", "").lower() for d in named}
                for fb in fallback:
                    if (
                        fb.get("disease_name", "").lower() not in existing_names
                        and len(named) < 3
                    ):
                        named.append(fb)
            parsed = {"type": "diagnosis", "diagnoses": named if named else fallback}

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
            "total": MAX_QUESTIONS,
        }

    def _name_diagnoses(
        self, probabilities, diseases, lang, model_name: str = None
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
                chunks.append(
                    f"[PASSAGE id={key} weight={round(prob,2)}]\n{text}"
                )
            candidates_text = "\n\n".join(chunks)
            probs_text = "\n".join(f"  {k}: {v*100:.0f}%" for k, v in top)
            prompt = build_diagnosis_naming_prompt(candidates_text, probs_text, lang)
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
            "type": _infer_qtype(opts_raw),
            "options": _to_qopts(opts_raw),
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

    def _get_session(self, session_id: str) -> dict:
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

    def _re_search(
        self,
        conversation: list,
        existing_diseases: list,
        existing_probs: dict,
        lang: str = "en",
        model_name: str = None,
    ) -> dict:
        user_texts = [
            m.get("content", "") for m in conversation if m.get("role") == "user"
        ]
        if not user_texts or len(user_texts) < 2:
            return existing_probs

        query = " | ".join(user_texts[-5:])
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

        # Try vector results with name_en first
        for r in results:
            name = r.get("name_en", "")
            if name and name not in existing_names:
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
                prompt = f"""Extract specific medical condition/disease names mentioned in these passages. For each, provide the relevant medical specialist.

Passages:
{context}

Respond ONLY with: {{"diseases": [{{"name_en": "disease", "specialist": "Specialist"}}]}}"""
                try:
                    raw = self.llm.ask(
                        [
                            {
                                "role": "system",
                                "content": "You extract disease names from medical text. Output ONLY valid JSON.",
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
                        if isinstance(item, str):
                            name = item.strip()
                            spec = "General"
                        else:
                            name = (item.get("name_en") or "").strip()
                            spec = (item.get("specialist") or "General").strip()
                        if name and name not in existing_names:
                            existing_diseases.append(
                                {
                                    "name_en": name,
                                    "name_local": from_english(name, lang) if lang != "en" else name,
                                    "symptoms_en": combined_text,
                                    "specialist": spec,
                                    "similarity": max_sim if max_sim > 0 else 0.5,
                                }
                            )
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
                name = (sel.get("name_en") or "").strip()
                if name and name not in existing_names:
                    existing_diseases.append(
                        {
                            "name_en": name,
                            "name_local": from_english(name, lang) if lang != "en" else name,
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


def _per_symptom_cap(top1_prior: float, remaining: int, min_per: int) -> int:
    uncertainty = 1.0 - float(top1_prior)
    requested = min_per + round(uncertainty * DYNAMIC_EXTRA)
    budget_room = remaining - min_per
    cap = min(requested, budget_room) if budget_room > 0 else min_per
    return max(min_per, cap)


def _short(probs: dict) -> dict:
    return {k: round(v, 2) for k, v in sorted(probs.items(), key=lambda x: -x[1])[:5]}


def _round(probs: dict) -> dict:
    return {k: round(v, 2) for k, v in probs.items()}

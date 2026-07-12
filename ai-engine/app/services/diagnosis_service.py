import json
import uuid

from app.services.logger import log
from app.services.web_search import search_web
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
    build_symptom_questions_prompt,
    build_conclusion_prompt,
)


class DiagnosisService:

    def __init__(self, store, embedder, session_mgr, llm):
        self.store = store
        self.embedder = embedder
        self.session_mgr = session_mgr
        self.llm = llm

    # ── Session management ──

    def create_session(self, baseline: dict | None = None, user_id: str = "anonymous") -> str:
        lang = detect_lang(json.dumps(baseline)) if baseline else "en"
        candidates = {
            "phase": "collection",
            "baseline": baseline or {},
            "language": lang,
            "symptoms": [],
            "socrates_axis": 0,
            "probabilities": {},
            "diseases": [],
            "conversation": [],
            "question_count": 0,
            "current_question": None,
        }
        session_id = str(uuid.uuid4())
        self.session_mgr._rpc("create_diagnosis_session", {
            "p_id": session_id,
            "p_user_id": user_id,
            "p_initial_symptoms": "diagnosis_session",
            "p_candidates": json.dumps(candidates),
        })
        log("SESSION", f"Created session {session_id[:8]} user={user_id} lang={lang}")
        return session_id

    # ── Symptom collection phase ──

    def get_symptom_questions(self, session_id: str, symptom_name: str) -> dict:
        session = self._get_session(session_id)
        candidates = session.get("candidates", {})
        if candidates.get("phase") not in ("collection",):
            return {"error": "Session is not in collection phase", "phase": candidates.get("phase")}

        lang = candidates.get("language", "en")

        log("SYMPTOM", f"Getting questions for '{symptom_name}' session={session_id[:8]}")

        # Vector search for this symptom
        query_vector = self.embedder.encode_query(symptom_name)
        results = self.store.search(query_vector, limit=5, filter_type=None)
        log("VECTOR", f"'{symptom_name}' -> {len(results)} results", [r.get("name_en") for r in results])

        if not results:
            log("VECTOR", f"No results for '{symptom_name}', falling back to web search")
            web_results = search_web(f"{symptom_name} medical condition symptoms", limit=5)
            if not web_results:
                return {"error": "No matching diseases found for this symptom"}
            web_text = "\n".join(
                f"{r['title']}: {r['content'] or r['snippet']}" for r in web_results if r.get('content') or r.get('snippet')
            )[:3000]
            candidates_text = f"Web search results for '{symptom_name}':\n{web_text}"
            results = [
                {
                    "name_en": r["title"],
                    "name_ar": r["title"],
                    "symptoms_en": r.get("content") or r.get("snippet") or "",
                    "symptoms_ar": r.get("content") or r.get("snippet") or "",
                    "specialist": "General",
                    "specialist_ar": "\u0639\u0627\u0645",
                    "similarity": 0.5,
                }
                for r in web_results
            ]
        else:
            candidates_text = format_candidates(results)

        # LLM generates up to 3 clarifying questions
        prompt = build_symptom_questions_prompt(symptom_name, candidates_text, lang)
        log("LLM", f"Asking Groq for symptom questions (prompt={len(prompt)} chars)")
        messages = [{"role": "system", "content": prompt}]
        content = self.llm.ask(messages)
        parsed = parse_llm_response(content)
        log("LLM", f"Groq response type={parsed.get('type')} questions={len(parsed.get('questions', []))}")

        questions = parsed.get("questions", [])[:3]

        # Normalize question types
        for q in questions:
            if q.get("type") not in ("single_choice", "yes_no", "multiple_choice"):
                q["type"] = "single_choice"
            if q.get("type") == "yes_no":
                q["options"] = [
                    {"id": "y", "label": "Yes" if lang == "en" else "\u0646\u0639\u0645"},
                    {"id": "n", "label": "No" if lang == "en" else "\u0644\u0627"},
                ]

        # Store in session
        symptom_entry = {
            "name": symptom_name,
            "candidates": results,
            "questions": questions,
            "answers": [],
            "question_count": len(questions),
            "conclusion": None,
        }
        candidates.setdefault("symptoms", []).append(symptom_entry)
        self._save_candidates(session_id, candidates)

        return {
            "symptom_name": symptom_name,
            "questions": questions,
        }

    def submit_symptom_answers(
        self, session_id: str, symptom_name: str, answers: list, symptoms_complete: bool = False
    ) -> dict:
        session = self._get_session(session_id)
        candidates = session.get("candidates", {})
        if candidates.get("phase") != "collection":
            return {"error": "Session is not in collection phase", "phase": candidates.get("phase")}

        lang = candidates.get("language", "en")
        symptoms = candidates.get("symptoms", [])
        symptom_entry = None
        for s in reversed(symptoms):
            if s.get("name") == symptom_name:
                symptom_entry = s
                break
        if not symptom_entry:
            return {"error": f"Symptom '{symptom_name}' not found in session"}

        log("SYMPTOM", f"Storing {len(answers)} answers for '{symptom_name}'", answers)
        qa_pairs = []
        for ans in answers:
            qid = ans.get("question_id", "")
            selected = ans.get("selected_option_labels", ans.get("answer", ""))
            qa_pairs.append({"question_id": qid, "answer": selected})
        symptom_entry["answers"] = qa_pairs

        # Generate conclusion via LLM
        qa_text = "\n".join(f"Q: {a.get('question_id')} A: {a.get('answer')}" for a in qa_pairs)
        candidates_text = format_candidates(symptom_entry.get("candidates", []))
        prompt = build_conclusion_prompt(symptom_name, qa_text, candidates_text, lang)
        log("LLM", f"Asking Groq for conclusion on '{symptom_name}'")
        messages = [{"role": "system", "content": prompt}]
        content = self.llm.ask(messages)
        parsed = parse_llm_response(content)
        conclusion = parsed.get("conclusion", "No conclusion generated.")
        log("LLM", f"Conclusion: {conclusion[:100]}")
        symptom_entry["conclusion"] = conclusion

        self._save_candidates(session_id, candidates)

        if symptoms_complete:
            return self._transition_to_diagnosis(session_id, candidates)
        return {
            "phase": "collection",
            "next_symptom_prompt": "Do you have any other symptoms?",
        }

    def _transition_to_diagnosis(self, session_id: str, candidates: dict) -> dict:
        log("PHASE", f"Transitioning to diagnosis session={session_id[:8]}")
        lang = candidates.get("language", "en")
        symptoms = candidates.get("symptoms", [])

        # Build combined text from all symptoms
        combined_text_parts = []
        for s in symptoms:
            combined_text_parts.append(f"Symptom: {s['name']}")
            for qa in s.get("answers", []):
                combined_text_parts.append(f"  {qa.get('question_id')}: {qa.get('answer')}")
            if s.get("conclusion"):
                combined_text_parts.append(f"  Conclusion: {s['conclusion']}")
        combined_text = "\n".join(combined_text_parts)
        log("VECTOR", f"Re-searching with combined text ({len(combined_text)} chars)")
        query_vector = self.embedder.encode_query(combined_text)
        results = self.store.search(query_vector, limit=5, filter_type=None)
        log("VECTOR", f"Found {len(results)} disease candidates", [r.get("name_en") for r in results])
        priors = compute_priors(results) if results else {}

        if not results:
            log("VECTOR", "Falling back to web search for disease candidates")
            web_results = search_web(combined_text[:200], limit=5)
            if web_results:
                results = [
                    {
                        "name_en": r["title"],
                        "name_ar": r["title"],
                        "symptoms_en": r.get("content") or r.get("snippet") or "",
                        "symptoms_ar": r.get("content") or r.get("snippet") or "",
                        "specialist": "General",
                        "specialist_ar": "\u0639\u0627\u0645",
                        "similarity": 0.5,
                    }
                    for r in web_results
                ]

        log("BAYES", f"Priors: { {k: round(v,2) for k,v in sorted(priors.items(), key=lambda x: -x[1])[:5] } }")

        # Build system prompt for first SOCRATES question
        candidates_text = format_candidates(results) if results else "No matching diseases found."
        probs_text = "\n".join(
            f"  {k}: {v*100:.0f}%" for k, v in sorted(priors.items(), key=lambda x: -x[1])
        ) if priors else ""

        system_prompt = build_system_prompt(candidates_text, 0, probs_text, language=lang)

        # Build initial message with symptom summary
        initial_msg = f"I have the following symptoms and findings:\n{combined_text}"

        conversation = [{"role": "user", "content": initial_msg}]
        messages = [{"role": "system", "content": system_prompt}, *conversation]

        content = self.llm.ask(messages)
        parsed = parse_llm_response(content)

        if parsed.get("type") == "error":
            messages.append({"role": "assistant", "content": content})
            messages.append({
                "role": "user",
                "content": "Please respond with valid JSON using the exact format specified.",
            })
            content = self.llm.ask(messages, temperature=0.1)
            parsed = parse_llm_response(content)

        conversation.append({"role": "assistant", "content": content})

        # Update session state
        candidates["phase"] = "diagnosis"
        candidates["socrates_axis"] = 1
        candidates["probabilities"] = priors
        candidates["diseases"] = results
        candidates["conversation"] = conversation
        candidates["question_count"] = 0
        candidates["current_question"] = parsed if parsed.get("type") != "diagnosis" else None

        self._save_candidates(session_id, candidates)

        if parsed.get("type") == "diagnosis":
            self.session_mgr.update_conversation(session_id, conversation, status="completed")
            return {
                "phase": "diagnosis",
                "type": "diagnosis",
                "diagnoses": parsed.get("diagnoses", force_top3(priors, results)),
                "probabilities": {k: round(v, 2) for k, v in priors.items()} if priors else {},
            }

        return {
            "phase": "diagnosis",
            "question": parsed,
            "total": MAX_QUESTIONS,
        }

    # ── Diagnosis phase ──

    def get_current_question(self, session_id: str) -> dict:
        session = self._get_session(session_id)
        candidates = session.get("candidates", {})
        if candidates.get("phase") != "diagnosis":
            return {"question": None, "phase": candidates.get("phase")}
        q = candidates.get("current_question")
        return {"question": q, "total": MAX_QUESTIONS}

    def submit_follow_up_answer(
        self, session_id: str, question_id: str, answer: str
    ) -> dict:
        session = self._get_session(session_id)
        candidates = session.get("candidates", {})
        if candidates.get("phase") != "diagnosis":
            return {"error": "Session is not in diagnosis phase", "phase": candidates.get("phase")}
        if session.get("status") == "completed":
            return {"error": "Session already completed"}

        lang = candidates.get("language", "en")
        conversation = candidates.get("conversation", [])
        diseases = candidates.get("diseases", [])
        probabilities = candidates.get("probabilities", {})
        socrates_axis = candidates.get("socrates_axis", 0)

        question_count = sum(1 for m in conversation if m.get("role") == "assistant")
        log("FOLLOWUP", f"Answer q{question_count+1}/{MAX_QUESTIONS} session={session_id[:8]} answer='{answer[:60]}' axis={socrates_axis}")

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
                probabilities = bayes_update(probabilities, probs_per_option, options, answer)
                new_top = max(probabilities.items(), key=lambda x: x[1])
                log("BAYES", f"Updated: {old_top[0]}={old_top[1]:.2f} -> {new_top[0]}={new_top[1]:.2f}")

        # Dynamic re-search every 3 axes
        if socrates_axis >= 3 and socrates_axis % 3 == 0:
            log("VECTOR", f"Re-search at axis {socrates_axis}")
            probabilities = self._re_search(conversation, diseases, probabilities)

        can_stop = check_stopping(probabilities, socrates_axis)
        next_axis = socrates_axis + 1
        force = question_count >= MAX_QUESTIONS or can_stop
        if force:
            log("FOLLOWUP", f"Forcing diagnosis: stop={can_stop} count={question_count+1}/{MAX_QUESTIONS}")

        # Build prompt
        diseases_text = format_candidates(diseases) if diseases else "No matching diseases found."
        probs_text = "\n".join(
            f"  {k}: {v*100:.0f}%" for k, v in sorted(probabilities.items(), key=lambda x: -x[1])
        ) if probabilities else ""

        system_prompt = build_system_prompt(
            diseases_text, socrates_axis, probs_text, language=lang, force=force
        )

        conversation.append({"role": "user", "content": answer})
        messages = [{"role": "system", "content": system_prompt}, *conversation]

        content = self.llm.ask(messages)
        parsed = parse_llm_response(content)
        log("LLM", f"Groq response type={parsed.get('type')} question_count={question_count+1}/{MAX_QUESTIONS}")

        if parsed.get("type") == "error":
            messages.append({"role": "assistant", "content": content})
            messages.append({
                "role": "user",
                "content": "Please respond with valid JSON using the exact format specified.",
            })
            content = self.llm.ask(messages, temperature=0.1)
            parsed = parse_llm_response(content)

        llm_diagnosis = (
            parsed.get("type") == "diagnosis"
            or bool(parsed.get("diagnoses"))
            or bool(parsed.get("diagnosis"))
        )

        if force:
            if not llm_diagnosis:
                parsed = {"type": "diagnosis", "diagnoses": force_top3(probabilities, diseases)}
            else:
                parsed["type"] = "diagnosis"
                if not parsed.get("diagnoses"):
                    diag = parsed.pop("diagnosis", {})
                    if isinstance(diag, dict) and "top_3" in diag:
                        parsed["diagnoses"] = diag["top_3"]
                    else:
                        parsed["diagnoses"] = force_top3(probabilities, diseases)
            conversation.append({"role": "assistant", "content": json.dumps(parsed)})
            self.session_mgr.update_conversation(session_id, conversation, status="completed")
            return {
                "type": "diagnosis",
                "diagnoses": parsed["diagnoses"],
                "probabilities": {k: round(v, 2) for k, v in probabilities.items()} if probabilities else {},
            }

        if llm_diagnosis:
            messages.append({"role": "assistant", "content": content})
            messages.append({
                "role": "user",
                "content": "Do NOT diagnose yet. Ask a SOCRATES question with options and probs_per_option.",
            })
            content = self.llm.ask(messages, temperature=0.1)
            parsed = parse_llm_response(content)

        conversation.append({"role": "assistant", "content": content})
        candidates["socrates_axis"] = next_axis
        candidates["probabilities"] = probabilities
        candidates["diseases"] = diseases
        candidates["conversation"] = conversation
        candidates["question_count"] = question_count + 1
        candidates["current_question"] = parsed if parsed.get("type") != "diagnosis" else None

        self._save_candidates(session_id, candidates)

        if parsed.get("type") == "diagnosis":
            self.session_mgr.update_conversation(session_id, conversation, status="completed")

        return {
            "question": parsed if parsed.get("type") != "diagnosis" else None,
            "diagnoses": parsed.get("diagnoses") if parsed.get("type") == "diagnosis" else None,
            "type": parsed.get("type"),
            "probabilities": {k: round(v, 2) for k, v in probabilities.items()} if probabilities else {},
            "total": MAX_QUESTIONS if parsed.get("type") != "diagnosis" else None,
        }

    # ── Report ──

    def get_report(self, session_id: str) -> dict:
        log("REPORT", f"Generating report session={session_id[:8]}")
        session = self._get_session(session_id)
        candidates = session.get("candidates", {})
        conversation = candidates.get("conversation", [])

        for m in reversed(conversation):
            if m.get("role") == "assistant":
                parsed = parse_llm_response(m.get("content", ""))
                if parsed.get("type") == "diagnosis" or parsed.get("diagnoses"):
                    return parsed

        probabilities = candidates.get("probabilities", {})
        diseases = candidates.get("diseases", [])
        return {
            "type": "diagnosis",
            "diagnoses": force_top3(probabilities, diseases),
        }

    # ── Internal helpers ──

    def _get_session(self, session_id: str) -> dict:
        session = self.session_mgr.get_session(session_id)
        if not session:
            raise ValueError(f"Session {session_id} not found")
        candidates = session.get("candidates", {})
        if isinstance(candidates, str):
            candidates = json.loads(candidates)
        if isinstance(candidates, list):
            candidates = {"diseases": candidates, "socrates_axis": 0, "probabilities": {}}
        conv = session.get("conversation", [])
        if isinstance(conv, str):
            conv = json.loads(conv)
        candidates["conversation"] = conv
        session["candidates"] = candidates
        return session

    def _save_candidates(self, session_id: str, candidates: dict):
        conversation = candidates.pop("conversation", [])
        self.session_mgr.update_conversation(session_id, conversation, candidates=candidates)

    def _re_search(self, conversation: list, existing_diseases: list, existing_probs: dict) -> dict:
        user_texts = [m.get("content", "") for m in conversation if m.get("role") == "user"]
        if not user_texts or len(user_texts) < 2:
            return existing_probs

        query = " | ".join(user_texts[-5:])
        query_vector = self.embedder.encode_query(query)
        results = self.store.search(query_vector, limit=5, filter_type=None)
        log("RESEARCH", f"Re-search query ({len(user_texts)} user msgs) -> {len(results)} new candidates", [r.get("name_en") for r in results])

        existing_names = {d.get("name_en", "") for d in existing_diseases or []}
        for r in results:
            name = r.get("name_en", "")
            if name and name not in existing_names:
                existing_diseases.append(r)
                existing_probs[name] = 0.01
                existing_names.add(name)

        total = sum(existing_probs.values()) or 1
        for k in existing_probs:
            existing_probs[k] /= total
        return existing_probs

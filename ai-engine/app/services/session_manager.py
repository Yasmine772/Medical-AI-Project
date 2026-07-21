import json
import os
import uuid
from typing import Any, Dict, List, Optional
from supabase import Client, create_client

from dotenv import load_dotenv
load_dotenv()

from app.services.logger import log


class SessionManager:

    def __init__(self):
        self.supabase_url = os.environ.get("SUPABASE_URL")
        self.supabase_key = os.environ.get("SUPABASE_KEY")
        if not (self.supabase_url and self.supabase_key):
            raise ValueError("Set SUPABASE_URL and SUPABASE_KEY in .env")
        self._supabase: Optional[Client] = None

    def connect(self):
        if self._supabase is None:
            self._supabase = create_client(self.supabase_url, self.supabase_key)
        return self._supabase

    def _rpc(self, name: str, params: Dict[str, Any] | None = None):
        return self.connect().rpc(name, params or {}).execute()

    def create_session(
        self,
        user_id: str,
        initial_symptoms: str,
        candidates: Optional[List[Dict]] = None,
    ) -> str:
        session_id = str(uuid.uuid4())
        self._rpc("create_diagnosis_session", {
            "p_id": session_id,
            "p_user_id": user_id,
            "p_initial_symptoms": initial_symptoms,
            "p_candidates": json.dumps(candidates) if candidates else None,
        })
        log("DB", f"Created session {session_id[:8]} for {user_id}")
        return session_id

    def get_session(self, session_id: str) -> Optional[Dict]:
        result = self._rpc("get_diagnosis_session", {"p_id": session_id})
        if not result.data:
            log("DB", f"Session {session_id[:8]} not found")
            return None
        session = result.data
        if isinstance(session, str):
            session = json.loads(session)
        if session.get("conversation") and isinstance(session["conversation"], str):
            session["conversation"] = json.loads(session["conversation"])
        if session.get("candidates") and isinstance(session["candidates"], str):
            session["candidates"] = json.loads(session["candidates"])
        log("DB", f"Got session {session_id[:8]} phase={session.get('candidates',{}).get('phase','?') if isinstance(session.get('candidates'), dict) else session.get('candidates')}")
        return session

    def update_conversation(
        self,
        session_id: str,
        conversation: List[Dict],
        status: Optional[str] = None,
        candidates: Optional[Dict] = None,
    ):
        data = {"conversation": json.dumps(conversation)}
        if status:
            data["status"] = status
        if candidates:
            data["candidates"] = json.dumps(candidates)
        self.connect().table("diagnosis_sessions").update(data).eq("id", session_id).execute()
        log("DB", f"Updated session {session_id[:8]} status={status or '-'} candidates_keys={list(candidates.keys()) if candidates else 'none'}")

    def insert_question(
        self,
        session_id: str,
        question_index: int,
        question_jsonb: Dict,
    ) -> Optional[str]:
        try:
            result = self._rpc("insert_diagnosis_question", {
                "p_session_id": session_id,
                "p_question_index": question_index,
                "p_question_jsonb": json.dumps(question_jsonb),
            })
            qid = result.data
            if isinstance(qid, list) and qid:
                qid = qid[0]
            log("DB", f"Inserted question {qid} for session {session_id[:8]} idx={question_index}")
            return qid
        except Exception as e:
            log("DB", f"insert_question failed (non-fatal): {e}")
            return None

    def get_question(self, question_id: str) -> Optional[Dict]:
        result = self._rpc("get_diagnosis_question", {"p_id": question_id})
        if not result.data:
            return None
        q = result.data
        if isinstance(q, str):
            q = json.loads(q)
        return q

    def list_questions(self, session_id: str) -> List[Dict]:
        result = self._rpc("list_diagnosis_questions", {"p_session_id": session_id})
        if not result.data:
            return []
        data = result.data
        if isinstance(data, str):
            data = json.loads(data)
        return data if isinstance(data, list) else []

    def list_sessions_by_user(self, user_id: str) -> List[Dict]:
        result = (
            self.connect()
            .table("diagnosis_sessions")
            .select("id, user_id, created_at, updated_at, status, candidates")
            .eq("user_id", user_id)
            .eq("status", "completed")
            .order("created_at", desc=True)
            .execute()
        )
        if not result.data:
            return []
        rows = result.data
        if isinstance(rows, str):
            rows = json.loads(rows)
        return rows if isinstance(rows, list) else []

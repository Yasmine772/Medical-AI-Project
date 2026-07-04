import json
import os
import uuid
from typing import Any, Dict, List, Optional
from supabase import Client, create_client

from dotenv import load_dotenv
load_dotenv()


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
        return session_id

    def get_session(self, session_id: str) -> Optional[Dict]:
        result = self._rpc("get_diagnosis_session", {"p_id": session_id})
        if not result.data:
            return None
        session = result.data
        if isinstance(session, str):
            session = json.loads(session)
        if session.get("conversation") and isinstance(session["conversation"], str):
            session["conversation"] = json.loads(session["conversation"])
        if session.get("candidates") and isinstance(session["candidates"], str):
            session["candidates"] = json.loads(session["candidates"])
        return session

    def update_conversation(self, session_id: str, conversation: List[Dict]):
        self._rpc("update_diagnosis_session", {
            "p_id": session_id,
            "p_conversation": json.dumps(conversation),
        })

    def complete_session(
        self,
        session_id: str,
        conversation: List[Dict],
        diagnosis: Dict,
    ):
        self._rpc("complete_diagnosis_session", {
            "p_id": session_id,
            "p_conversation": json.dumps(conversation),
            "p_disease_name": diagnosis.get("disease_name", ""),
            "p_disease_name_ar": diagnosis.get("disease_name_ar", ""),
            "p_confidence": diagnosis.get("confidence", "Low"),
            "p_specialist": diagnosis.get("specialist", ""),
            "p_specialist_ar": diagnosis.get("specialist_ar", ""),
            "p_advice": diagnosis.get("advice", ""),
            "p_reasoning": diagnosis.get("reasoning", ""),
        })

    def get_user_history(self, user_id: str) -> List[Dict]:
        result = self._rpc("get_user_diagnosis_history", {"p_user_id": user_id})
        if not result.data:
            return []
        data = result.data
        if isinstance(data, str):
            data = json.loads(data)
        return data

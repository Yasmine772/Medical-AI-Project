import os
import numpy as np
from typing import Any, Dict, List, Optional
from supabase import Client, create_client

from dotenv import load_dotenv
load_dotenv()

from app.services.logger import log


class PgVectorClient:

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

    def _to_list(self, embedding: np.ndarray) -> list:
        return embedding.tolist() if hasattr(embedding, "tolist") else embedding

    def insert(self, record_id: str, document: str, embedding: np.ndarray,
               type: str, metadata: dict):
        payload = {
            "id": record_id,
            "document": document,
            "embedding": self._to_list(embedding),
            "type": type,
        }
        if type == "disease":
            payload.update({
                "name_en": metadata.get("name_en"),
                "name_ar": metadata.get("name_ar"),
                "severity": metadata.get("severity"),
                "severity_ar": metadata.get("severity_ar"),
                "specialist": metadata.get("specialist"),
                "specialist_ar": metadata.get("specialist_ar"),
                "symptoms_en": metadata.get("symptoms_en"),
                "symptoms_ar": metadata.get("symptoms_ar"),
            })
        elif type == "pdf":
            payload.update({
                "source": metadata.get("source"),
                "page": metadata.get("page"),
                "chunk_index": metadata.get("chunk_index"),
                "language": metadata.get("language"),
            })
        self.connect().table("embeddings").insert(payload).execute()
        log("VECTOR", f"Inserted {type} id={record_id[:12]}")

    def search(
        self,
        query_embedding: np.ndarray,
        limit: int = 5,
        filter_type: str | None = None,
    ) -> List[dict]:
        params = {
            "query_embedding": self._to_list(query_embedding),
            "match_count": limit,
            "filter_type": filter_type,
        }
        response = self._rpc("search_embeddings", params)
        results = response.data or []
        log("VECTOR", f"search type={filter_type} limit={limit} -> {len(results)} results")
        return results

    def count(self, filter_type: str | None = None) -> int:
        response = self._rpc("count_embeddings", {"filter_type": filter_type})
        if not response.data:
            return 0
        c = int(response.data)
        log("VECTOR", f"count type={filter_type} -> {c}")
        return c

    def delete_all(self):
        self.connect().table("embeddings").delete().neq("id", "").execute()
        log("VECTOR", "Deleted all embeddings")

    def close(self):
        self._supabase = None

    # ── internal ──

    def _rpc(self, function_name: str, params: Dict[str, Any] | None = None):
        return self.connect().rpc(function_name, params or {}).execute()

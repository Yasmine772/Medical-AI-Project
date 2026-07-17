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
            raise ValueError(
                "Set SUPABASE_URL and SUPABASE_KEY in the environment."
            )

        self._supabase: Optional[Client] = None

    def connect(self):
        if self._supabase is None:
            self._supabase = create_client(self.supabase_url, self.supabase_key)
        return self._supabase

    def _rpc(self, function_name: str, params: Dict[str, Any] | None = None):
        try:
            return self.connect().rpc(function_name, params or {}).execute()
        except Exception as e:
            reset_errors = ("RemoteProtocolError", "ConnectionReset", "StreamReset", "APIError")
            if any(name in type(e).__name__ or name in str(e) for name in reset_errors):
                log("SUPABASE", f"RPC {function_name} connection reset, reconnecting: {e}")
                self._supabase = None
                return self.connect().rpc(function_name, params or {}).execute()
            raise

    def ping(self) -> Any:
        response = self._rpc("ping_pgvector")
        return response.data

    def insert_disease(
        self,
        disease_id: str,
        document: str,
        embedding: np.ndarray,
        metadata: dict,
    ):
        payload = {
            "disease_id": disease_id,
            "doc": document,
            "query_embedding": embedding.tolist() if hasattr(embedding, "tolist") else embedding,
            "name_en": metadata.get("name_en"),
            "name_ar": metadata.get("name_ar"),
            "severity": metadata.get("severity"),
            "severity_ar": metadata.get("severity_ar"),
            "specialist": metadata.get("specialist"),
            "specialist_ar": metadata.get("specialist_ar"),
            "symptoms_en": metadata.get("symptoms_en"),
            "symptoms_ar": metadata.get("symptoms_ar"),
        }
        self._rpc("insert_disease_embedding", payload)
        return None

    def search(
        self,
        query_embedding: np.ndarray,
        limit: int = 5,
    ) -> List[dict]:
        response = self._rpc(
            "search_diseases",
            {
                "query_embedding": query_embedding.tolist()
                if hasattr(query_embedding, "tolist")
                else query_embedding,
                "match_count": limit,
            },
        )
        results = response.data or []
        log("VECTOR", f"search_diseases limit={limit} -> {len(results)} results")
        return results

    def count(self) -> int:
        response = self._rpc("count_disease_embeddings")
        if not response.data:
            return 0
        c = int(response.data)
        log("VECTOR", f"count_disease_embeddings -> {c}")
        return c

    def delete_all(self):
        client = self.connect()
        client.table("disease_embeddings").delete().neq("id", "").execute()
        log("VECTOR", f"Deleted all disease embeddings")
        return None

    def close(self):
        self._supabase = None
        return None

    # ── PDF chunks ─────────────────────────────────────────

    def insert_pdf_chunk(
        self,
        chunk_id: str,
        document: str,
        embedding: np.ndarray,
        metadata: dict,
    ):
        payload = {
            "chunk_id": chunk_id,
            "doc": document,
            "query_embedding": embedding.tolist()
            if hasattr(embedding, "tolist")
            else embedding,
            "source": metadata.get("source"),
            "page": metadata.get("page"),
            "chunk_index": metadata.get("chunk_index"),
            "language": metadata.get("language"),
        }
        self._rpc("insert_pdf_chunk", payload)
        log("VECTOR", f"Inserted pdf chunk {metadata.get('source')} p.{metadata.get('page')}")

    def search_pdf(
        self,
        query_embedding: np.ndarray,
        limit: int = 3,
    ) -> List[dict]:
        response = self._rpc(
            "search_pdf_chunks",
            {
                "query_embedding": query_embedding.tolist()
                if hasattr(query_embedding, "tolist")
                else query_embedding,
                "match_count": limit,
            },
        )
        results = response.data or []
        log("VECTOR", f"search_pdf_chunks limit={limit} -> {len(results)} results")
        return results

    def count_pdf(self) -> int:
        response = self._rpc("count_pdf_chunks")
        if not response.data:
            return 0
        c = int(response.data)
        log("VECTOR", f"count_pdf_chunks -> {c}")
        return c

import os
import numpy as np
from typing import Any, Dict, List, Optional
from supabase import Client, create_client

from dotenv import load_dotenv
load_dotenv()


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
        client = self.connect()
        return client.rpc(function_name, params or {}).execute()

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
        return response.data or []

    def count(self) -> int:
        response = self._rpc("count_disease_embeddings")
        if not response.data:
            return 0
        return int(response.data)

    def delete_all(self):
        self._rpc("delete_all_disease_embeddings")
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
        return response.data or []

    def count_pdf(self) -> int:
        response = self._rpc("count_pdf_chunks")
        if not response.data:
            return 0
        return int(response.data)

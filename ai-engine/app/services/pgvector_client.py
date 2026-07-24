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

    def _build_payload(self, record_id: str, document: str, embedding: list,
                       type: str, metadata: dict) -> dict:
        payload = {
            "id": record_id,
            "document": document,
            "embedding": embedding,
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
        return payload

    def insert(self, record_id: str, document: str, embedding: np.ndarray,
               type: str, metadata: dict):
        payload = self._build_payload(record_id, document, self._to_list(embedding),
                                      type, metadata)
        self.connect().table("embeddings").insert(payload).execute()

    def insert_batch(self, rows: list, batch_size: int = 500):
        """rows: list of (record_id, document, embedding_list, type, metadata)"""
        payloads = [
            self._build_payload(rid, doc, emb, typ, meta)
            for rid, doc, emb, typ, meta in rows
        ]
        for i in range(0, len(payloads), batch_size):
            self.connect().table("embeddings").upsert(payloads[i:i + batch_size], on_conflict='id').execute()

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
        return response.data or []

    def count(self, filter_type: str | None = None) -> int:
        if filter_type is not None:
            response = self.connect().table("embeddings").select("id", count="exact").eq("type", filter_type).execute()
            c = response.count or 0
        else:
            response = self._rpc("count_embeddings", {"filter_type": None})
            c = int(response.data) if response.data else 0
        return c

    def delete_all(self):
        self.connect().table("embeddings").delete().neq("id", "").execute()

    def close(self):
        self._supabase = None

    # ── PDF chunks (legacy, uses embeddings table internally) ──

    def insert_pdf_chunk(
        self,
        chunk_id: str,
        document: str,
        embedding: np.ndarray,
        metadata: dict,
    ):
        self.insert(chunk_id, document, embedding, "pdf", metadata)
        log("VECTOR", f"Inserted pdf chunk {metadata.get('source')} p.{metadata.get('page')}")

    def search_pdf(
        self,
        query_embedding: np.ndarray,
        limit: int = 3,
    ) -> List[dict]:
        return self.search(query_embedding, limit=limit, filter_type="pdf")

    def count_pdf(self) -> int:
        return self.count(filter_type="pdf")

    # ── internal ──

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

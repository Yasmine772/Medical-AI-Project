from app.services.pgvector_client import PgVectorClient
from app.services.embedding_service import EmbeddingService

_store: PgVectorClient = None
_embedder: EmbeddingService = None


def init(store: PgVectorClient, embedder: EmbeddingService):
    global _store, _embedder
    _store = store
    _embedder = embedder


def get_store() -> PgVectorClient:
    return _store


def get_embedder() -> EmbeddingService:
    return _embedder

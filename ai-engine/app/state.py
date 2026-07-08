from app.services.pgvector_client import PgVectorClient
from app.services.embedding_service import EmbeddingService
from app.services.session_manager import SessionManager

_store: PgVectorClient = None
_embedder: EmbeddingService = None
_session_mgr: SessionManager = None


def init(store: PgVectorClient, embedder: EmbeddingService):
    global _store, _embedder, _session_mgr
    _store = store
    _embedder = embedder
    _session_mgr = SessionManager()
    _session_mgr.connect()


def get_store() -> PgVectorClient:
    return _store


def get_embedder() -> EmbeddingService:
    return _embedder


def get_session_manager() -> SessionManager:
    return _session_mgr

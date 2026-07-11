from threading import Lock
from app.services.logger import log
from app.services.pgvector_client import PgVectorClient
from app.services.embedding_service import EmbeddingService
from app.services.session_manager import SessionManager
from app.services.llm_service import LLMService


_store: PgVectorClient = None
_embedder: EmbeddingService = None
_session_mgr: SessionManager = None
_llm: LLMService = None

_insert_tasks: dict = {}
_insert_tasks_lock = Lock()


def init(store: PgVectorClient, embedder: EmbeddingService):
    global _store, _embedder, _session_mgr, _llm
    _store = store
    _embedder = embedder
    _session_mgr = SessionManager()
    _session_mgr.connect()
    _llm = LLMService()
    log("INIT", "Services initialized")

def get_store() -> PgVectorClient:
    return _store


def get_embedder() -> EmbeddingService:
    return _embedder


def get_session_manager() -> SessionManager:
    return _session_mgr


def get_llm() -> LLMService:
    return _llm

def set_insert_progress(task_id: str, status: str, current: int = 0, total: int = 0, result: dict = None):
    with _insert_tasks_lock:
        _insert_tasks[task_id] = {"status": status, "current": current, "total": total, "result": result}

def get_insert_progress(task_id: str) -> dict:
    with _insert_tasks_lock:
        return _insert_tasks.get(task_id)

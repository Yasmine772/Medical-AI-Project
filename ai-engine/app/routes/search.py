from fastapi import APIRouter, Query
from app.state import get_store, get_embedder

router = APIRouter()


@router.get("/search")
async def search(q: str = Query(""), limit: int = Query(5)):
    if not q:
        return {"results": []}
    store = get_store()
    embedder = get_embedder()
    query_vector = embedder.encode(q)
    results = store.search(query_vector, limit=limit)
    return {"results": results}

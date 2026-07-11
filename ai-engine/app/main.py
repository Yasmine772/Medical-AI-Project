from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import uvicorn

from app.routes.health import router as health_router
from app.routes.search import router as search_router
from app.routes.insert import router as insert_router
from app.routes.diagnosis import router as diagnosis_router
from app.routes.report import router as report_router
from app.state import init
from app.services.pgvector_client import PgVectorClient
from app.services.embedding_service import EmbeddingService

app = FastAPI(
    title="Medical Diagnosis AI",
    description="\u062e\u062f\u0645\u0629 \u0627\u0644\u0630\u0643\u0627\u0621 \u0627\u0644\u0627\u0635\u0637\u0646\u0627\u0639\u064a \u0644\u0644\u062a\u0634\u062e\u064a\u0635 \u0627\u0644\u0637\u0628\u064a",
    version="0.2.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(health_router)
app.include_router(search_router)
app.include_router(insert_router)

app.include_router(report_router)
app.include_router(diagnosis_router)


@app.on_event("startup")
async def startup():
    store = PgVectorClient()
    store.connect()
    embedder = EmbeddingService()
    init(store, embedder)
    print(f"Connected to Supabase pgvector. Existing records: {store.count()}")


@app.on_event("shutdown")
async def shutdown():
    from app.state import get_store
    store = get_store()
    if store:
        store.close()


if __name__ == "__main__":
    uvicorn.run("app.main:app", host="0.0.0.0", port=5000, reload=True)

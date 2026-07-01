from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import uvicorn

from app.services.pgvector_client import PgVectorClient
from app.services.embedding_service import EmbeddingService


app = FastAPI(
    title="Medical Diagnosis AI",
    description="خدمة الذكاء الاصطناعي للتشخيص الطبي",
    version="0.1.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

store: PgVectorClient = None
embedder: EmbeddingService = None


@app.on_event("startup")
async def startup():
    global store, embedder
    store = PgVectorClient()
    store.connect()
    store.create_table()
    embedder = EmbeddingService()
    print(f"Connected to Supabase pgvector. Existing records: {store.count()}")


@app.on_event("shutdown")
async def shutdown():
    if store:
        store.close()


@app.get("/")
async def root():
    return {"message": "Medical AI Service is running!"}


@app.get("/health")
async def health_check():
    return {
        "status": "healthy",
        "service": "FastAPI",
        "records": store.count() if store else 0,
    }


if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=5000)
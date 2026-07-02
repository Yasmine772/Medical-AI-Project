from fastapi import APIRouter
from app.state import get_store

router = APIRouter()


@router.get("/")
async def root():
    return {"message": "Medical AI Service is running!"}


@router.get("/health")
async def health_check():
    store = get_store()
    count = store.count() if store else 0
    return {"status": "healthy", "service": "FastAPI", "records": count}

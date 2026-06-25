from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import uvicorn

# إنشاء تطبيق FastAPI
app = FastAPI(
    title="Medical Diagnosis AI",
    description="خدمة الذكاء الاصطناعي للتشخيص الطبي",
    version="0.1.0"
)

# إضافة CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# نقطة الترحيب
@app.get("/")
async def root():
    return {"message": "Medical AI Service is running!"}

# نقطة التحقق من الصحة
@app.get("/health")
async def health_check():
    return {"status": "healthy", "service": "FastAPI"}

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000)
import json
import uuid
from fastapi import APIRouter, File, UploadFile, HTTPException
import fitz

from app.models.schemas import DiseaseItem
from app.state import get_store, get_embedder

router = APIRouter()


def _insert_disease(disease: DiseaseItem):
    store = get_store()
    embedder = get_embedder()
    disease_id = disease.id or str(uuid.uuid4())
    symptoms_ar = "، ".join(disease.symptoms_ar)
    document = f"""
المرض: {disease.name_ar}
الأعراض: {symptoms_ar}
الشدة: {disease.severity_ar}
الوصف: {disease.description}
النصيحة: {disease.advice}
التخصص الطبي: {disease.specialist_ar}
""".strip()
    embedding = embedder.encode(document)
    metadata = {
        "name_en": disease.name,
        "name_ar": disease.name_ar,
        "severity": disease.severity,
        "severity_ar": disease.severity_ar,
        "specialist": disease.specialist,
        "specialist_ar": disease.specialist_ar,
        "symptoms_en": ", ".join(disease.symptoms),
        "symptoms_ar": ", ".join(disease.symptoms_ar),
    }
    store.insert_disease(disease_id, document, embedding, metadata)


@router.post("/insert/json-file")
async def insert_json_file(file: UploadFile = File(...)):
    if not file.filename or not file.filename.endswith(".json"):
        raise HTTPException(400, "Only .json files are accepted")

    content = await file.read()
    data = json.loads(content)

    if isinstance(data, list):
        diseases = [DiseaseItem(**d) for d in data]
    elif isinstance(data, dict) and "diseases" in data:
        diseases = [DiseaseItem(**d) for d in data["diseases"]]
    else:
        raise HTTPException(400, "JSON must be an array of diseases or {diseases: [...]}")

    for disease in diseases:
        _insert_disease(disease)

    return {"filename": file.filename, "added": len(diseases)}


@router.post("/insert/pdf")
async def insert_pdf(file: UploadFile = File(...)):
    if not file.filename or not file.filename.endswith(".pdf"):
        raise HTTPException(400, "Only PDF files are accepted")

    store = get_store()
    embedder = get_embedder()

    content = await file.read()
    doc = fitz.open(stream=content, filetype="pdf")

    added = 0
    for page_num in range(len(doc)):
        page = doc[page_num]
        text = page.get_text().strip()
        if not text:
            continue

        chunk_size = 500
        words = text.split()
        for chunk_idx in range(0, len(words), chunk_size):
            chunk_text = " ".join(words[chunk_idx : chunk_idx + chunk_size])
            chunk_id = f"{file.filename}-p{page_num}-c{chunk_idx // chunk_size}"
            embedding = embedder.encode(chunk_text)
            metadata = {
                "name_en": file.filename,
                "name_ar": file.filename,
                "severity": "",
                "severity_ar": "",
                "specialist": "en",
                "specialist_ar": "en",
                "symptoms_en": f"source={file.filename};page={page_num};chunk={chunk_idx // chunk_size}",
                "symptoms_ar": f"source={file.filename};page={page_num};chunk={chunk_idx // chunk_size}",
            }
            store.insert_disease(chunk_id, chunk_text, embedding, metadata)
            added += 1

    page_count = len(doc)
    doc.close()
    return {"filename": file.filename, "pages": page_count, "chunks_added": added}

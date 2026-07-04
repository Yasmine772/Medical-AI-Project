import hashlib
import json
import uuid
from fastapi import APIRouter, File, UploadFile, HTTPException
import fitz
from langchain_text_splitters import RecursiveCharacterTextSplitter

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
        raise HTTPException(
            400, "JSON must be an array of diseases or {diseases: [...]}"
        )

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

    splitter = RecursiveCharacterTextSplitter(
        chunk_size=500,
        chunk_overlap=80,
        separators=["\n\n", "\n", "، ", ". ", "؟ ", "! ", " "],
    )

    added = 0
    for page_num in range(len(doc)):
        page = doc[page_num]
        text = page.get_text().strip()
        if not text:
            continue

        chunks = splitter.split_text(text)
        for idx, chunk_text in enumerate(chunks):
            chunk_text = chunk_text.strip()
            if not chunk_text:
                continue
            raw_id = f"pdf_api_{file.filename}_p{page_num}_c{idx}"
            chunk_id = hashlib.md5(raw_id.encode()).hexdigest()[:16]
            embedding = embedder.encode(chunk_text)
            metadata = {
                "source": file.filename,
                "page": page_num,
                "chunk_index": idx,
                "chunk_size": len(chunk_text),
                "language": None,
            }
            store.insert_pdf_chunk(chunk_id, chunk_text, embedding, metadata)
            added += 1

    page_count = len(doc)
    doc.close()
    return {"filename": file.filename, "pages": page_count, "chunks_added": added}

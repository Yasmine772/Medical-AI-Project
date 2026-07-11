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
    store.insert(disease_id, document, embedding, "disease", metadata)


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
        chunk_overlap=100,
        separators=["\n\n", "\n", "، ", ". ", "؟ ", "! ", " "],
    )

    batch_rows = []
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
            metadata = {
                "source": file.filename,
                "page": page_num,
                "chunk_index": idx,
                "language": None,
            }
            batch_rows.append((chunk_id, chunk_text, metadata))

    page_count = len(doc)
    doc.close()

    if not batch_rows:
        return {"filename": file.filename, "pages": page_count, "chunks_added": 0}

    texts = [row[1] for row in batch_rows]
    embeddings = embedder.encode_batch(texts)

    store_rows = []
    for (chunk_id, chunk_text, meta), emb in zip(batch_rows, embeddings):
        store_rows.append((chunk_id, chunk_text, emb.tolist(), "pdf", meta))

    store.insert_batch(store_rows)
    return {"filename": file.filename, "pages": page_count, "chunks_added": len(batch_rows)}

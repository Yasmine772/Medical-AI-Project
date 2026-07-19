import hashlib
import json
import uuid
import threading
from fastapi import APIRouter, File, UploadFile, HTTPException
import fitz
from langchain_text_splitters import RecursiveCharacterTextSplitter

from app.models.schemas import DiseaseItem
from app.state import get_store, get_embedder, set_insert_progress, get_insert_progress

router = APIRouter()

# Serialize PDF insertion so only one PDF is processed at a time (avoids
# spawning a thread per file and burning RAM when many are uploaded at once).
_pdf_lock = threading.Lock()


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


def _process_pdf(task_id: str, content: bytes, filename: str):
    try:
        store = get_store()
        embedder = get_embedder()

        doc = fitz.open(stream=content, filetype="pdf")

        splitter = RecursiveCharacterTextSplitter(
            chunk_size=500,
            chunk_overlap=100,
            separators=["\n\n", "\n", "، ", ". ", "؟ ", "! ", " "],
        )

        set_insert_progress(task_id, "extracting", 0, 1)
        batch_rows = []
        total_pages = len(doc)
        for page_num in range(total_pages):
            text = doc[page_num].get_text().strip()
            if not text:
                continue
            chunks = splitter.split_text(text)
            for idx, chunk_text in enumerate(chunks):
                chunk_text = chunk_text.strip()
                if not chunk_text:
                    continue
                raw_id = f"pdf_api_{filename}_p{page_num}_c{idx}"
                chunk_id = hashlib.md5(raw_id.encode()).hexdigest()[:16]
                metadata = {
                    "source": filename,
                    "page": page_num,
                    "chunk_index": idx,
                    "language": None,
                }
                batch_rows.append((chunk_id, chunk_text, metadata))

        page_count = total_pages
        doc.close()

        if not batch_rows:
            set_insert_progress(task_id, "done", 0, 0, result={"filename": filename, "pages": page_count, "chunks_added": 0})
            return

        total_chunks = len(batch_rows)
        texts = [row[1] for row in batch_rows]

        set_insert_progress(task_id, "encoding", 0, total_chunks)
        batch_size = 128
        embeddings = []
        for i in range(0, total_chunks, batch_size):
            batch_texts = texts[i:i + batch_size]
            batch_embs = embedder.encode_batch(batch_texts, batch_size=batch_size)
            embeddings.extend(batch_embs)
            set_insert_progress(task_id, "encoding", min(i + batch_size, total_chunks), total_chunks)

        store_rows = []
        for (chunk_id, chunk_text, meta), emb in zip(batch_rows, embeddings):
            store_rows.append((chunk_id, chunk_text, emb.tolist(), "pdf", meta))

        set_insert_progress(task_id, "inserting", 0, total_chunks)
        insert_bs = 500
        for i in range(0, len(store_rows), insert_bs):
            store.insert_batch(store_rows[i:i + insert_bs], batch_size=insert_bs)
            set_insert_progress(task_id, "inserting", min(i + insert_bs, total_chunks), total_chunks)

        result = {"filename": filename, "pages": page_count, "chunks_added": total_chunks}
        set_insert_progress(task_id, "done", total_chunks, total_chunks, result=result)
    except Exception as ex:
        set_insert_progress(task_id, "error", 0, 0, result={"error": str(ex)})


@router.post("/insert/pdf")
async def insert_pdf(file: UploadFile = File(...)):
    if not file.filename or not file.filename.endswith(".pdf"):
        raise HTTPException(400, "Only PDF files are accepted")

    content = await file.read()
    task_id = str(uuid.uuid4())
    set_insert_progress(task_id, "starting", 0, 0)

    def _run():
        with _pdf_lock:
            _process_pdf(task_id, content, file.filename)

    thread = threading.Thread(target=_run, daemon=True)
    thread.start()

    return {"task_id": task_id, "status": "started", "filename": file.filename}


@router.get("/insert/progress/{task_id}")
async def insert_progress(task_id: str):
    task = get_insert_progress(task_id)
    if task is None:
        raise HTTPException(404, "Task not found")
    return {"task_id": task_id, **task}

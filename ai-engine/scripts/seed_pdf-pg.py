"""
seed_pdf-pg.py
--------------
Reads one or more medical PDF files, extracts text,
splits it into overlapping chunks, and stores the chunks in Supabase through RPC calls.

Usage:
    python scripts/seed_pdf-pg.py --path data/report.pdf
    python scripts/seed_pdf-pg.py --path data/pdfs/
"""

import argparse
import hashlib
import os
import sys
import unicodedata
from pathlib import Path

import fitz
import pdfplumber

sys.path.append(str(Path(__file__).parent.parent))

from app.services.embedding_service import EmbeddingService
from app.services.pgvector_client import PgVectorClient


CHUNK_SIZE = 500
CHUNK_OVERLAP = 80
MIN_CHUNK_LENGTH = 80
CHUNK_ID_VERSION = "pdfv3"


def normalize_pdf_text(text: str) -> str:
    if not text:
        return ""

    cleaned_lines = []
    for line in text.splitlines():
        stripped = unicodedata.normalize("NFC", line).replace("\u200f", "").replace("\u200e", "").strip()
        cleaned_lines.append(stripped)

    return "\n".join(cleaned_lines)


def extract_text_from_pdf(pdf_path: str) -> list[dict]:
    pages = []

    # PyMuPDF preserves Arabic reading order better than pdfplumber for this PDF.
    try:
        doc = fitz.open(pdf_path)
        for i, page in enumerate(doc):
            raw_text = page.get_text("text") or ""
            text = normalize_pdf_text(raw_text).strip()
            if text:
                pages.append({"page": i + 1, "text": text})
        doc.close()
    except Exception as error:
        print(f"   ⚠️  PyMuPDF failed: {error} — switching to pdfplumber")

    if not pages:
        try:
            with pdfplumber.open(pdf_path) as pdf:
                for i, page in enumerate(pdf.pages):
                    raw_text = page.extract_text() or ""
                    text = normalize_pdf_text(raw_text).strip()
                    if text:
                        pages.append({"page": i + 1, "text": text})
        except Exception as error:
            print(f"   ❌ pdfplumber also failed: {error}")

    return pages


def chunk_text(text: str, source: str, page: int) -> list[dict]:
    chunks = []
    start = 0
    idx = 0

    while start < len(text):
        end = start + CHUNK_SIZE
        chunk = text[start:end].strip()

        if len(chunk) >= MIN_CHUNK_LENGTH:
            raw_id = f"{CHUNK_ID_VERSION}_{source}_page{page}_chunk{idx}"
            chunk_id = hashlib.md5(raw_id.encode()).hexdigest()[:16]

            chunks.append(
                {
                    "id": chunk_id,
                    "document": chunk,
                    "metadata": {
                        "source": source,
                        "page": page,
                        "chunk_index": idx,
                        "chunk_size": len(chunk),
                        "language": detect_language(chunk),
                    },
                }
            )
            idx += 1

        start += CHUNK_SIZE - CHUNK_OVERLAP

    return chunks


def detect_language(text: str) -> str:
    arabic_chars = sum(1 for c in text if "\u0600" <= c <= "\u06FF")
    english_chars = sum(1 for c in text if c.isascii() and c.isalpha())
    total = arabic_chars + english_chars

    if total == 0:
        return "unknown"

    arabic_ratio = arabic_chars / total
    if arabic_ratio > 0.75:
        return "ar"
    if arabic_ratio < 0.25:
        return "en"
    return "mixed"


def build_metadata(chunk: dict) -> dict:
    meta = chunk["metadata"]
    return {
        "name_en": meta["source"],
        "name_ar": f"page {meta['page']} chunk {meta['chunk_index']}",
        "severity": "pdf_chunk",
        "severity_ar": f"page {meta['page']}",
        "specialist": meta["language"],
        "specialist_ar": meta["language"],
        "symptoms_en": f"chunk_size={meta['chunk_size']}",
        "symptoms_ar": f"source={meta['source']};page={meta['page']};chunk={meta['chunk_index']}",
    }


def seed_pdfs(input_path: str):
    path = Path(input_path)

    if path.is_file() and path.suffix.lower() == ".pdf":
        pdf_files = [path]
    elif path.is_dir():
        pdf_files = list(path.glob("**/*.pdf"))
    else:
        print(f"❌ Invalid path: {input_path}")
        sys.exit(1)

    if not pdf_files:
        print(f"❌ No PDF files found in: {input_path}")
        sys.exit(1)

    print(f"\n📑 Found {len(pdf_files)} PDF file(s)")

    print("🔌 Connecting to Supabase RPC...")
    client = PgVectorClient()
    client.connect()
    print(f"✅ Ping: {client.ping()}")

    embedder = EmbeddingService()

    total_attempted = 0

    for pdf_path in pdf_files:
        print(f"\n📄 Processing: {pdf_path.name}")

        pages = extract_text_from_pdf(str(pdf_path))
        if not pages:
            print("   ⚠️  No text extracted — skipping (may be a scanned image PDF)")
            continue

        print(f"   📃 Extracted {len(pages)} page(s)")

        all_chunks = []
        for page_data in pages:
            all_chunks.extend(
                chunk_text(
                    text=page_data["text"],
                    source=pdf_path.name,
                    page=page_data["page"],
                )
            )

        print(
            f"   ✂️  Created {len(all_chunks)} chunk(s) "
            f"(chunk size: {CHUNK_SIZE} chars, overlap: {CHUNK_OVERLAP})"
        )

        if not all_chunks:
            print("   ⚠️  No usable chunks found — skipping")
            continue

        documents = [chunk["document"] for chunk in all_chunks]
        embeddings = embedder.encode_batch(documents, batch_size=32)

        for chunk, embedding in zip(all_chunks, embeddings):
            client.insert_disease(
                disease_id=chunk["id"],
                document=chunk["document"],
                embedding=embedding,
                metadata=build_metadata(chunk),
            )
            total_attempted += 1

        print(f"   ✅ Added: {len(all_chunks)}")

    print(f"""
────────────────────────────────
✅ PDF Seeding complete!
   Files processed: {len(pdf_files)}
   Chunks attempted: {total_attempted}
    Added: {total_attempted}
────────────────────────────────
""")

    if total_attempted > 0:
        print("🧪 Running a quick search test...")
        test_embedding = embedder.encode("حكة وطفح جلدي")
        results = client.search(test_embedding, limit=2)
        print("   Closest match for 'حكة وطفح جلدي':")
        for index, row in enumerate(results, start=1):
            preview = (row.get("document") or "")[:80].replace("\n", " ")
            print(f"   {index}. [{row.get('id', '')}] {preview}...")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Seed Supabase pgvector from medical PDF files")
    parser.add_argument(
        "--path",
        required=True,
        help="Path to a single PDF file or a folder containing PDF files",
    )
    args = parser.parse_args()
    seed_pdfs(args.path)
# This script reads one or more medical PDF files, extracts text,
# splits it into overlapping chunks, and inserts each chunk into ChromaDB
# The script handles Arabic, English, or mixed-language PDFs, including plain text reports, tables, and mixed layouts. It can process multiple PDF files in one run.
# To use the script, run it from the command line with the path to a single PDF file or a folder containing PDF files as an argument. For example to test:
#  python scripts/seed_pdf.py --path "app/data/diseasesPdf.pdf"

"""
seed_pdf.py
-----------
Reads one or more medical PDF files, extracts text,
splits it into overlapping chunks, and inserts each chunk
into ChromaDB as a searchable document.

Handles:
  - Arabic, English, or mixed-language PDFs
  - Plain text reports, tables, and mixed layouts
  - Multiple PDF files in one run

Usage:
    # Single file
    python scripts/seed_pdf.py --path data/report.pdf

    # Entire folder
    python scripts/seed_pdf.py --path data/pdfs/
"""

import os
import sys
import argparse
import hashlib
from pathlib import Path
import arabic_reshaper
from bidi.algorithm import get_display
import fitz          
import pdfplumber    

# Add project root to Python path
sys.path.append(str(Path(__file__).parent.parent))
from app.services.chroma_client import ChromaClient

# ─────────────────────────────────────────────────────────
# Config
# ─────────────────────────────────────────────────────────
CHROMA_PERSIST_DIR = "./chroma_db"
COLLECTION_NAME    = "medical_diseases"

# Chunking settings
CHUNK_SIZE    = 500   # characters per chunk
CHUNK_OVERLAP = 80    # overlap between consecutive chunks
                      # overlap prevents a sentence from being cut in half
                      # between two chunks, which would hurt search quality

# Minimum chunk length — discard chunks that are too short to be useful
MIN_CHUNK_LENGTH = 80


# ─────────────────────────────────────────────────────────
# Step 1 — Extract text from PDF
# ─────────────────────────────────────────────────────────

def extract_text_from_pdf(pdf_path: str) -> list[dict]:
    """
    Extracts text page-by-page from a PDF file.

    Strategy:
      - Try pdfplumber first — it handles tables and mixed layouts better
      - Fall back to PyMuPDF if pdfplumber returns empty text
        (some PDFs are easier for PyMuPDF to parse)
      - Both handle Arabic, English, and mixed-language content

    Args:
        pdf_path: path to the PDF file

    Returns:
        list of dicts: [{"page": 1, "text": "..."}, ...]
    """
    pages = []

    def fix_arabic_text(text: str) -> str:
        reshaped_text = arabic_reshaper.reshape(text)
        return get_display(reshaped_text)

    # ── Try pdfplumber first ──────────────────────────────
    try:
        with pdfplumber.open(pdf_path) as pdf:
            for i, page in enumerate(pdf.pages):
                raw_text = page.extract_text() or ""
                text = fix_arabic_text(raw_text).strip()
                if text:
                    pages.append({"page": i + 1, "text": text})
    except Exception as e:
        print(f"   ⚠️  pdfplumber failed: {e} — switching to PyMuPDF")

    # ── Fall back to PyMuPDF if pdfplumber got nothing ───
    if not pages:
        try:
            doc = fitz.open(pdf_path)
            for i, page in enumerate(doc):
                raw_text = page.get_text("text") or ""
                text = fix_arabic_text(raw_text).strip()
                if text:
                    pages.append({"page": i + 1, "text": text})
            doc.close()
        except Exception as e:
            print(f"   ❌ PyMuPDF also failed: {e}")

    return pages
# ─────────────────────────────────────────────────────────
# Step 2 — Split text into overlapping chunks
# ─────────────────────────────────────────────────────────
def chunk_text(text: str, source: str, page: int) -> list[dict]:
    """
    Splits a page's text into overlapping chunks.

    Why overlap?
      If a sentence starts at character 490 and CHUNK_SIZE is 500,
      without overlap it would be cut between two chunks and neither
      chunk would carry the full meaning.
      Overlap ensures every sentence appears complete in at least one chunk.

    Args:
        text:   the full text of one page
        source: filename, used in metadata
        page:   page number, used in metadata

    Returns:
        list of dicts: each dict is one chunk ready for ChromaDB
    """
    reshaped_text = arabic_reshaper.reshape(text)
    # for proper display of Arabic text in LTR environments, we need to apply the bidi algorithm
    text = get_display(reshaped_text)
    chunks = []
    start  = 0
    idx    = 0

    while start < len(text):
        end   = start + CHUNK_SIZE
        chunk = text[start:end].strip()

        # Skip chunks that are too short to be meaningful
        if len(chunk) >= MIN_CHUNK_LENGTH:

            # Generate a stable unique ID for this chunk
            # md5 of (source + page + chunk index) — avoids duplicates on re-runs
            raw_id    = f"{source}_page{page}_chunk{idx}"
            chunk_id  = hashlib.md5(raw_id.encode()).hexdigest()[:16]

            chunks.append({
                "id":       chunk_id,
                "document": chunk,
                "metadata": {
                    "source":      source,          # filename
                    "page":        page,            # page number (int)
                    "chunk_index": idx,             # position within the page
                    "chunk_size":  len(chunk),      # actual character count
                    "language":    detect_language(chunk),  # "ar", "en", or "mixed"
                }
            })
            idx += 1

        # Move forward by (CHUNK_SIZE - CHUNK_OVERLAP) to create the overlap
        start += CHUNK_SIZE - CHUNK_OVERLAP

    return chunks


def detect_language(text: str) -> str:
    """
    Simple heuristic to detect whether text is Arabic, English, or mixed.
    Used to populate the 'language' metadata field for filtering later.

    Args:
        text: any string

    Returns:
        "ar", "en", or "mixed"
    """
    arabic_chars  = sum(1 for c in text if "\u0600" <= c <= "\u06FF")
    english_chars = sum(1 for c in text if c.isascii() and c.isalpha())
    total         = arabic_chars + english_chars

    if total == 0:
        return "unknown"

    arabic_ratio = arabic_chars / total

    if arabic_ratio > 0.75:
        return "ar"
    elif arabic_ratio < 0.25:
        return "en"
    else:
        return "mixed"


# ─────────────────────────────────────────────────────────
# Step 3 — Insert chunks into ChromaDB
# ─────────────────────────────────────────────────────────
def insert_chunks(collection, chunks: list[dict]) -> tuple[int, int]:
    """
    Inserts a list of chunks into ChromaDB, skipping duplicates.

    Args:
        collection: ChromaDB collection object
        chunks:     list of chunk dicts from chunk_text()

    Returns:
        (added, skipped) counts
    """
    added   = 0
    skipped = 0

    # Check all IDs at once to avoid one-by-one DB calls
    all_ids      = [c["id"] for c in chunks]
    existing     = collection.get(ids=all_ids)
    existing_ids = set(existing["ids"])

    new_chunks = [c for c in chunks if c["id"] not in existing_ids]
    skipped    = len(chunks) - len(new_chunks)

    if new_chunks:
        collection.add(
            ids       = [c["id"]       for c in new_chunks],
            documents = [c["document"] for c in new_chunks],
            metadatas = [c["metadata"] for c in new_chunks],
            # ChromaDB generates embeddings automatically from `documents`
        )
        added = len(new_chunks)

    return added, skipped


# ─────────────────────────────────────────────────────────
# Main
# ─────────────────────────────────────────────────────────
def seed_pdfs(input_path: str):
    """
    Main entry point.
    Accepts either a single PDF file or a directory of PDFs.

    Args:
        input_path: path to a .pdf file or a folder containing .pdf files
    """

    # ── Collect PDF paths ─────────────────────────────────
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

    # ── Connect to ChromaDB ───────────────────────────────
    print(f"🔌 Connecting to ChromaDB...")
    chroma     = ChromaClient(persist_directory=CHROMA_PERSIST_DIR)
    collection = chroma.get_or_create_collection(COLLECTION_NAME)

    # ── Process each PDF ──────────────────────────────────
    total_added   = 0
    total_skipped = 0

    for pdf_path in pdf_files:
        print(f"\n📄 Processing: {pdf_path.name}")

        # Step 1: Extract text page by page
        pages = extract_text_from_pdf(str(pdf_path))

        if not pages:
            print(f"   ⚠️  No text extracted — skipping (may be a scanned image PDF)")
            continue

        print(f"   📃 Extracted {len(pages)} page(s)")

        # Step 2: Chunk each page and collect all chunks
        all_chunks = []
        for page_data in pages:
            page_chunks = chunk_text(
                text   = page_data["text"],
                source = pdf_path.name,
                page   = page_data["page"],
            )
            all_chunks.extend(page_chunks)

        print(f"   ✂️  Created {len(all_chunks)} chunk(s) "
              f"(chunk size: {CHUNK_SIZE} chars, overlap: {CHUNK_OVERLAP})")

        # Step 3: Insert into ChromaDB
        added, skipped = insert_chunks(collection, all_chunks)
        total_added   += added
        total_skipped += skipped

        print(f"   ✅ Added: {added}  |  Skipped (duplicates): {skipped}")

    # ── Summary ───────────────────────────────────────────
    print(f"""
────────────────────────────────
✅ PDF Seeding complete!
   Files processed: {len(pdf_files)}
   Chunks added:    {total_added}
   Chunks skipped:  {total_skipped} (already existed)
   Total in ChromaDB: {collection.count()} records
────────────────────────────────
""")

    # ── Quick search test ─────────────────────────────────
    if total_added > 0:
        print("🧪 Running a quick search test...")
        results = collection.query(
            query_texts=["حكة وطفح جلدي"],
            n_results=2,
            # Optional: filter by language
            # where={"language": "ar"}
        )
        print("   Closest match for 'حكة وطفح جلدي':")
        for i, (doc_id, doc) in enumerate(
            zip(results["ids"][0], results["documents"][0])
        ):
            preview = doc[:80].replace("\n", " ")
            print(f"   {i+1}. [{doc_id}] {preview}...")


# ─────────────────────────────────────────────────────────
# Entry point
# ─────────────────────────────────────────────────────────
if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Seed ChromaDB from medical PDF files")
    parser.add_argument(
        "--path",
        required=True,
        help="Path to a single PDF file or a folder containing PDF files"
    )
    args = parser.parse_args()
    seed_pdfs(args.path)

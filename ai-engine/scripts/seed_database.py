# To test this script, run the following command in your terminal:
# cd ai-engine && python scripts/seed_database.py
# ensure that you have the required dependencies from requirements.txt installed in your Python environment.

"""
seed_database.py
----------------
Reads diseases.json, builds a text document for each disease,
and inserts it into ChromaDB with metadata.


"""

import json
import os
import sys
from pathlib import Path
import httpx

sys.path.append(str(Path(__file__).parent.parent))

from app.services.chroma_client import ChromaClient

httpx.DEFAULT_TIMEOUT_CONFIG = httpx.Timeout(600.0)

# ─────────────────────────────────────────────
# Config 
# ─────────────────────────────────────────────
DISEASES_JSON_PATH = "./app/data/diseases.json"
CHROMA_PERSIST_DIR = "./chroma_db"
COLLECTION_NAME    = "medical_diseases"
BATCH_SIZE         = 30   # number of diseases added to ChromaDB per batch


# ─────────────────────────────────────────────
# Core function: convert a disease dict into a single text document
# ─────────────────────────────────────────────
def build_document(disease: dict) -> str:
    """
    Converts a disease object from JSON into a single Arabic text string.

    Why merge all fields into one text?
    - The embedding model understands full sentences better than separate lists
    - The patient's everyday words (e.g. "itching", "rash") must exist in the
      document so the model can find the semantic similarity with the query

    Args:
        disease: a single disease dict from the JSON file

    Returns:
        str: a complete text string ready for embedding
    """
    # Join Arabic symptoms into a comma-separated string
    symptoms_ar = "، ".join(disease.get("symptoms_ar", []))

    # Build the document in Arabic — patients will query in Arabic
    document = f"""
المرض: {disease.get('name_ar', '')}
الأعراض: {symptoms_ar}
الشدة: {disease.get('severity_ar', '')}
الوصف: {disease.get('description', '')}
الاحتمالية: {disease.get('likelihoods', '')}
النصيحة: {disease.get('advice', '')}
التخصص الطبي: {disease.get('specialist_ar', '')}
""".strip()

    return document


def build_metadata(disease: dict) -> dict:
    """
    Builds the metadata dict from a disease object.

    Metadata is used later for filtering — for example:
        collection.query(where={"specialist": "Dermatologist"})

    Note: ChromaDB only accepts str, int, float, bool values.
          Lists and nested dicts are not supported.

    Args:
        disease: a single disease dict from the JSON file

    Returns:
        dict: metadata ready for ChromaDB
    """
    return {
        "disease_id":    str(disease.get("id", "")),
        "name_en":       disease.get("name", ""),
        "name_ar":       disease.get("name_ar", ""),
        "severity":      disease.get("severity", ""),
        "severity_ar":   disease.get("severity_ar", ""),
        "specialist":    disease.get("specialist", ""),
        "specialist_ar": disease.get("specialist_ar", ""),
        # Convert lists to strings because ChromaDB does not accept lists in metadata
        "symptoms_en":   ", ".join(disease.get("symptoms", [])),
        "symptoms_ar":   ", ".join(disease.get("symptoms_ar", [])),
    }


# ─────────────────────────────────────────────
# Main function
# ─────────────────────────────────────────────
def seed_database():
    """
    Main entry point — runs all seeding steps:
    1. Read the JSON file
    2. Build documents and metadata for each disease
    3. Insert everything into ChromaDB in batches
    """

    # ── Step 1: Read diseases.json ───────────────────────
    print(f"\n📂 Reading file: {DISEASES_JSON_PATH}")

    if not os.path.exists(DISEASES_JSON_PATH):
        print(f"❌ File not found: {DISEASES_JSON_PATH}")
        sys.exit(1)

    with open(DISEASES_JSON_PATH, "r", encoding="utf-8") as f:
        diseases = json.load(f)

    print(f"✅ Loaded {len(diseases)} diseases from JSON")


    # ── Step 2: Connect to ChromaDB ──────────────────────
    print(f"\n🔌 Connecting to ChromaDB...")
    chroma = ChromaClient(persist_directory=CHROMA_PERSIST_DIR)
    collection = chroma.get_or_create_collection(COLLECTION_NAME)

    # Warn if the collection already has data
    existing_count = collection.count()
    if existing_count > 0:
        print(f"⚠️  Collection already contains {existing_count} records.")
        answer = input("Re-seed anyway? Duplicates will be skipped. (y/n): ")
        if answer.lower() != "y":
            print("Aborted.")
            return


    # ── Step 3: Build and insert data in batches ─────────
    print(f"\n⚙️  Processing diseases and inserting into ChromaDB...")

    total_added   = 0
    total_skipped = 0

    # Process in batches to avoid memory issues with large datasets
    for batch_start in range(0, len(diseases), BATCH_SIZE):
        batch = diseases[batch_start : batch_start + BATCH_SIZE]

        batch_ids       = []
        batch_documents = []
        batch_metadatas = []

        for disease in batch:
            disease_id = str(disease.get("id", ""))

            # Skip if this disease already exists in the collection
            existing = collection.get(ids=[disease_id])
            if existing["ids"]:
                total_skipped += 1
                continue

            # Build the document and metadata for this disease
            document = build_document(disease)
            metadata = build_metadata(disease)

            batch_ids.append(disease_id)
            batch_documents.append(document)
            batch_metadatas.append(metadata)

        # Insert the batch if it has any new records
        if batch_ids:
            collection.add(
                ids=batch_ids,
                documents=batch_documents,   # ChromaDB generates embeddings automatically
                metadatas=batch_metadatas,
            )
            total_added += len(batch_ids)
            print(f"   ✅ Batch inserted: {len(batch_ids)} diseases "
                  f"(total so far: {total_added})")


    # ── Step 4: Summary ──────────────────────────────────
    final_count = collection.count()
    print(f"""
────────────────────────────────
✅ Seeding complete!
   Added:    {total_added} new diseases
   Skipped:  {total_skipped} (already existed)
   Total in ChromaDB: {final_count} records
────────────────────────────────
""")

    # Quick sanity check — verify search is working
    if total_added > 0:
        print("🧪 Running a quick search test...")
        results = collection.query(
            query_texts=["حكة وطفح جلدي"],
            n_results=2,
        )
        print("   Closest match for 'حكة وطفح جلدي':")
        for i, (doc_id, doc) in enumerate(
            zip(results["ids"][0], results["documents"][0])
        ):
            # Show only the first line of the document
            first_line = doc.split("\n")[0]
            print(f"   {i+1}. [{doc_id}] {first_line}")


# ─────────────────────────────────────────────
# Entry point
# ─────────────────────────────────────────────
if __name__ == "__main__":
    seed_database()

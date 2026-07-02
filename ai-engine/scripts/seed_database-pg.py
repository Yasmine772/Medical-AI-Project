"""
seed_database-pg.py
-------------------
Reads diseases.json, builds a text document for each disease,
generates embeddings, and stores them in Supabase through RPC calls.

Usage:
    python scripts/seed_database-pg.py
"""

import json
import os
import sys
from pathlib import Path

sys.path.append(str(Path(__file__).parent.parent))

from app.services.embedding_service import EmbeddingService
from app.services.pgvector_client import PgVectorClient

DISEASES_JSON_PATH = "./app/data/diseases.json"
BATCH_SIZE = 30


def build_document(disease: dict) -> str:
    symptoms_ar = "، ".join(disease.get("symptoms_ar", []))

    return f"""
المرض: {disease.get('name_ar', '')}
الأعراض: {symptoms_ar}
الشدة: {disease.get('severity_ar', '')}
الوصف: {disease.get('description', '')}
الاحتمالية: {disease.get('likelihoods', '')}
النصيحة: {disease.get('advice', '')}
التخصص الطبي: {disease.get('specialist_ar', '')}
""".strip()


def build_metadata(disease: dict) -> dict:
    return {
        "name_en": disease.get("name", ""),
        "name_ar": disease.get("name_ar", ""),
        "severity": disease.get("severity", ""),
        "severity_ar": disease.get("severity_ar", ""),
        "specialist": disease.get("specialist", ""),
        "specialist_ar": disease.get("specialist_ar", ""),
        "symptoms_en": ", ".join(disease.get("symptoms", [])),
        "symptoms_ar": ", ".join(disease.get("symptoms_ar", [])),
    }


def seed_database():
    print(f"\n📂 Reading file: {DISEASES_JSON_PATH}")

    if not os.path.exists(DISEASES_JSON_PATH):
        print(f"❌ File not found: {DISEASES_JSON_PATH}")
        sys.exit(1)

    with open(DISEASES_JSON_PATH, "r", encoding="utf-8") as handle:
        diseases = json.load(handle)

    print(f"✅ Loaded {len(diseases)} diseases from JSON")

    print("\n🔌 Connecting to Supabase RPC...")
    client = PgVectorClient()
    client.connect()
    print(f"✅ Ping: {client.ping()}")

    print("🧹 Clearing existing disease rows...")
    client.delete_all()

    embedder = EmbeddingService()

    print("\n⚙️  Processing diseases and inserting through RPC...")

    total_attempted = 0
    all_documents = []
    all_metadata = []
    all_ids = []

    for disease in diseases:
        disease_id = str(disease.get("id", ""))
        all_ids.append(disease_id)
        all_documents.append(build_document(disease))
        all_metadata.append(build_metadata(disease))

    embeddings = embedder.encode_batch(all_documents, batch_size=BATCH_SIZE)

    for disease_id, document, embedding, metadata in zip(
        all_ids, all_documents, embeddings, all_metadata
    ):
        client.insert_disease(disease_id, document, embedding, metadata)
        total_attempted += 1
        if total_attempted % BATCH_SIZE == 0 or total_attempted == len(all_ids):
            print(f"   ✅ Processed {total_attempted}/{len(all_ids)} records")

    print(f"""
────────────────────────────────
✅ Seeding complete!
   Attempted: {total_attempted}
   Added:     {total_attempted}
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
    seed_database()

import sys
from pathlib import Path

sys.path.append(str(Path(__file__).parent.parent))

from app.services.pgvector_client import PgVectorClient
from app.services.embedding_service import EmbeddingService


def main():
    query = input("Enter your symptoms in Arabic or English: ").strip()
    if not query:
        print("Query cannot be empty.")
        return

    print(f"\nSearching for: {query}")

    store = PgVectorClient()
    store.connect()

    embedder = EmbeddingService()
    query_vector = embedder.encode_query(query)

    results = store.search(query_vector, limit=5)

    if not results:
        print("No results found.")
        store.close()
        return

    print(f"\nTop {len(results)} results:\n")
    for i, r in enumerate(results, 1):
        print(f"{i}. {r.get('name_ar', '')} ({r.get('name_en', '')})")
        print(f"   Similarity: {r['similarity']:.4f}")
        print(f"   Source: {r.get('symptoms_ar', '')}")
        preview = r.get('document', '')[:300]
        if preview:
            print(f"   Content: {preview}...")
        print()

    store.close()


if __name__ == "__main__":
    main()

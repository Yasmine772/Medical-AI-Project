"""
Smoke test for Supabase Postgres connectivity.

Usage:
    python scripts/test_supabase_connection.py
"""

import sys
from pathlib import Path

sys.path.append(str(Path(__file__).parent.parent))

from app.services.pgvector_client import PgVectorClient


def main() -> int:
    print("Starting Supabase RPC connection test...")

    try:
        client = PgVectorClient()
        client.connect()

        ping_result = client.ping()

        print("Connection successful.")
        print(f"Ping response: {ping_result}")

        client.close()
        return 0
    except Exception as error:
        print("Connection test failed.")
        print(f"Error: {error}")
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
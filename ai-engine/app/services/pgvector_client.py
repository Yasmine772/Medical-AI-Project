import os
import psycopg2
from psycopg2.extras import execute_values
from pgvector.psycopg2 import register_vector
import numpy as np
from typing import List, Optional

from dotenv import load_dotenv
load_dotenv()


class PgVectorClient:

    def __init__(self):
        self.connection_string = os.environ.get("SUPABASE_DATABASE_URL")
        if not self.connection_string:
            raise ValueError(
                "SUPABASE_DATABASE_URL environment variable is not set. "
                "Get it from your Supabase project: "
                "Project Settings → Database → Connection String (URI)."
            )
        self._connection = None

    def connect(self):
        if self._connection is None or self._connection.closed:
            self._connection = psycopg2.connect(self.connection_string)
            register_vector(self._connection)
            self._enable_extension()
        return self._connection

    def _enable_extension(self):
        with self._connection.cursor() as cur:
            cur.execute("CREATE EXTENSION IF NOT EXISTS vector")
        self._connection.commit()

    def create_table(self):
        with self._connection.cursor() as cur:
            cur.execute("""
                CREATE TABLE IF NOT EXISTS disease_embeddings (
                    id          TEXT PRIMARY KEY,
                    document    TEXT NOT NULL,
                    embedding   vector(384) NOT NULL,
                    name_en     TEXT,
                    name_ar     TEXT,
                    severity    TEXT,
                    severity_ar TEXT,
                    specialist  TEXT,
                    specialist_ar TEXT,
                    symptoms_en TEXT,
                    symptoms_ar TEXT
                );
            """)
            cur.execute("""
                CREATE INDEX IF NOT EXISTS idx_disease_embeddings_cosine
                ON disease_embeddings
                USING hnsw (embedding vector_cosine_ops);
            """)
        self._connection.commit()

    def insert_disease(
        self,
        disease_id: str,
        document: str,
        embedding: np.ndarray,
        metadata: dict,
    ):
        with self._connection.cursor() as cur:
            cur.execute(
                """
                INSERT INTO disease_embeddings
                    (id, document, embedding, name_en, name_ar,
                     severity, severity_ar, specialist, specialist_ar,
                     symptoms_en, symptoms_ar)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                ON CONFLICT (id) DO NOTHING
                """,
                (
                    disease_id,
                    document,
                    embedding,
                    metadata.get("name_en"),
                    metadata.get("name_ar"),
                    metadata.get("severity"),
                    metadata.get("severity_ar"),
                    metadata.get("specialist"),
                    metadata.get("specialist_ar"),
                    metadata.get("symptoms_en"),
                    metadata.get("symptoms_ar"),
                ),
            )
        self._connection.commit()

    def search(
        self,
        query_embedding: np.ndarray,
        limit: int = 5,
    ) -> List[dict]:
        with self._connection.cursor() as cur:
            cur.execute(
                """
                SELECT id, document, name_en, name_ar, specialist_ar,
                       symptoms_ar, description, advice,
                       1 - (embedding <=> %s::vector) AS similarity
                FROM disease_embeddings
                ORDER BY embedding <=> %s::vector
                LIMIT %s
                """,
                (query_embedding, query_embedding, limit),
            )
            rows = cur.fetchall()
        return [
            {
                "id": row[0],
                "document": row[1],
                "name_en": row[2],
                "name_ar": row[3],
                "specialist_ar": row[4],
                "symptoms_ar": row[5],
                "description": row[6],
                "advice": row[7],
                "similarity": float(row[8]),
            }
            for row in rows
        ]

    def count(self) -> int:
        with self._connection.cursor() as cur:
            cur.execute("SELECT COUNT(*) FROM disease_embeddings")
            return cur.fetchone()[0]

    def delete_all(self):
        with self._connection.cursor() as cur:
            cur.execute("DELETE FROM disease_embeddings")
        self._connection.commit()

    def close(self):
        if self._connection and not self._connection.closed:
            self._connection.close()

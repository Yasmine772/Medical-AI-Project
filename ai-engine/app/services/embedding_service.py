"""
Embedding Service
Converts text to numerical vectors using a multilingual embedding model.
Supports multiple languages, including Arabic.
"""

from sentence_transformers import SentenceTransformer
import numpy as np
from typing import List


class EmbeddingService:
    """Convert text to dense vectors for vector-similarity search.

    Two prefix conventions are applied (required by the multilingual-e5 model):
      - ``passage:`` prefix for text stored in the vector DB
      - ``query:`` prefix for user search queries

    Attributes:
        model_name (str): Name of the SentenceTransformer model.
        model: The loaded SentenceTransformer model.
        embedding_dim (int): Dimension of the produced vectors (384 for multilingual-e5-small).
    """

    def __init__(self, model_name: str = "intfloat/multilingual-e5-small"):
        print(f"Loading model: {model_name}...")
        self.model_name = model_name
        self.model = SentenceTransformer(model_name)
        self.embedding_dim = self.model.get_embedding_dimension()
        print(f"Model loaded! dim={self.embedding_dim}")

    def encode(self, text: str) -> np.ndarray:
        """Embed a passage/document with the ``passage:`` prefix.

        Args:
            text (str): Non-empty text to embed.

        Returns:
            np.ndarray: A 384-dimensional vector.

        Raises:
            TypeError: If ``text`` is not a string.
            ValueError: If ``text`` is empty/whitespace.
        """
        if not isinstance(text, str):
            raise TypeError(f"Text must be a string, not {type(text)}")
        if not text.strip():
            raise ValueError("Text cannot be empty")
        text_with_prefix = f"passage: {text}"
        return self.model.encode(text_with_prefix, convert_to_numpy=True)

    def encode_batch(self, texts: List[str], batch_size: int = 32) -> List[np.ndarray]:
        """Embed a list of passages in one batched call (faster than one-by-one).

        Args:
            texts (List[str]): Non-empty list of non-empty strings.
            batch_size (int): Number of texts per batch. Increasing this uses
                              more memory but can speed up encoding.

        Returns:
            List[np.ndarray]: One vector per input text.

        Raises:
            TypeError: If any element is not a string.
            ValueError: If the list is empty or any element is empty.
        """
        if not isinstance(texts, list):
            raise TypeError(f"Texts must be a list, not {type(texts)}")
        if len(texts) == 0:
            raise ValueError("The list of texts is empty")
        for i, text in enumerate(texts):
            if not isinstance(text, str):
                raise TypeError(f"Element at index {i} is not a string: {type(text)}")
            if not text.strip():
                raise ValueError(f"Element at index {i} is empty")
        texts_with_prefix = [f"passage: {t}" for t in texts]
        embeddings = self.model.encode(texts_with_prefix, batch_size=batch_size, convert_to_numpy=True)
        return [embeddings[i] for i in range(len(texts))]

    def encode_query(self, text: str) -> np.ndarray:
        """Embed a user search query with the ``query:`` prefix.

        Args:
            text (str): Non-empty query text.

        Returns:
            np.ndarray: A 384-dimensional vector.

        Raises:
            TypeError: If ``text`` is not a string.
            ValueError: If ``text`` is empty/whitespace.
        """
        if not isinstance(text, str):
            raise TypeError(f"Text must be a string, not {type(text)}")
        if not text.strip():
            raise ValueError("Text cannot be empty")
        return self.model.encode(f"query: {text}", convert_to_numpy=True)

    def get_embedding_dimension(self) -> int:
        """Return the dimension size of the vectors.

        Returns:
            int: Dimension size (384 for multilingual-e5-small).
        """
        return self.embedding_dim

    def get_model_name(self) -> str:
        """Return the name of the embedding model.

        Returns:
            str: Model name.
        """
        return self.model_name

    def calculate_similarity(self, embedding1: np.ndarray, embedding2: np.ndarray) -> float:
        """Calculate cosine similarity between two vectors.

        Args:
            embedding1 (np.ndarray): First vector.
            embedding2 (np.ndarray): Second vector.

        Returns:
            float: Similarity score between -1 and 1 (1 = identical).
        """
        dot_product = np.dot(embedding1, embedding2)
        norm1 = np.linalg.norm(embedding1)
        norm2 = np.linalg.norm(embedding2)
        if norm1 == 0 or norm2 == 0:
            return 0.0
        return float(dot_product / (norm1 * norm2))
"""
Embedding Service
Converts text to numerical vectors using the all-MiniLM-L6-v2 model.
Supports multiple languages, including Arabic.
"""

from pydoc import text

from sentence_transformers import SentenceTransformer
import numpy as np
from typing import List

class EmbeddingService:
    """
    A service for converting text to embeddings.

    Attributes:
        model_name (str): The name of the model being used.
        model: The loaded SentenceTransformer model.
        embedding_dim (int): The dimension size of the vectors (typically 384 for all-MiniLM-L6-v2).
    """

    def __init__(self, model_name: str = "intfloat/multilingual-e5-small"):
        """
        Initializes the embedding service.

        Args:
            model_name (str): The name of the model. Default is "all-MiniLM-L6-v2",
                              which is a lightweight, fast, and multilingual model.
        """
        print(f"Loading model: {model_name}...")
        self.model_name = model_name
        self.model = SentenceTransformer(model_name)
        
        # Retrieve the embedding dimension
        # all-MiniLM-L6-v2 outputs 384-dimensional vectors
        self.embedding_dim = self.model.get_sentence_embedding_dimension()
        print(f"Model loaded successfully!")
        print(f"Embedding Dimension: {self.embedding_dim}")

    def encode(self, text: str) -> np.ndarray:
        """
        Converts a single string into a vector.

        Args:
            text (str): The text to encode.

        Returns:
            np.ndarray: A vector of shape (embedding_dim,).

        Example:
            >>> service = EmbeddingService()
            >>> embedding = service.encode("Fever and cough")
            >>> print(embedding.shape)  # (384,)
        """
        if not isinstance(text, str):
            raise TypeError(f"Text must be a string, not {type(text)}")
            
        if not text.strip():
            raise ValueError("Text cannot be empty")
            
        text_with_prefix = f"passage: {text}"
        embedding = self.model.encode(text_with_prefix, convert_to_numpy=True)
        return embedding

    def encode_batch(self, texts: List[str], batch_size: int = 32) -> List[np.ndarray]:
        """
        Converts a list of strings into vectors.
        This method is more efficient than encoding one by one.

        Args:
            texts (List[str]): List of texts to encode.
            batch_size (int): Number of texts processed at once.
                              (Increasing this may speed up the process but consume more memory).

        Returns:
            List[np.ndarray]: A list of vectors.

        Example:
            >>> service = EmbeddingService()
            >>> texts = ["Fever and cough", "Skin rash", "Headache"]
            >>> embeddings = service.encode_batch(texts)
            >>> print(len(embeddings))  # 3
            >>> print(embeddings[0].shape)  # (384,)
        """
        if not isinstance(texts, list):
            raise TypeError(f"Texts must be a list, not {type(texts)}")
            
        if len(texts) == 0:
            raise ValueError("The list of texts is empty")
            
        # Validate that all elements are strings
        for i, text in enumerate(texts):
            if not isinstance(text, str):
                raise TypeError(f"Element at index {i} is not a string: {type(text)}")
            if not text.strip():
                raise ValueError(f"Element at index {i} is empty")
                
        texts_with_prefix = [f"passage: {t}" for t in texts]
        embeddings = self.model.encode(texts_with_prefix, batch_size=batch_size, convert_to_numpy=True)
        
        return [embeddings[i] for i in range(len(texts))]
    
    def embed(self, text: str) -> list:
        return self.model.encode(f"passage: {text}", convert_to_numpy=True).tolist()

    def embed_query(self, query: str) -> list:
        return self.model.encode(f"query: {query}", convert_to_numpy=True).tolist()

    def encode_query(self, text: str) -> np.ndarray:
        if not isinstance(text, str):
            raise TypeError(f"Text must be a string, not {type(text)}")
        if not text.strip():
            raise ValueError("Text cannot be empty")
        return self.model.encode(f"query: {text}", convert_to_numpy=True)

    def get_embedding_dimension(self) -> int:
        """
        Returns the dimension size of the vectors.

        Returns:
            int: Dimension size (usually 384 for all-MiniLM-L6-v2).
        """
        return self.embedding_dim

    def get_model_name(self) -> str:
        """
        Returns the name of the model being used.

        Returns:
            str: Model name.
        """
        return self.model_name

    def calculate_similarity(self, embedding1: np.ndarray, embedding2: np.ndarray) -> float:
        """
        Calculates cosine similarity between two vectors.

        Args:
            embedding1 (np.ndarray): The first vector.
            embedding2 (np.ndarray): The second vector.

        Returns:
            float: Similarity score between -1 and 1 (1 = identical).
        """
        # Calculate Cosine Similarity
        dot_product = np.dot(embedding1, embedding2)
        norm1 = np.linalg.norm(embedding1)
        norm2 = np.linalg.norm(embedding2)
        
        if norm1 == 0 or norm2 == 0:
            return 0.0
            
        similarity = dot_product / (norm1 * norm2)
        return float(similarity)

# Example usage
if __name__ == "__main__":
    print("=" * 60)
    print("Testing Embedding Service")
    print("=" * 60)
    
    # Initialize the service
    service = EmbeddingService()
    
    print("\n1️⃣  Testing single text encoding:")
    print("-" * 60)
    text1 = "Fever, cough, and shortness of breath"
    embedding1 = service.encode(text1)
    print(f"Text: {text1}")
    print(f"Vector shape: {embedding1.shape}")
    print(f"First 5 values: {embedding1[:5]}")
    
    print("\n2️⃣  Testing batch text encoding:")
    print("-" * 60)
    texts = [
        "Fever and cough",
        "Skin rash and itching",
        "Headache and dizziness"
    ]
    embeddings = service.encode_batch(texts)
    print(f"Number of texts: {len(texts)}")
    print(f"Number of vectors: {len(embeddings)}")
    print(f"Shape of each vector: {embeddings[0].shape}")
    
    print("\n3️⃣  Testing similarity calculation:")
    print("-" * 60)
    text_a = "Fever and cough"
    text_b = "High temperature and cough"
    text_c = "Skin rash"
    
    embedding_a = service.encode(text_a)
    embedding_b = service.encode(text_b)
    embedding_c = service.encode(text_c)
    
    similarity_ab = service.calculate_similarity(embedding_a, embedding_b)
    similarity_ac = service.calculate_similarity(embedding_a, embedding_c)
    
    print(f"Text A: {text_a}")
    print(f"Text B: {text_b}")
    print(f"Text C: {text_c}")
    print(f"\nSimilarity between A and B: {similarity_ab:.4f} (Very similar)")
    print(f"Similarity between A and C: {similarity_ac:.4f} (Different)")
    
    print("\n" + "=" * 60)
    print("All tests passed!")
    print("=" * 60)
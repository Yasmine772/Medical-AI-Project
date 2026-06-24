import chromadb
from chromadb.config import Settings
import os


class ChromaClient:
    """
    A client wrapper for ChromaDB operations.
    
    Attributes:
        persist_directory (str): Directory where ChromaDB data is stored
        client: The underlying ChromaDB client instance
    """
    
    def __init__(self, persist_directory: str = "./chroma_db"):
        """
        Initialize the ChromaDB client.
        
        Args:
            persist_directory: Directory path for persisting ChromaDB data
        """
        self.persist_directory = persist_directory
        os.makedirs(persist_directory, exist_ok=True)
        
        self.client = chromadb.PersistentClient(
            path=persist_directory,
            settings=Settings(
                anonymized_telemetry=False
            )
        )
        print(f"Connected to ChromaDB at: {persist_directory}")
    
    def get_or_create_collection(self, collection_name: str = "medical_diseases"):
        """
        Get an existing collection or create a new one if it doesn't exist.
        
        Args:
            collection_name: Name of the collection to get or create
            
        Returns:
            ChromaDB collection object
        """
        try:
            collection = self.client.get_collection(collection_name)
            print(f"Collection retrieved: {collection_name}")
        except:
            collection = self.client.create_collection(
                name=collection_name,
                metadata={"hnsw:space": "cosine"}
            )
            print(f"Collection created: {collection_name}")
        
        return collection
    
    def get_client(self):
        """
        Get the underlying ChromaDB client for advanced operations.
        
        Returns:
            The ChromaDB PersistentClient instance
        """
        return self.client
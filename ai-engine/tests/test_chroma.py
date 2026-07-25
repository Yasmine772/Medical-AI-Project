import sys
import os
from pathlib import Path

# Add project root to Python path
project_root = Path(__file__).parent.parent.absolute()
sys.path.insert(0, str(project_root))

print(f" Project Path: {project_root}")

try:
    # Import ChromaClient from services
    from app.services.chroma_client import ChromaClient
    
    print(" ChromaClient imported successfully!")
    
    # Initialize ChromaDB client
    print("Connecting to ChromaDB...")
    client = ChromaClient()
    
    # Get or create collection
    print("Getting or creating collection...")
    collection = client.get_or_create_collection("medical_diseases")
    
    # Display collection information
    print(f" Collection Name: {collection.name}")
    print(f" Document Count: {collection.count()}")
    
except Exception as e:
    print(f"❌ Error: {e}")
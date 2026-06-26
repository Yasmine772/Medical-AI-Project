# tests/model_test.py
# before running this test, make sure to install the required packages:
# pip install -r requirements.txt
# then run the test using:
# Virtual environment command : .\venv\Scripts\Activate.ps1
# pip install sentence-transformers numpy
# test command : python3 tests/model_test.py


import sys
import os

sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from app.services.embedding_service import EmbeddingService

def test():
    print("--- Starting Model Test ---")
    service = EmbeddingService()
    
    test_text = "headache and fever"
    vector = service.encode(test_text)
    
    print(f"Text: {test_text}")
    print(f"Vector Shape: {vector.shape}")
    
    if vector.shape[0] == 384:
        print("Success: Vector size is 384")
    else:
        print(" Failed: Vector size is not 384")

if __name__ == "__main__":
    test()

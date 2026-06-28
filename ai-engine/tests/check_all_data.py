# This script checks all the data in the ChromaDB collection and prints the total number of records found, along with their details (ID and name in Arabic).
# its only for testing purposes, to check if the data is correctly inserted into the database.
# the command: python tests/check_all_data.py

from app.services.chroma_client import ChromaClient
chroma = ChromaClient(persist_directory="./chroma_db")
collection = chroma.get_or_create_collection("medical_diseases")

all_data = collection.get()

total_records = len(all_data['ids'])
print(f"✅ تم العثور على {total_records} سجل في قاعدة البيانات.\n")

print("--- تفاصيل الأمراض الموجودة ---")
for i in range(total_records):
    name = all_data['metadatas'][i].get('name_ar')
    d_id = all_data['ids'][i]
    print(f"{i+1}. [ID: {d_id}] - {name}")


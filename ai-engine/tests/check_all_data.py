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


import sys, json, time

sys.path.insert(0, r"D:\programming projects\laravel_projects\Medical-AI-Project\ai-engine")

from app.state import init, get_store, get_embedder, get_session_manager, get_llm
from app.services.pgvector_client import PgVectorClient
from app.services.embedding_service import EmbeddingService
from app.services.diagnosis_service import DiagnosisService
from app.services.logger import enable

enable()

store = PgVectorClient()
embedder = EmbeddingService()
init(store, embedder)
svc = DiagnosisService(get_store(), get_embedder(), get_session_manager(), get_llm())

# ── Step 1: Create session ──
print("\n=== 1. CREATE SESSION ===")
session_id = svc.create_session({"gender": "male"}, "test_user")
print(f"session_id: {session_id[:8]}...")

# ── Step 2: Symptom questions for "fever" ──
print("\n=== 2. SYMPTOM QUESTIONS (fever) ===")
r = svc.get_symptom_questions(session_id, "fever")
print(json.dumps(r, ensure_ascii=False, indent=2)[:500])

# ── Step 3: Submit symptom answers ──
print("\n=== 3. SUBMIT SYMPTOM ANSWERS ===")
answers = [{"question_id": "q0", "answer": "38.5"}]
r = svc.submit_symptom_answers(session_id, "fever", answers, symptoms_complete=True)
print(json.dumps(r, ensure_ascii=False, indent=2)[:500])

# ── Step 4: Follow-up Q&A loop ──
print("\n=== 4. FOLLOW-UP LOOP ===")
answers = ["Head and throat", "Sudden", "Sharp", "No", "Mild", "No", "Yes", "No", "Sometimes", "I think so"]
for i in range(10):
    q = svc.get_current_question(session_id)
    qtype = q.get("question", {}).get("type", "")
    if qtype == "diagnosis":
        print(f"\n--- Q{i+1} DIAGNOSIS ---")
        break
    ans = answers[i] if i < len(answers) else "yes"
    print(f"\n--- Q{i+1} ---")
    print(f"  Q: {q.get('question',{}).get('question','?')[:60]}")
    print(f"  A: {ans}")
    r = svc.submit_follow_up_answer(session_id, f"q{i}", ans)
    if r.get("type") == "diagnosis":
        print("  => DIAGNOSIS!")
        diags = r.get("diagnoses", [])
        for d in diags:
            print(f"     {d.get('disease_name','?'):30s} {d.get('probability',0)*100:.0f}%")
        break

# ── Step 5: Report ──
print("\n=== 5. REPORT ===")
r = svc.get_report(session_id)
diags = r.get("diagnoses", [])
for d in diags:
    print(f"  {d.get('disease_name','?'):30s} {d.get('probability',0)*100:.0f}% - {d.get('specialist','?')}")
print("\nDone!")

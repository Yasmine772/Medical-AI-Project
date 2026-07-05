import requests

sid = "e7c748ec-514a-4994-8d87-2721c0997a0a"
for i in range(10):
    r = requests.post("http://localhost:5000/diagnose/continue", data={"session_id": sid, "answer": "لا"})
    data = r.json()
    t = data.get("type", "?")
    probs = {k: round(v, 2) for k, v in data.get("probabilities", {}).items()}
    print(f"Q{i+1}: type={t} top3={sum(sorted(probs.values(), reverse=True)[:3]):.0%}")
    if t == "diagnosis":
        for d in data.get("diagnoses", []):
            print(f"  -> {d['disease_name']} ({d['probability']*100:.0f}%)")
        break

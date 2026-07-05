import requests, sys

sid = sys.argv[1]
for i in range(15):
    r = requests.post("http://localhost:5000/diagnose/continue", data={"session_id": sid, "answer": "نعم"}, timeout=30)
    data = r.json()
    t = data.get("type", "?")
    probs = {k: round(v, 2) for k, v in data.get("probabilities", {}).items()}
    top = dict(sorted(probs.items(), key=lambda x: -x[1])[:3])
    print(f"Q{i+1}: type={t} top3={top}")
    sys.stdout.flush()
    if t == "diagnosis":
        for d in data.get("diagnoses", []):
            print(f"  -> {d['disease_name']} ({d['probability']*100:.0f}%)")
        break

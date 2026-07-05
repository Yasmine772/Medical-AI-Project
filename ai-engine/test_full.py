import requests, sys, json

sid = requests.post("http://localhost:5000/diagnose/start", data={"symptom": "cough"}, timeout=60).json()["session_id"]
print(f"Session: {sid}")

for i in range(10):
    r = requests.post("http://localhost:5000/diagnose/continue", data={"session_id": sid, "answer": "\u0646\u0639\u0645"}, timeout=120)
    data = r.json()
    t = data["type"]
    probs = {k: round(v, 2) for k, v in data.get("probabilities", {}).items()}
    top3 = dict(sorted(probs.items(), key=lambda x: -x[1])[:3])
    print(f"{i+1}: type={t} top3={top3}")
    sys.stdout.flush()
    if t == "diagnosis":
        for d in data.get("diagnoses", []):
            print(f"  -> {d['disease_name']} ({d['probability']*100:.0f}%) {d.get('specialist','')}")
        print(json.dumps(data, ensure_ascii=False, indent=2))
        break

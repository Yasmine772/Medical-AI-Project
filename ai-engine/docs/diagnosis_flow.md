# Diagnosis Flow Diagram

```mermaid
sequenceDiagram
    participant Flutter as Flutter (Mobile)
    participant Laravel as Laravel (MySQL)
    participant FastAPI as FastAPI (pgvector)

    Note over Flutter: User opens diagnosis screen

    Flutter->>Laravel: GET /api/user/medical-profile
    Laravel->>Laravel: Check auth, fetch user data

    alt No medical data
        Laravel-->>Flutter: Static questions (age, smoker, etc.)
        Flutter->>Laravel: POST /api/user/medical-profile (answers)
        Laravel->>Laravel: Save to MySQL
    end

    Note over Flutter: User types in search box

    Flutter->>Laravel: GET /api/symptoms?q=...
    Laravel-->>FastAPI: GET /symptoms?q=...
    FastAPI->>FastAPI: Embed query → search pgvector
    FastAPI-->>Laravel: {results: [{key, en, ar, symptoms, specialist}]}
    Laravel-->>Flutter: {results: [...]}

    Note over Flutter: User taps ONE result

    Flutter->>Laravel: POST /api/diagnosis/start {symptom: "key"}
    Laravel->>Laravel: Fetch past diagnoses from MySQL
    Laravel->>FastAPI: POST /diagnose/start {symptom, past_diagnoses, x_user_id}

    FastAPI->>FastAPI: Embed symptom → search pgvector → candidates
    FastAPI->>FastAPI: Create Supabase session
    FastAPI->>FastAPI: LLM generates first question (SOCRATES)

    FastAPI-->>Laravel: {session_id, type: "question", question, options, probabilities}
    Laravel-->>Flutter: {session_id, type: "question", question, options}

    loop Q&A Loop (up to 10 questions)
        Flutter->>Flutter: User selects answer (multi-choice only)
        Flutter->>Laravel: POST /api/diagnosis/answer {session_id, answer}
        Laravel->>FastAPI: POST /diagnose/continue {session_id, answer}

        FastAPI->>FastAPI: Append to conversation
        FastAPI->>FastAPI: Check question_count >= 10 → force
        FastAPI->>FastAPI: LLM next question or diagnosis

        alt type == "question"
            FastAPI-->>Laravel: {type: "question", question, options, probabilities}
            Laravel-->>Flutter: {type: "question", question, options}
        else type == "diagnosis"
            FastAPI-->>Laravel: {type: "diagnosis", diagnoses: [{disease_name, confidence, probability, ...}]}
            Laravel-->>Flutter: {type: "diagnosis", diagnoses: [Strong, Moderate, Less Likely]}
            Laravel->>Laravel: Save diagnosis to MySQL
        end
    end

    Note over Flutter: Show top 3 possibilities
    Note over Flutter: User taps "Download Report"

    Flutter->>Laravel: GET /api/report/{session_id}
    Laravel->>Laravel: Generate PDF report
    Laravel-->>Flutter: PDF file
```

## How to view

- **GitHub/GitLab** — renders automatically
- **VS Code** — install "Markdown Preview Mermaid Support" extension
- **Online** — paste at https://mermaid.live
- **PlantUML** — `docs/diagnosis_flow.puml` for PlantUML-compatible tools

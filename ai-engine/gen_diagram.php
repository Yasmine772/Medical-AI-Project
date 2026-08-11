<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Generating PlantUML diagram...\n";

$uml = "@startuml Diagnosis_Flow
title Medical Diagnosis Flow

actor Patient
participant Frontend
participant Laravel
participant FastAPI
participant LLM
participant Supabase

== Start Diagnosis ==
Patient -> Frontend: Submit baseline form
Frontend -> Laravel: POST /diagnosis/start
Laravel -> FastAPI: POST /diagnosis/start
FastAPI -> FastAPI: create_session(baseline)
FastAPI --> Laravel: {session_id}
Laravel -> Supabase: INSERT session
Laravel --> Frontend: {session_id}

== Search Symptoms ==
Patient -> Frontend: Search symptom
Frontend -> Laravel: GET /symptoms?q=cough
Laravel -> FastAPI: GET /symptoms?q=cough
FastAPI -> FastAPI: embed query
FastAPI -> Supabase: vector search
FastAPI -> LLM: extract diseases
FastAPI -> FastAPI: dedupe + rank
FastAPI --> Laravel: {results}
Laravel --> Frontend: {results}

== Select Symptom ==
Patient -> Frontend: Select symptom
Frontend -> Laravel: POST /symptom/select
Laravel -> FastAPI: POST /symptom/select
FastAPI -> FastAPI: vector search + extract
FastAPI -> FastAPI: compute_priors()
FastAPI -> LLM: generate first question
FastAPI --> Laravel: {question}
Laravel --> Frontend: {question}

== Follow-up Loop ==
loop until diagnosis
    Patient -> Frontend: Answer question
    Frontend -> Laravel: POST /follow-up/answer
    Laravel -> FastAPI: POST /follow-up/answer
    FastAPI -> FastAPI: bayes_update()
    alt cap hit AND not no_more_symptoms
        FastAPI --> Frontend: need_more_symptoms
    else force_diagnosis=true
        FastAPI -> FastAPI: _finalize()
        FastAPI -> LLM: name diagnoses
        FastAPI --> Frontend: diagnosis
    else normal
        FastAPI -> LLM: generate next question
        FastAPI --> Frontend: {question}
    end
end

== Generate Report ==
Patient -> Frontend: Generate report
Frontend -> Laravel: POST /reports/{id}/generate
Laravel -> FastAPI: POST /generate-report/{id}
FastAPI -> FastAPI: build_report_html()
FastAPI -> FastAPI: generate_pdf()
FastAPI --> Laravel: {pdf_path}
Laravel -> Supabase: UPDATE session (phase, pdf)
Laravel -> Laravel: assign doctor from ai_result specialist
Laravel --> Frontend: {pdf_path}

== Doctor Review ==
actor Doctor
Doctor -> Frontend: View reviews
Frontend -> Laravel: GET /doctor/reviews
Laravel -> Supabase: SELECT sessions WHERE doctor_id
Laravel -> Laravel: buildPatientData() from user profile
Laravel --> Frontend: {reviews with patient data}

Doctor -> Frontend: Submit review
Frontend -> Laravel: POST /doctor/reviews/{id}/submit
Laravel -> Laravel: submitReview(decision, notes)
Laravel -> Supabase: UPDATE session (ai_result, phase)
Laravel -> FastAPI: POST /generate-report/{id} (reviewed)
FastAPI -> FastAPI: render with doctor footer
FastAPI --> Laravel: {pdf_path}
Laravel --> Frontend: {updated session}

@enduml";

file_put_contents(__DIR__.'/diagnosis_flow.puml', $uml);
echo "Wrote diagnosis_flow.puml\n";

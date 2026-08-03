<?php

namespace App\Services\Api;

use App\Models\DiagnosisSession;
use App\Models\PatientProfile;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected string $fastApiUrl;

    protected int $timeout;

    protected int $reportTimeout;

    public function __construct()
    {
        $this->fastApiUrl = config('services.fastapi.url');
        $this->timeout = config('services.fastapi.timeout');
        $this->reportTimeout = config('services.fastapi.report_timeout', 60);
    }

    // ------------------------------------------------------------------------------
    public function startDiagnosis($request): ?array
    {
        $user = auth()->user();

        if ($request && $request['assessment_for'] === 'myself') {

            PatientProfile::updateOrCreate(['user_id' => $user->id], [
                'gender' => $request['gender'],
                'is_smoker' => $request['is_smoker'],
                'has_diabetes' => $request['has_diabetes'],
                'has_hypertension' => $request['has_hypertension'],
                'drinks_alcohol' => $request['is_alcoholic'],
                'occupation' => $request['patient_job'],
                'is_pregnant' => $request['is_pregnant'],
                'activity_level' => $request['activity_level'],
                'birth_date' => $request['birth_date'],
                'blood_type' => $request['blood_type'] ?? null,
            ]);
        }

        $age = Carbon::parse($request['birth_date'])->age;

        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->fastApiUrl.'/diagnosis/start', [
                    'patient_name' => $user->full_name,
                    'user_id' => $user->id,
                    'gender' => $request['gender'],
                    'age' => $age,
                    'is_smoker' => $request['is_smoker'],
                    'has_diabetes' => $request['has_diabetes'],
                    'has_hypertension' => $request['has_hypertension'],
                    'is_pregnant' => $request['is_pregnant'],
                    'is_alcoholic' => $request['is_alcoholic'],
                    'patient_job' => $request['patient_job'],
                    'activity_level' => $request['activity_level'],
                    'assessment_for' => $request['assessment_for'],
                    'model_name' => $request['model_name'] ?? null,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                Log::info($result);

                $sessionId = $result['data']['session_id'] ?? null;

                DiagnosisSession::create([
                    'session_hash' => $sessionId,
                    'status' => 'ACTIVE',
                    'phase' => 'doctor_review',
                    'pdf_file_path' => null,
                    'user_id' => $user->id,
                    'started_at' => now(),
                ]);

                return $result;
            }
            Log::error('FastAPI start diagnosis failed', ['body' => $response->body()]);

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (startDiagnosis): '.$e->getMessage());

            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (startDiagnosis): '.$e->getMessage());

            return null;
        }
    }

    // *********************************************** */
    public function searchSymptoms(string $query, ?string $modelName = null): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->fastApiUrl.'/symptoms', ['q' => $query, 'model_name' => $modelName]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FastAPI search symptoms failed', ['query' => $query, 'body' => $response->body()]);

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (searchSymptoms): '.$e->getMessage());

            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (searchSymptoms): '.$e->getMessage());

            return null;
        }
    }

    // *********************************************** */
    public function getSymptomQuestions($data): ?array
    {
        try {
            $response = Http::timeout(120)
                ->asForm()
                ->post($this->fastApiUrl.'/symptom/select', [
                    'session_id' => $data['session_id'],
                    'name' => $data['name'],
                ]);

            if ($response->successful()) {
                return $response->json();
            }
            Log::error('FastAPI get symptom questions failed', ['name' => $data['name']]);

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (getSymptomQuestions): '.$e->getMessage());

            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (getSymptomQuestions): '.$e->getMessage());

            return null;
        }
    }

    // *********************************************** */
    public function getNextDiagnosisQuestion($data)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->fastApiUrl.'/follow-up/next', [
                    'session_id' => $data['session_id'],
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FastAPI get next diagnosis question failed', ['body' => $response->body()]);

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (getNextDiagnosisQuestion): '.$e->getMessage());

            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (getNextDiagnosisQuestion): '.$e->getMessage());

            return null;
        }
    }

    // ********************************************* */
    public function submitDiagnosisAnswer($data): ?array
    {
        $user = auth()->user();
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->fastApiUrl.'/follow-up/answer', [
                    'session_id' => $data['session_id'],
                    'question_id' => $data['question_id'],
                    'answer' => $data['answer'],
                ]);

            if ($response->successful()) {
                return $response->json();
            }
            Log::error('FastAPI submit diagnosis answer failed', ['body' => $response->body()]);

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (submitDiagnosisAnswer): '.$e->getMessage());

            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (submitDiagnosisAnswer): '.$e->getMessage());

            return null;
        }
    }

    // ************************************************************ */
    public function getDiagnosisHistory(string $userId, string $languageCode = 'en')
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->fastApiUrl.'/diagnosis-history', [
                    'user_id' => $userId,
                    'language_code' => $languageCode,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FastAPI get diagnosis history failed', ['body' => $response->body()]);

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (getDiagnosisHistory): '.$e->getMessage());

            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (getDiagnosisHistory): '.$e->getMessage());

            return null;
        }
    }

    // //////////////////////////////////////////////////////////////////////////////////////////////
    public function generateReport(string $sessionId, string $languageCode = 'en')
    {
        try {
            $response = Http::timeout($this->reportTimeout)
                ->post($this->fastApiUrl."/generate-report/{$sessionId}", ['language_code' => $languageCode]);

            if ($response->successful()) {
                $result = $response->json();

                $session = DiagnosisSession::where('session_hash', $sessionId)->first();

                if ($session) {
                    $pdfPath = $result['pdf_path'] ?? $result['pdf_url'] ?? null;

                    $session->update([
                        'phase' => 'completed',
                        'report_generated_at' => now(),
                        'pdf_file_path' => $pdfPath ?? $session->pdf_file_path,
                        'pdf_url' => $pdfPath
                            ? $this->fastApiUrl."/reports/{$sessionId}/download?language_code={$languageCode}"
                            : $session->pdf_url,
                    ]);

                    if (empty($session->ai_result)) {
                        app(DiagnosisDataService::class)->store($session, $languageCode);
                    }

                    if (! $session->doctor_id) {
                        $preview = Http::timeout($this->timeout)
                            ->get($this->fastApiUrl."/reports/{$sessionId}/preview", ['language_code' => $languageCode]);

                        if ($preview->successful()) {
                            $previewData = $preview->json();
                            $diagnoses = $previewData['diagnoses'] ?? [];
                            $specialist = $diagnoses[0]['specialist'] ?? null;

                            if ($specialist) {
                                $doctorAssignmentService = app(DoctorAssignmentService::class);
                                $doctorAssignmentService->assign($session->id, $specialist);
                            }
                        }
                    }
                }

                return $result;
            }

            Log::error('FastAPI generate-report failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI report timeout: '.$e->getMessage());

            return null;

        } catch (\Exception $e) {
            Log::error('FastAPI report error: '.$e->getMessage());

            return null;
        }
    }

    // //////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Regenerate the report PDF after the doctor's review, using the reviewed
     * diagnoses and a doctor-reviewed footer instead of the AI disclaimer.
     */
    public function generateDoctorReport(DiagnosisSession $session, string $languageCode = 'en')
    {
        $doctor = $session->doctor;

        $payload = [
            'language_code' => $languageCode,
            'diagnoses' => $this->formatDiagnosesForPdf($session->ai_result ?? []),
            'patient_info' => $this->formatPatientInfoForPdf($session),
            'initial_symptoms' => is_array($session->symptoms)
                ? implode(', ', array_values($session->symptoms))
                : '',
            'doctor_review' => $doctor ? [
                'doctor_name' => $doctor->user?->full_name ?? 'Doctor',
                'specialization' => $doctor->specialization,
                'phone' => $doctor->phone,
                'reviewed_at' => now()->format('Y-m-d H:i'),
                'notes' => $session->doctor_notes,
            ] : null,
        ];

        try {
            $response = Http::timeout($this->reportTimeout)
                ->acceptJson()
                ->post($this->fastApiUrl."/generate-report/{$session->session_hash}", $payload);

            if ($response->successful()) {
                $result = $response->json();

                $session->update([
                    'phase' => 'completed',
                    'report_generated_at' => now(),
                    'pdf_file_path' => $result['pdf_path'] ?? $session->pdf_file_path,
                    'pdf_url' => $result['pdf_path']
                        ? $this->fastApiUrl."/reports/{$session->session_hash}/download?language_code={$languageCode}&reviewed=1"
                        : $session->pdf_url,
                ]);

                return $result;
            }

            Log::error('FastAPI generate-doctor-report failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI doctor-report timeout: '.$e->getMessage());

            return null;

        } catch (\Exception $e) {
            Log::error('FastAPI doctor-report error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Convert Laravel ai_result rows (name_en/name_ar + int probability 0-100)
     * into the FastAPI template diagnosis shape.
     */
    protected function formatDiagnosesForPdf(array $aiResult): array
    {
        return collect($aiResult)
            ->map(function ($d) {
                $probability = $d['probability'] ?? null;
                if (is_numeric($probability) && $probability > 1) {
                    $probability = (float) $probability / 100;
                }

                $confidence = strtolower((string) ($d['confidence'] ?? ''));
                $confidence = match ($confidence) {
                    'high', 'strong' => 'Strong',
                    'medium', 'moderate' => 'Moderate',
                    'low', 'less likely' => 'Less Likely',
                    default => $d['confidence'] ?? 'Less Likely',
                };

                return [
                    'disease_name' => $d['name_en'] ?? $d['disease_name'] ?? '',
                    'disease_name_ar' => $d['name_ar'] ?? $d['disease_name_ar'] ?? '',
                    'probability' => is_numeric($probability) ? (float) $probability : null,
                    'confidence' => $confidence,
                    'specialist' => $d['specialist'] ?? '',
                    'advice' => $d['advice'] ?? '',
                    'reasoning' => $d['reasoning'] ?? '',
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Build patient_info for the PDF from session patient_data + profile extras.
     */
    protected function formatPatientInfoForPdf(DiagnosisSession $session): array
    {
        $p = $session->patient_data ?? [];
        $profile = $session->user?->profile;

        return [
            'patient_name' => $session->user?->full_name ?? ($p['name'] ?? null),
            'age' => $p['age'] ?? null,
            'gender' => $p['gender'] ?? null,
            'is_smoker' => $p['smoker'] ?? null,
            'has_diabetes' => $p['diabetes'] ?? null,
            'has_hypertension' => $p['hypertension'] ?? null,
            'is_pregnant' => $p['pregnant'] ?? null,
            'activity_level' => $p['activity_level'] ?? null,
            'blood_type' => $profile?->blood_type ?? $p['blood_type'] ?? null,
            'occupation' => $profile?->occupation ?? $p['occupation'] ?? null,
            'drinks_alcohol' => $profile?->drinks_alcohol ?? null,
        ];
    }

    public function downloadReport(string $sessionId, string $languageCode = 'en')
    {
        try {
            $session = DiagnosisSession::where('session_hash', $sessionId)->first();

            $reviewed = $session && $session->doctor_reviewed_at ? 1 : 0;

            $response = Http::timeout($this->reportTimeout)
                ->get($this->fastApiUrl."/reports/{$sessionId}/download", [
                    'language_code' => $languageCode,
                    'reviewed' => $reviewed,
                ]);

            if ($response->successful()) {
                $filename = "diagnostic_report_{$sessionId}.pdf";
                $disposition = $response->header('Content-Disposition');
                if ($disposition && preg_match('/filename="?([^"]+)"?/', $disposition, $m)) {
                    $filename = $m[1];
                }

                return response()->make(
                    $response->body(),
                    200,
                    [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                    ]
                );
            }

            Log::error('FastAPI download-report failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI download timeout: '.$e->getMessage());

            return null;

        } catch (\Exception $e) {
            Log::error('FastAPI download error: '.$e->getMessage());

            return null;
        }
    }

    public function previewReport(string $sessionId, string $languageCode = 'en')
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->fastApiUrl."/reports/{$sessionId}/preview", ['language_code' => $languageCode]);

            if ($response->successful()) {
                return response()->make(
                    $response->body(),
                    200,
                    ['Content-Type' => 'text/html; charset=utf-8']
                );
            }

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI preview timeout: '.$e->getMessage());

            return null;

        } catch (\Exception $e) {
            Log::error('FastAPI preview error: '.$e->getMessage());

            return null;
        }
    }
}

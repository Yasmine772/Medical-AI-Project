<?php

namespace App\Services\Api;

use App\Models\DiagnosisSession;
use App\Models\PatientProfile;
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
                'is_alcoholic'     => $request['is_alcoholic'],
                'patient_job'      => $request['patient_job'],
                'is_pregnant' => $request['is_pregnant'],
                'activity_level' => $request['activity_level'],
            ]);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->fastApiUrl.'/diagnosis/start', [
                    'user_id' => $user->id,
                    'gender' => $request['gender'],
                    'is_smoker' => $request['is_smoker'],
                    'has_diabetes' => $request['has_diabetes'],
                    'has_hypertension' => $request['has_hypertension'],
                    'is_pregnant' => $request['is_pregnant'],
                    'is_alcoholic'     => $request['is_alcoholic'],
                    'patient_job'      => $request['patient_job'],
                    'activity_level' => $request['activity_level'],
                    'assessment_for' => $request['assessment_for'],
                ]);

            if ($response->successful()) {
                $result = $response->json();

                DiagnosisSession::create([
                    'status' => 'ACTIVE',
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
    public function searchSymptoms(string $query): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->fastApiUrl.'/symptoms', ['q' => $query]);

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
            $response = Http::timeout($this->timeout)
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
                $responseData = $response->json();

                $responseType = $responseData['data']['response_type'] ?? null;

                if ($responseType === 'diagnosis') {
                    $currentSession = DiagnosisSession::where('user_id', $user->id)
                        ->where('status', 'ACTIVE')
                        ->first();

                    if ($currentSession) {
                        $currentSession->update([
                            'status' => 'COMPLETED',
                            'completed_at' => now(),
                        ]);
                    }
                }
                return $responseData;
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
    public function getDiagnosisHistory(string $userId)
    {
        try {
            $response = Http::timeout($this->timeout)
                    ->get($this->fastApiUrl . '/diagnosis-history' ,[
                        'user_id' => $userId
                    ]);

            if ($response->successful()) 
            {
                if (empty($responseData['data']['data'])) 
                {
                    return 'NoDiagnosisHistory';
                }
                return $response->json();
            }

            Log::error('FastAPI get diagnosis history failed', [ 'body' => $response->body()]);
            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (getDiagnosisHistory): ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (getDiagnosisHistory): ' . $e->getMessage());
            return null;
        }
    }

    // //////////////////////////////////////////////////////////////////////////////////////////////
    public function generateReport(string $sessionId)
    {
        try {
            $response = Http::timeout($this->reportTimeout)
                ->post($this->fastApiUrl."/generate-report/{$sessionId}");

            if ($response->successful()) {
                return $response->json();
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

    public function downloadReport(string $sessionId)
    {
        try {
            $response = Http::timeout($this->reportTimeout)
                ->get($this->fastApiUrl."/reports/{$sessionId}/download");

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

    public function previewReport(string $sessionId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->fastApiUrl."/reports/{$sessionId}/preview");

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

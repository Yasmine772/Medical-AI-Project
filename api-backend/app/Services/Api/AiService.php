<?php

namespace App\Services\Api;

use App\Models\DiagnosisSession;
use App\Models\User;
use App\Services\Api\AuthService;
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
    //------------------------------------------------------------------------------
    private function isProfileComplete(User $user): bool
    {
        $userService = app(AuthService::class);
        $userWithProfile = $userService->getUserProfile($user);
        $profile = $userWithProfile->profile;

        if (!$profile) {
            return false;
        }
        return !empty($profile->gender)
            && !is_null($profile->is_smoker)
            && !is_null($profile->has_diabetes)
            && !is_null($profile->has_hypertension)
            && !is_null($profile->is_pregnant)
            && !empty($profile->activity_level);
    }
        //*********************************** */
    private function getActiveDiagnosisData(User $user, array $requestData): array
    {
        $userService = app(AuthService::class);
        $userWithProfile = $userService->getUserProfile($user);
        $profile = $userWithProfile->profile;
        $profileComplete = $this->isProfileComplete($user);

        if ($profile && $profileComplete) {

            return [
                'gender' => $profile->gender,
                'is_smoker' => (bool) $profile->is_smoker,
                'has_diabetes' => (bool) $profile->has_diabetes,
                'has_hypertension' => (bool) $profile->has_hypertension,
                'is_pregnant' => (bool) $profile->is_pregnant,
                'activity_level' => $profile->activity_level,
                'from_profile' => true,
            ];
        }

        return [
            'gender' => $requestData['gender'],
            'is_smoker' => (bool) $requestData['is_smoker'],
            'has_diabetes' => (bool) $requestData['has_diabetes'],
            'has_hypertension' => (bool) $requestData['has_hypertension'],
            'is_pregnant' => (bool) $requestData['is_pregnant'],
            'activity_level' => $requestData['activity_level'],
            'from_profile' => false,
        ];
    }
        //*********************************** */
    private function saveDiagnosisData(User $user, array $data): void
    {
        $userService = app(AuthService::class);
        $userService->updateProfile(
            $user,
            [
                'gender' => $data['gender'] ?? null,
                'is_smoker' => $data['is_smoker'] ?? false,
                'has_diabetes' => $data['has_diabetes'] ?? false,
                'has_hypertension' => $data['has_hypertension'] ?? false,
                'is_pregnant' => $data['is_pregnant'] ?? false,
                'activity_level' => $data['activity_level'] ?? 'moderate',
            ],
            null,  
            true   // isMedicalOnly = true
        );
    }
        //*********************************** */
    public function startDiagnosis(User $user, array $requestData, string $assessmentFor = 'myself'): ?array {

        $activeData = $this->getActiveDiagnosisData($user, $requestData);

        if ($activeData['from_profile']) {
            $profileComplete = $this->isProfileComplete($user);

            if (!$profileComplete) {
                return [
                    'error' => 'Please complete your medical profile first before starting diagnosis.',
                ];
            }
        } else {
            $this->saveDiagnosisData($user, $activeData);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['x_user_id' => (string) $user->id])
                ->post($this->fastApiUrl . '/diagnosis/start', [
                    'gender' => $activeData['gender'],
                    'is_smoker' => $activeData['is_smoker'],
                    'has_diabetes' => $activeData['has_diabetes'],
                    'has_hypertension' => $activeData['has_hypertension'],
                    'is_pregnant' => $activeData['is_pregnant'],
                    'activity_level' => $activeData['activity_level'],
                    'assessment_for' => $assessmentFor,
                ]);

            if ($response->successful()) {
                $result = $response->json();

                $diagnosisSession = DiagnosisSession::create([
                    'status' => 'ACTIVE', 
                    'pdf_file_path' => null,
                    'user_id' => $user->id,
                    'started_at' => now()
                ]);

                session(['ai_session_id' => $result['data']['session_id']]);
                session(['diagnosis_session_id' => $diagnosisSession->id]);

                if (isset($result['data'])) {
                    $result['data']['profile_source'] = $activeData['from_profile'] ? 'saved' : 'new';
                    $result['data']['profile_complete'] = $this->isProfileComplete($user);
                }

                return $result;
            }

        Log::error('FastAPI start diagnosis failed', [
            'status' => $response->status(),
            'user_id' => $user->id,
            'body' => $response->body(),
        ]);
            return null;
        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (startDiagnosis): ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (startDiagnosis): ' . $e->getMessage());
            return null;
        }
    }
    //*********************************************** */
    public function searchSymptoms(string $query): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->fastApiUrl . '/symptoms', ['q' => $query]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FastAPI search symptoms failed', [
                'status' => $response->status(),
                'query' => $query,
                'body' => $response->body(),
            ]);
            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (searchSymptoms): ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (searchSymptoms): ' . $e->getMessage());
            return null;
        }
    }
    //*********************************************** */
    public function getSymptomQuestions($data , string $sessionId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->fastApiUrl . '/symptoms/questions', [
                    'session_id' => $sessionId,
                    'symptom_name' => $data['symptom_name'],
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FastAPI get symptom questions failed', [
                'session_id' => $sessionId,
                'symptom_name' => $data['symptom_name'],
            ]);
            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (getSymptomQuestions): ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (getSymptomQuestions): ' . $e->getMessage());
            return null;
        }
    }
    //*********************************************** */
    public function submitSymptomAnswers($data, $sessionId = null): ?array {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->fastApiUrl . '/symptoms/answers', [
                    'session_id' => $sessionId,
                    'symptom_name' => $data['symptom_name'],
                    'answers' => $data['answers'],
                    'symptoms_complete' => $data['symptoms_complete'],
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FastAPI submit symptom answers failed', [
                'status' => $response->status(),
                'session_id' => $sessionId,
                'symptom_name' => $data['symptom_name'],
                'body' => $response->body(),
            ]);
            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (submitSymptomAnswers): ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (submitSymptomAnswers): ' . $e->getMessage());
            return null;
        }
    }
    //*********************************************** */
    public function getNextDiagnosisQuestion(string $sessionId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->fastApiUrl . '/follow-up/next', [
                    'session_id' => $sessionId,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FastAPI get next diagnosis question failed', [
                'session_id' => $sessionId,
                'body' => $response->body(),
            ]);
            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (getNextDiagnosisQuestion): ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (getNextDiagnosisQuestion): ' . $e->getMessage());
            return null;
        }
    }

    //********************************************* */
    public function submitDiagnosisAnswer(string $sessionId, string $questionId, string $answer): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->fastApiUrl . '/follow-up/answer', [
                    'session_id' => $sessionId,
                    'question_id' => $questionId,
                    'answer' => $answer,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FastAPI submit diagnosis answer failed', [
                'status' => $response->status(),
                'session_id' => $sessionId,
                'question_id' => $questionId,
                'body' => $response->body(),
            ]);
            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (submitDiagnosisAnswer): ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (submitDiagnosisAnswer): ' . $e->getMessage());
            return null;
        }
    }

 //************************************************************ */
    public function getDiagnosisHistory(string $userId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['x_user_id' => $userId])
                ->get($this->fastApiUrl . '/diagnose/history');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FastAPI get diagnosis history failed', [
                'status' => $response->status(),
                'user_id' => $userId,
                'body' => $response->body(),
            ]);
            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout (getDiagnosisHistory): ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('FastAPI error (getDiagnosisHistory): ' . $e->getMessage());
            return null;
        }
    }

////////////////////////////////////////////////////////////////////////////////////////////////
    public function generateReport(string $sessionId)
    {
        try {
            $response = Http::timeout($this->reportTimeout)
                ->post($this->fastApiUrl . "/generate-report/{$sessionId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FastAPI generate-report failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI report timeout: ' . $e->getMessage());
            return null;

        } catch (\Exception $e) {
            Log::error('FastAPI report error: ' . $e->getMessage());
            return null;
        }
    }

    public function downloadReport(string $sessionId)
    {
        try {
            $response = Http::timeout($this->reportTimeout)
                ->get($this->fastApiUrl . "/reports/{$sessionId}/download");

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
            Log::error('FastAPI download timeout: ' . $e->getMessage());
            return null;

        } catch (\Exception $e) {
            Log::error('FastAPI download error: ' . $e->getMessage());
            return null;
        }
    }

    public function previewReport(string $sessionId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->fastApiUrl . "/reports/{$sessionId}/preview");

            if ($response->successful()) {
                return response()->make(
                    $response->body(),
                    200,
                    ['Content-Type' => 'text/html; charset=utf-8']
                );
            }

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI preview timeout: ' . $e->getMessage());
            return null;

        } catch (\Exception $e) {
            Log::error('FastAPI preview error: ' . $e->getMessage());
            return null;
        }
    }
}
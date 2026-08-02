<?php

namespace App\Services\Api;

use App\Models\DiagnosisSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiagnosisDataService
{
    protected string $fastApiUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->fastApiUrl = config('services.fastapi.url');
        $this->timeout = config('services.fastapi.timeout', 10);
    }

    public function fetchFromFastApi(string $sessionHash, string $languageCode = 'ar'): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->fastApiUrl."/reports/{$sessionHash}/preview", ['language_code' => $languageCode]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('DiagnosisDataService: preview failed', [
                'session_hash' => $sessionHash,
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::warning('DiagnosisDataService: FastAPI unavailable', [
                'session_hash' => $sessionHash,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function format(?array $preview): array
    {
        if (!$preview) {
            return [
                'patient'  => null,
                'symptoms' => [],
                'ai_result'=> [],
                'tips'     => [],
                'pdf_url'  => null,
            ];
        }

        $patientInfo = $preview['patient_info'] ?? [];
        $diagnoses = $preview['diagnoses'] ?? [];

        $patient = [
            'age'    => $patientInfo['age'] ?? null,
            'gender' => $patientInfo['gender'] ?? null,
            'smoker' => $patientInfo['is_smoker'] ?? null,
            'diabetes'    => $patientInfo['has_diabetes'] ?? null,
            'hypertension'=> $patientInfo['has_hypertension'] ?? null,
            'pregnant'    => $patientInfo['is_pregnant'] ?? null,
            'activity_level' => $patientInfo['activity_level'] ?? null,
        ];

        $symptoms = $this->extractSymptoms($preview);

        $aiResult = array_map(function ($d) {
            return [
                'name_ar'     => $d['disease_name_ar'] ?? $d['disease_name_local'] ?? $d['disease_name'] ?? '',
                'name_en'     => $d['disease_name'] ?? $d['disease_name_local'] ?? '',
                'probability' => isset($d['probability'])
                    ? (int) round($d['probability'] * 100)
                    : null,
                'confidence'  => $d['confidence'] ?? null,
                'specialist'  => $d['specialist'] ?? null,
            ];
        }, array_slice($diagnoses, 0, 3));

        $tips = collect();
        if (!empty($preview['advice'])) {
            $tips->push($preview['advice']);
        }
        foreach ($diagnoses as $d) {
            if (!empty($d['advice'])) {
                $tips->push($d['advice']);
            }
        }
        $tips = $tips->unique()->values()->take(5)->toArray();

        return [
            'patient'  => $patient,
            'symptoms' => $symptoms,
            'ai_result'=> $aiResult,
            'tips'     => $tips,
            'pdf_url'  => null,
        ];
    }

    public function store(DiagnosisSession $session, string $languageCode = 'ar'): bool
    {
        $preview = $this->fetchFromFastApi($session->session_hash, $languageCode);

        if (!$preview) {
            return false;
        }

        $data = $this->format($preview);

        $session->update([
            'patient_data' => $data['patient'],
            'symptoms'     => $data['symptoms'],
            'ai_result'    => $data['ai_result'],
            'tips'         => $data['tips'],
        ]);

        return true;
    }

    private function extractSymptoms(array $preview): array
    {
        $conversation = $preview['conversation'] ?? [];

        foreach ($conversation as $msg) {
            if (($msg['role'] ?? '') === 'user') {
                $text = $msg['text'] ?? $msg['content'] ?? '';
                $text = preg_replace('/^Patient (reports|presents with|complains of)[:\-]?\s*/i', '', trim($text));

                if ($text !== '') {
                    $parts = preg_split('/[,،;]+|\band\b|\bمع\b/i', $text);
                    $symptoms = array_values(array_filter(array_map('trim', $parts)));

                    if (!empty($symptoms)) {
                        return $symptoms;
                    }
                }
            }
        }

        return [];
    }
}

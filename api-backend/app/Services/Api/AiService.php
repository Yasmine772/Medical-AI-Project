<?php

namespace app\Services\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected string $fastApiUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->fastApiUrl = config('services.fastapi.url');
        $this->timeout = config('services.fastapi.timeout');
    }
//------------------------------------------------------------------------------

    public function searchSymptoms( string $query)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->fastApiUrl . '/symptoms', ['q' => $query]);

            if ($response->successful()) {
                return $response->json();
            }

            return 'No symptoms found';

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout: ' . $e->getMessage());
            return null;

        } catch (\Exception $e) {
            Log::error('FastAPI error: ' . $e->getMessage());
            return null;
        }
    }

    public function startDiagnose(string $symptom, string $pastDiagnoses, string|int $userId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['X-User-Id' => $userId])
                ->asForm()
                ->post($this->fastApiUrl . '/diagnose/start', [
                    'symptom' => $symptom,
                    'past_diagnoses' => $pastDiagnoses,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout: ' . $e->getMessage());
            return null;

        } catch (\Exception $e) {
            Log::error('FastAPI error: ' . $e->getMessage());
            return null;
        }
    }

    public function continueDiagnose(string|int $sessionId, string $answer)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->fastApiUrl . '/diagnose/continue', [
                    'session_id' => $sessionId,
                    'answer' => $answer,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (ConnectionException $e) {
            Log::error('FastAPI timeout: ' . $e->getMessage());
            return null;

        } catch (\Exception $e) {
            Log::error('FastAPI error: ' . $e->getMessage());
            return null;
        }
    }
}
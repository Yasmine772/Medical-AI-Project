<?php

namespace app\Services\Api;

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
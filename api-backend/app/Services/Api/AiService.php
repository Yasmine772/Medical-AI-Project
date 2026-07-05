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
    public function searchSymptoms($request)
    {
        try {
            $response = Http::timeout($this->timeout)->get($this->fastApiUrl . '/symptoms',
                                                        ['q' => $request['q'] ]);

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
}
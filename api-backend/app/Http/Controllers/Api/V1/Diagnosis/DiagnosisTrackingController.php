<?php

namespace App\Http\Controllers\Api\V1\Diagnosis;

use App\Http\Controllers\Controller;
use App\Services\Api\DiagnosisTrackingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosisTrackingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected DiagnosisTrackingService $trackingService
    ) {}

    public function getTracking(Request $request, string $sessionHash): JsonResponse
    {
        $lang = $request->query('language_code', 'en');
        $data = $this->trackingService->getTrackingData(
            $request->user()->id,
            $sessionHash,
            $lang
        );

        if (!$data) {
            return $this->errorResponse('Session not found', null, 404);
        }

        return $this->successResponse($data, 'Tracking data retrieved', 200);
    }

    public function getUserSessions(Request $request): JsonResponse
    {
        $lang = $request->query('language_code', 'en');
        $sessions = $this->trackingService->getUserSessions(
            $request->user()->id,
            $lang
        );

        return $this->successResponse($sessions, 'Sessions retrieved', 200);
    }
}

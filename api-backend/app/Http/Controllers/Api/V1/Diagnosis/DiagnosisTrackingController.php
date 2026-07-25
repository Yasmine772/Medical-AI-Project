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

    public function getTracking(Request $request, int $sessionId): JsonResponse
    {
        $data = $this->trackingService->getTrackingData(
            $request->user()->id,
            $sessionId
        );

        if (!$data) {
            return $this->errorResponse('Session not found', null, 404);
        }

        return $this->successResponse($data, 'Tracking data retrieved', 200);
    }

    public function getUserSessions(Request $request): JsonResponse
    {
        $sessions = $this->trackingService->getUserSessions(
            $request->user()->id
        );

        return $this->successResponse($sessions, 'Sessions retrieved', 200);
    }
}

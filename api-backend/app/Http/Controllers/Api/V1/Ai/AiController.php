<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Ai\SymptomsRequest;
use App\Http\Requests\User\Ai\StartDiagnoseRequest;
use App\Http\Requests\User\Ai\ContinueDiagnoseRequest;
use App\Services\Api\AiService;
use App\Traits\ApiResponseTrait;

class AiController extends Controller
{
    use ApiResponseTrait;
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    // -------------------------------------------------------------------------------------------

    public function search(SymptomsRequest $request)
    {
        $result = $this->aiService->searchSymptoms($request->query('query', ''));

        return match($result) {
            'No symptoms found' => $this->errorResponse('There are no symptoms found', null, 404),
             null => $this->errorResponse('Search service error. Please check storage/logs/laravel.log for details', null, 503),
            default => $this->successResponse($result, 'Success', 200),
        };
    }

    public function start(StartDiagnoseRequest $request)
    {
        $userId = auth()->id() ?? 'anonymous';
        $result = $this->aiService->startDiagnose(
            $request->input('symptom'),
            $request->input('past_diagnoses', ''),
            $userId
        );

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }

        if (isset($result['session_id'])) {
            session(['ai_session_id' => $result['session_id']]);
        }

        return $this->successResponse($result, 'Success', 200);
    }

    public function continue(ContinueDiagnoseRequest $request)
    {
        $result = $this->aiService->continueDiagnose(
            session('ai_session_id'),
            $request->input('answer')
        );

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }

        if (($result['type'] ?? '') === 'diagnosis') {
            session()->forget('ai_session_id');
        }

        return $this->successResponse($result, 'Success', 200);
    }
}

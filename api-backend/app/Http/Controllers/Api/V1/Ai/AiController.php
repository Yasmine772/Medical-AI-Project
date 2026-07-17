<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Ai\StartDiagnoseRequest;
use App\Http\Requests\User\Ai\SubmitDiagnosisAnswerRequest;
use App\Http\Requests\User\Ai\SubmitSymptomAnswersRequest;
use App\Http\Requests\User\Ai\SymptomQuestionsRequest;
use App\Http\Requests\User\Ai\SymptomsRequest;
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
//------------------------------------------------------------------------------------
    public function startDiagnosis(StartDiagnoseRequest $request)
    {
        $result = $this->aiService->startDiagnosis($request->validated());

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }
        return $this->successResponse($result, 'Diagnosis started successfully', 200);
    }
    //************************************************* */
    public function searchSymptoms(SymptomsRequest $request)
    {
        $result = $this->aiService->searchSymptoms($request->query('q', ''));

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }

        if (isset($result['results']) && empty($result['results'])) {
            return $this->errorResponse('No symptoms found', null, 404);
        }
        return $this->successResponse($result, 'Symptoms retrieved successfully', 200);
    }
//************************************************** */
    public function getSymptomQuestions(SymptomQuestionsRequest $request , $sessionId = null)
    {
        $result = $this->aiService->getSymptomQuestions($request->validated(), $sessionId);

        if ($result === null) {
            return $this->errorResponse(
                'Failed to get symptom questions. Please try again.',
                null,
                503
            );
        }

        if (isset($result['status']) && $result['status'] === 'error') {
            return $this->errorResponse(
                $result['detail'] ?? 'Failed to get symptom questions',
                null,
                400
            );
        }
        return $this->successResponse($result, 'Questions retrieved successfully', 200);
    }
//************************************************** */
    public function submitSymptomAnswers(SubmitSymptomAnswersRequest $request, $sessionId = null)
    {
        $result = $this->aiService->submitSymptomAnswers($request->validated(),$sessionId);

        if ($result === null) {
            return $this->errorResponse(
                'Failed to submit symptom answers. Please try again.',
                null,
                503
            );
        }

        if (isset($result['status']) && $result['status'] === 'error') {
            return $this->errorResponse(
                $result['detail'] ?? 'Failed to submit symptom answers',
                null,
                400
            );
        }
        return $this->successResponse($result, 'Answers submitted successfully', 200);
    }
//************************************************** */
    public function getNextDiagnosisQuestion($sessionId)
    {
        $result = $this->aiService->getNextDiagnosisQuestion($sessionId);

        if ($result === null) {
            return $this->errorResponse(
                'Failed to get next question. Please try again.',
                null,
                503
            );
        }

        if (isset($result['status']) && $result['status'] === 'error') {
            return $this->errorResponse(
                $result['detail'] ?? 'Failed to get next question',
                null,
                400
            );
        }

        return $this->successResponse($result, 'Next question retrieved successfully', 200);
    }
    //**************************************** */
    public function submitDiagnosisAnswer(SubmitDiagnosisAnswerRequest $request)
    {
        $sessionId = $request->input('session_id') ?? session('ai_session_id');
        $questionId = $request->input('question_id');
        $answer = $request->input('answer');

        if (!$sessionId) {
            return $this->errorResponse('Session ID is required. Please start diagnosis first.', null, 400);
        }

        $result = $this->aiService->submitDiagnosisAnswer(
            $sessionId,
            $request->input('question_id'),
            $request->input('answer')
        );

        if ($result === null) {
            return $this->errorResponse(
                'Failed to submit answer. Please try again.',
                null,
                503
            );
        }

        if (isset($result['status']) && $result['status'] === 'error') {
            return $this->errorResponse(
                $result['detail'] ?? 'Failed to submit answer',
                null,
                400
            );
        }

        if (isset($result['data']['type']) && $result['data']['type'] === 'diagnosis') {
            session()->forget('ai_session_id');
        }

        return $this->successResponse($result, 'Answer submitted successfully', 200);
    }
//*************************************************************** */
    public function getDiagnosisHistory()
    {
        $userId = auth()->user->id;

        $result = $this->aiService->getDiagnosisHistory($userId);

        if ($result === null) {
            return $this->errorResponse(
                'Failed to get diagnosis history. Please try again.',
                null,
                503
            );
        }

        return $this->successResponse($result, 'Diagnosis history retrieved successfully', 200);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Ai\DiagnosisAnswerRequest;
use App\Http\Requests\User\Ai\GetNextQuestionRequest;
use App\Http\Requests\User\Ai\StartDiagnoseRequest;
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
    public function getSymptomQuestions(SymptomQuestionsRequest $request)
    {
        $result = $this->aiService->getSymptomQuestions($request->validated());

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }
        return $this->successResponse($result, 'Questions retrieved successfully', 200);
    }
//************************************************** */
    public function getNextDiagnosisQuestion(GetNextQuestionRequest $request)
    {
        $result = $this->aiService->getNextDiagnosisQuestion($request->validated());

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }
        return $this->successResponse($result, 'Next question retrieved successfully', 200);
    }
    //**************************************** */
    public function submitDiagnosisAnswer(DiagnosisAnswerRequest $request)
    {
        $result = $this->aiService->submitDiagnosisAnswer($request->validated());

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }
        return $this->successResponse($result, 'Answer submitted successfully', 200);
    }
//*************************************************************** */
    public function getDiagnosisHistory()
    {
        $result = $this->aiService->getDiagnosisHistory(auth()->user()->id);

        if ($result === null) {
            return $this->errorResponse('You have not any diagnose yet , Please start first diagnose!', null, 503);
        }
        return $this->successResponse($result, 'Diagnosis history retrieved successfully', 200);
    }
}

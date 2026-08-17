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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    use ApiResponseTrait;

    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    // ------------------------------------------------------------------------------------
    public function startDiagnosis(StartDiagnoseRequest $request)
    {
        $result = $this->aiService->startDiagnosis($request->validated());

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }

        return $this->successResponse($result, 'Diagnosis started successfully', 200);
    }

    // ************************************************* */
    public function searchSymptoms(SymptomsRequest $request)
    {
        $result = $this->aiService->searchSymptoms($request->query('q', ''), $request->query('model_name'));

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }

        if (isset($result['results']) && empty($result['results'])) {
            return $this->errorResponse('No symptoms found', null, 404);
        }

        return $this->successResponse($result, 'Symptoms retrieved successfully', 200);
    }

    // ************************************************** */
    public function getSymptomQuestions(SymptomQuestionsRequest $request)
    {
        $result = $this->aiService->getSymptomQuestions($request->validated());

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }

        return $this->successResponse($result, 'Questions retrieved successfully', 200);
    }

    // ************************************************** */
    public function getNextDiagnosisQuestion(GetNextQuestionRequest $request)
    {
        $result = $this->aiService->getNextDiagnosisQuestion($request->validated());

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }

        return $this->successResponse($result, 'Next question retrieved successfully', 200);
    }

    // **************************************** */
    public function submitDiagnosisAnswer(DiagnosisAnswerRequest $request)
    {
        $result = $this->aiService->submitDiagnosisAnswer($request->validated());

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }

        return $this->successResponse($result, 'Answer submitted successfully', 200);
    }

    // *************************************************************** */
    public function getDiagnosisHistory(Request $request)
    {
        $result = $this->aiService->getDiagnosisHistory(auth()->user()->id, $request->query('language_code', 'en'));

        if ($result === null) {
            return $this->errorResponse('Diagnosis service error. Please check storage/logs/laravel.log for details', null, 503);
        }

        return $this->successResponse($result, 'Diagnosis history retrieved successfully', 200);
    }

    // *************************************************************** */
    public function insertJsonFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json',
        ]);

        $file = $request->file('file');
        $response = Http::attach(
            'file',
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        )->post(config('services.fastapi.url') . '/insert/json-file');

        if (!$response->successful()) {
            return $this->errorResponse('Insertion service error', $response->json(), $response->status());
        }

        return $this->successResponse($response->json(), 'Diseases inserted successfully', 200);
    }

    // *************************************************************** */
    public function insertPdfFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf',
        ]);

        $file = $request->file('file');
        $response = Http::attach(
            'file',
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        )->post(config('services.fastapi.url') . '/insert/pdf');

        if (!$response->successful()) {
            return $this->errorResponse('Insertion service error', $response->json(), $response->status());
        }

        return $this->successResponse($response->json(), 'PDF queued successfully', 200);
    }
}

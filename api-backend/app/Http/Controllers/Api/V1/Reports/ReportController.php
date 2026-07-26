<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Services\Api\AiService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponseTrait;

    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function generate(Request $request, string $sessionId)
    {
        $result = $this->aiService->generateReport($sessionId, $request->input('language_code', 'en'));

        if ($result === null) {
            return $this->errorResponse('Report generation service error. Please check storage/logs/laravel.log for details', null, 503);
        }

        return $this->successResponse($result, 'Report generated successfully', 200);
    }

    public function download(Request $request, string $sessionId)
    {
        $result = $this->aiService->downloadReport($sessionId, $request->query('language_code', 'en'));

        if ($result === null) {
            return $this->errorResponse('Report download service error', null, 503);
        }

        return $result;
    }

    public function preview(Request $request, string $sessionId)
    {
        $result = $this->aiService->previewReport($sessionId, $request->query('language_code', 'en'));

        if ($result === null) {
            return $this->errorResponse('Report preview service error', null, 503);
        }

        return $result;
    }
}

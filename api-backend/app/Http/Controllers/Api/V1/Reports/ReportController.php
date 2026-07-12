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

    public function generate(string $sessionId)
    {
        $result = $this->aiService->generateReport($sessionId);

        if ($result === null) {
            return $this->errorResponse('Report generation service error. Please check storage/logs/laravel.log for details', null, 503);
        }

        return $this->successResponse($result, 'Report generated successfully', 200);
    }

    public function download(string $sessionId)
    {
        $result = $this->aiService->downloadReport($sessionId);

        if ($result === null) {
            return $this->errorResponse('Report download service error', null, 503);
        }

        return $result;
    }

    public function preview(string $sessionId)
    {
        $result = $this->aiService->previewReport($sessionId);

        if ($result === null) {
            return $this->errorResponse('Report preview service error', null, 503);
        }

        return $result;
    }
}

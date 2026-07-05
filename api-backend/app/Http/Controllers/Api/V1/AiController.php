<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Ai\searchRequest;
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

    public function search(searchRequest $request)
    {
        $result = $this->aiService->searchSymptoms($request->validated());

        return match($result) {
            'No symptoms found' => $this->errorResponse('There are no symptoms found', null,404),
            null => $this->errorResponse('Search service error. Please check storage/logs/laravel.log for details', null, 503),
            default => $this->successResponse($result , 'Success' , 200),
        };
    }
    
}

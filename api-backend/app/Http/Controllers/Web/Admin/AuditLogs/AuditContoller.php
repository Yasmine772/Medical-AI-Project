<?php

namespace App\Http\Controllers\web\Admin\AuditLogs;

use App\Http\Controllers\Controller;
use App\Services\Web\AuditService;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AuditContoller extends Controller
{ 
    use ApiResponseTrait;

    protected $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    
    }

    /**
     * Display the audit logs with optional filters for event and category.
     * @return \Illuminate\Http\JsonResponse
     */
    public function showLogs(Request $request)
    {
    try {
      $filters = [
            'event' => $request->query('operation'),
            'category' => $request->query('category'),
             'date' => $request->query('date_range')
        ];

        $logs = $this->auditService->getLatestLogs($filters);
        return $this->successResponse($logs, 'Logs retrieved successfully');
    } catch (\Exception $e) {

        Log::error($e->getMessage()); 
        
        return $this->errorResponse('Failed to retrieve logs', $e->getMessage(), 500);
    }
    }
    /**
     * Count the total number of audit logs.
     * @return \Illuminate\Http\JsonResponse
     */

    public function countLogs()
    {
        try{
            $count = $this->auditService->getTotalCount();
            return $this->successResponse(['count' => $count], 'Total count retrieved successfully');
        } 
        catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->errorResponse('Failed to retrieve total count', $e->getMessage(), 500);
        }
    }

    /**
     * Count the total number of change logs.
     * @return \Illuminate\Http\JsonResponse
     */

    public function changeLogs()
    {
        try {
            $changeLogs = $this->auditService->changeLogs();
            return $this->successResponse($changeLogs, 'Change logs retrieved successfully');
        } 
        catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->errorResponse('Failed to retrieve change logs', $e->getMessage(), 500);
        }
    }
}

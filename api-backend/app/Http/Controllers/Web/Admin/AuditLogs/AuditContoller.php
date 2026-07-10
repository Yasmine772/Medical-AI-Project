<?php

namespace App\Http\Controllers\web\Admin\AuditLogs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditContoller extends Controller
{ 

    /**
     * Display the audit logs.
     * @return \Illuminate\Http\JsonResponse
     */
    public function showLogs()
    {
    $logs = Audit::with('user')->latest()->take(50)->get();
    
    return response()->json($logs);
     }
}

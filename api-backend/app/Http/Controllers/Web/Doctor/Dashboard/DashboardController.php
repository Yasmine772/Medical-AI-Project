<?php

namespace App\Http\Controllers\Web\Doctor\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Web\DoctorDashboardService;
use App\Traits\ApiResponseTrait;

class DashboardController extends Controller
{
    use ApiResponseTrait;
    protected DoctorDashboardService $doctorDashboardService;

    public function __construct(DoctorDashboardService $doctorDashboardService)
    {
        $this->doctorDashboardService = $doctorDashboardService;
    }
    //************************************************************************************* */
    public function getDoctorSummary() 
    {
        $doctor = $this->doctorDashboardService->getDoctorSummary();
        if($doctor == 'DoctorNotFound'){
            return $this->errorResponse('Error', 'Doctor not found!', 404);
        }
        return $this->successResponse($doctor , 'Doctor data retrieved successfully', 200);
    }
//*********************************************************************************** */
    public function updateAvailability()
    {
        $doctor = $this->doctorDashboardService->updateAvailability();
        if ($doctor == 'DoctorNotFound') {
            return $this->errorResponse('Doctor not found!', null, 404);
        }
        return $this->successResponse($doctor->is_active, 'Doctor status updated successfully', 200);
    }
}

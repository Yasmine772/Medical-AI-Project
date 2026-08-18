<?php

namespace App\Http\Controllers\Web\Doctor\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

    public function getDoctorProfits()
    {
        $profits = $this->doctorDashboardService->getDoctorProfits();
        if ($profits == 'DoctorNotFound') {
            return $this->errorResponse('Doctor not found!', null, 404);
        }
        return $this->successResponse($profits, 'Doctor profits retrieved successfully', 200);
    }

    public function getDailyProfits()
    {
        $profits = $this->doctorDashboardService->getDailyProfits();
        if ($profits == 'DoctorNotFound') {
            return $this->errorResponse('Doctor not found!', null, 404);
        }
        return $this->successResponse($profits, 'Doctor daily profits retrieved successfully', 200);
    }

    public function getMonthlyProfits()
    {
        $profits = $this->doctorDashboardService->getMonthlyProfits();
        if ($profits == 'DoctorNotFound') {
            return $this->errorResponse('Doctor not found!', null, 404);
        }
        return $this->successResponse($profits, 'Doctor monthly profits retrieved successfully', 200);
    }

    //**************************************** */
    public function todayCases(Request $request)
    {
        $cases = $this->doctorDashboardService->todayCases();
        if($cases == 'DoctorNotFound'){
            return $this->errorResponse('Doctor not found!', null, 404);
        }
        if (!$cases) {
            return $this->errorResponse('There are no cases today!', null, 404);
        }
        return $this->successResponse($cases, 'Today cases retrieved successfully', 200);
    }

    //**************************************** */
    public function monthCases(Request $request)
    {
        $cases = $this->doctorDashboardService->monthCases();
        if($cases == 'DoctorNotFound'){
            return $this->errorResponse('Doctor not found!', null, 404);
        }
        if (!$cases) {
            return $this->errorResponse('There are no cases this month!', null, 404);
        }
        return $this->successResponse($cases, 'Monthly cases retrieved successfully', 200);
    }

    //*************************************** */
    public function recentCompleted()
    {
        $recentCompleted = $this->doctorDashboardService->recentCompletedCases();
        if($recentCompleted == 'DoctorNotFound'){
            return $this->errorResponse('Doctor not found!', null, 404);
        }
        if (!$recentCompleted) {
            return $this->errorResponse('There are no recent completed cases!', null, 404);
        }
        return $this->successResponse($recentCompleted, 'Recent completed cases retrieved successfully', 200);
    }
    //*************************************** */
    public function getCase($id)
    {
        $case = $this->doctorDashboardService->getCase($id);
        if ($case == 'DoctorNotFound') {
            return $this->errorResponse('Doctor not found!', null, 404);
        }
        if (!$case) {
            return $this->errorResponse('Case not found!', null, 404);
        }
        return $this->successResponse($case, 'Case retrieved successfully', 200);
    }
    //*************************************** */
    public function incomingCases()
    {
        $cases = $this->doctorDashboardService->incomingCases();

        if ($cases === 'DoctorNotFound') {
            return $this->errorResponse('Doctor not found!', null, 404);
        }

        return $this->successResponse($cases, 'Incoming cases retrieved successfully', 200);
    }
    //*************************************** */
    public function urgentCases()
    {
        $cases = $this->doctorDashboardService->urgentCases();

        if ($cases === 'DoctorNotFound') {
            return $this->errorResponse('Doctor not found!', null, 404);
        }

        return $this->successResponse($cases, 'Urgent cases retrieved successfully', 200);
    }
    //*************************** */
    public function checkExpired()
    {
        $result = $this->doctorDashboardService->checkExpiredCases();

        if ($result === 'DoctorNotFound') {
            return $this->errorResponse('Doctor not found!', null, 404);
        }
        return $this->successResponse($result, 'Expired cases checked successfully', 200);
    }
    //***************************** */
    public function reassign(Request $request, $id)
    {
        $result = $this->doctorDashboardService->reassignCase($id);

        if ($result === 'DoctorNotFound') {
            return $this->errorResponse('Doctor not found!', null, 404);
        }

        if ($result === 'CaseNotFound') {
            return $this->errorResponse('Case not found!', null, 404);
        }

        if ($result === 'NotAuthorized') {
            return $this->errorResponse('You are not authorized to reassign this case', null, 403);
        }

        if ($result === 'NoDoctorAvailable') {
            return $this->errorResponse('No available doctor found for reassignment', null, 404);
        }

        return $this->successResponse($result, 'Case reassigned successfully', 200);
    }

}


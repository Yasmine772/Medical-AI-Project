<?php

namespace App\Http\Controllers\Web\Admin\DoctorManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Doctor\DoctorRequest;
use App\Http\Requests\Web\Doctor\RejectDoctorRequest;
use App\Services\Web\DoctorService;
use App\Traits\ApiResponseTrait;

class DoctorController extends Controller
{
    use ApiResponseTrait;
    protected DoctorService $doctorService;

    public function __construct(DoctorService $doctorService)
    {
        $this->doctorService = $doctorService;
    }
//------------------------------------------------------------------------------
    /**
     * Get all doctor requests
     * 
     * @return \Illuminate\Http\JsonResponse
     * 
     * @note Returns all doctor requests (pending, approved, rejected)
     */
    public function index()
    {
        $requests = $this->doctorService->index();
        if($requests === null) {
            return $this->errorResponse('No doctor requests yet!', null, 404);
        }
        return $this->successResponse($requests, 'Doctor requests retrieved successfully!', 200);
    }
//************************************************************ */
    /**
     * Get specific doctor request by ID
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @note Returns full details of a single doctor request
     */
    public function show(int $id)
    {
        $doctorReq = $this->doctorService->show($id);
        if($doctorReq === null) {
            return $this->errorResponse('Doctor request not found!', null, 404);    
        }
        return $this->successResponse($doctorReq, 'Doctor request retrieved successfully!', 200); 
    }
//************************************************************ */
    /**
     * Approve a doctor request
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @note Requires Queue Worker ==> php artisan queue:work
     * @note Changes status to 'approved' and creates doctor account
     *  Also sends welcome email to doctor
     */
    public function approve(int $id)
    {
        $doctorReq = $this->doctorService->approve($id);
        if($doctorReq === null) {
            return $this->errorResponse('Doctor request not found!', null, 404);    
        }
        return $this->successResponse($doctorReq, 'Doctor request approved successfully!', 200);
    }
//************************************************************ */
    /**
     * Reject a doctor request
     * 
     * @param int $id 
     * @param RejectDoctorRequest $request ['rejection_reason' => '...']
     * @return \Illuminate\Http\JsonResponse
     * 
     * @note Requires Queue Worker ==> php artisan queue:work
     * @note Sends rejection email to doctor
     */
    public function reject(int $id , RejectDoctorRequest $request)
    {
        $result = $this->doctorService->reject($id, $request->validated());
        if($result === null) {
            return $this->errorResponse('Doctor request not found!', null, 404);    
        }
        return $this->successResponse($result, 'Doctor request rejected successfully!', 200);
    }
    //************************************************************ */
    /**
     * Send new join request
     * 
     * @param DoctorRequest $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @note Requires Queue Worker ==> php artisan queue:work
     */
    public function sendJoinRequest(DoctorRequest $request)
    {
        $doctorRequest = $this->doctorService->sendJoinRequest($request->validated());
        
        if($doctorRequest == null){
            return $this->errorResponse('Error', 'Failed to send doctor request, check laravel.log file for more details!', 500 );
        }
        return $this->successResponse(null, 'Doctor join request sent successfully!',200);
    }

    
}

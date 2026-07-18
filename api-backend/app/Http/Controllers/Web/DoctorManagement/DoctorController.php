<?php

namespace App\Http\Controllers\Web\DoctorManagement;

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
    public function index()
    {
        $requests = $this->doctorService->index();
        if($requests === null) {
            return $this->errorResponse('No doctor requests yet!', null, 404);
        }
        return $this->successResponse($requests, 'Doctor requests retrieved successfully!', 200);
    }
//************************************************************ */
    public function show(int $id)
    {
        $doctorReq = $this->doctorService->show($id);
        if($doctorReq === null) {
            return $this->errorResponse('Doctor request not found!', null, 404);    
        }
        return $this->successResponse($doctorReq, 'Doctor request retrieved successfully!', 200); 
    }
//************************************************************ */
    public function approve(int $id)
    {
        $doctorReq = $this->doctorService->approve($id);
        if($doctorReq === null) {
            return $this->errorResponse('Doctor request not found!', null, 404);    
        }
        return $this->successResponse($doctorReq, 'Doctor request approved successfully!', 200);
    }
//************************************************************ */
    public function reject(int $id , RejectDoctorRequest $request)
    {
        $result = $this->doctorService->reject($id, $request->validated());
        if($result === null) {
            return $this->errorResponse('Doctor request not found!', null, 404);    
        }
        return $this->successResponse($result, 'Doctor request rejected successfully!', 200);
    }
    //************************************************************ */
    public function sendJoinRequest(DoctorRequest $request)
    {
        $doctorRequest = $this->doctorService->sendJoinRequest($request->validated());
        return $this->successResponse($doctorRequest, 'Doctor join request sent successfully!',200);
    }

    
}

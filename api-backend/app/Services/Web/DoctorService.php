<?php

namespace App\Services\Web;

use App\Events\doctorRegisterEvent;
use App\Http\Resources\doctorResource;
use App\Models\DoctorRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorService
{
    public function index()
    {
       $allDoctorRequests = DoctorRequest::all();

       if($allDoctorRequests->isEmpty()) {
           return null; 
       }
        return $allDoctorRequests;
    }
    //************************************************************ */
    public function show(int $id)
    {
        $doctorReq = DoctorRequest::find($id);
        if($doctorReq === null) {
            return null;
        }
        return $doctorReq;
    }
    //*********************************************************** */
    public function approve(int $id)
    {
        $doctorReq = DoctorRequest::find($id);
        if($doctorReq === null) {
            return null;
        }
        $doctorReq->status = 'approved';
        $doctorReq->save();

        event(new DoctorRegisterEvent($doctorReq));

        return new doctorResource($doctorReq);
    }
    //*************************************************************** */
    public function reject(int $id ,array $array)
    {
        $doctorReq = DoctorRequest::find($id);
        if($doctorReq === null) {
            return null;
        }
        $doctorReq->status = 'rejected';
        $doctorReq->rejection_reason = $array['rejection_reason'];
        $doctorReq->save();

        $user = User::where('email', $doctorReq->email)->first();
        if ($user) {
            $user->delete();
        }
        return $doctorReq;
    }
    //************************************************************ */
    public function sendJoinRequest(array $data)
    {
        DB::beginTransaction();

        $fileService = app(FileService::class);

        $licensePath = isset($data['license_file'])
                    ? $fileService->uploadFile($data['license_file'], 'doctor/licenses')
                    : null;

        $cvPath = isset($data['cv_file'])
                    ? $fileService->uploadFile($data['cv_file'], 'doctor/CVs')
                    : null;

        $photoPath = isset($data['photo'])
                    ? $fileService->uploadFile($data['photo'], 'doctor/photos')
                    : null;

        $doctorRequest = DoctorRequest::create([
            'full_name'           => $data['full_name'],
            'email'               => $data['email'],
            'password'            => Hash::make($data['password']),
            'phone'               => $data['phone'] ?? null,
            'specialization'      => $data['specialization'],
            'years_of_experience' => $data['years_of_experience'],
            'clinic_phone'        => $data['clinic_phone'] ?? null,
            'clinic_address'      => $data['clinic_address'] ?? null,
            'license_number'      => $data['license_number'] ?? null,
            'biography'           => $data['biography'] ?? null,
            'photo'               => $photoPath,
            'license_file'        => $licensePath,
            'cv_file'             => $cvPath,
            'status'              => 'pending',
        ]);

        DB::commit();
        
        return new doctorResource($doctorRequest);
    }
}

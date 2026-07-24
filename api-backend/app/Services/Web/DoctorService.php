<?php

namespace App\Services\Web;

use App\Events\doctorRegisterEvent;
use App\Http\Resources\doctorResource;
use App\Jobs\ProcessDoctorApproval;
use App\Jobs\SendRejectionEmailJob;
use App\Jobs\UploadDoctorRequestFiles;
use App\Models\DoctorRequest;
use App\Models\User;
use App\Notifications\NewDoctorRequestNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
        ProcessDoctorApproval::dispatch($doctorReq)->onQueue('default');

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

        SendRejectionEmailJob::dispatch($doctorReq, $doctorReq->rejection_reason)->onQueue('default');

        $user = User::where('email', $doctorReq->email)->first();
        if ($user) {
            $user->delete();
        }
        return $doctorReq;
    }
    //************************************************************ */
    public function sendJoinRequest(array $data)
    {
        try {
            DB::beginTransaction();

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
                'status'              => 'pending',
            ]);

            $tempFiles = [];

            if (isset($data['license_file'])) {
                $tempFiles['license_file'] = $data['license_file']->store('temp/licenses', 'public');
            }

            if (isset($data['cv_file'])) {
                $tempFiles['cv_file'] = $data['cv_file']->store('temp/cvs', 'public');
            }

            if (isset($data['photo'])) {
                $tempFiles['photo'] = $data['photo']->store('temp/photos', 'public');
            }

            DB::commit();

            UploadDoctorRequestFiles::dispatch($doctorRequest->id, $tempFiles)->onQueue('default');

            $admin = User::role('admin')->first();
            $admin->notify(new NewDoctorRequestNotification($doctorRequest));

            return $doctorRequest;

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Doctor request creation failed: ' . $e->getMessage());
            throw $e;
        }
    }
}

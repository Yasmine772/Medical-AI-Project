<?php

namespace App\Listeners;

use App\Events\DoctorRegisterEvent;
use App\Models\Doctor;
use App\Models\DoctorRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateDoctorListener
{
    public function __construct()
    {
        //
    }

    public function handle(DoctorRegisterEvent $event): void
    {
        try {
            DB::beginTransaction();

            $doctorRequest = $event->doctorRequest;

            $d_req = DoctorRequest::where('email', $doctorRequest->email)->first();

            if (!$d_req) {
                Log::error('User not found for doctor registration', [
                    'email' => $doctorRequest->email
                ]);
                return;
            }

            $doctor = Doctor::create([
                'full_name'           => $doctorRequest->full_name,
                'email'               => $doctorRequest->email,
                'password'            => $doctorRequest->password,
                'phone'               => $doctorRequest->phone,
                'specialization'      => $doctorRequest->specialization,
                'years_of_experience' => $doctorRequest->years_of_experience,
                'clinic_phone'        => $doctorRequest->clinic_phone,
                'clinic_address'      => $doctorRequest->clinic_address,
                'license_number'      => $doctorRequest->license_number,
                'biography'           => $doctorRequest->biography,
                'photo'               => $doctorRequest->photo,
                'license_file'        => $doctorRequest->license_file,
                'cv_file'             => $doctorRequest->cv_file,
                'is_active'           => true,
            ]);

            // $user = User::create([
            //     'full_name'  => $doctorRequest->full_name,
            //     'email'      => $doctorRequest->email,
            //     'password'   => $doctorRequest->password,
            // ]);
            // $user->assignRole('doctor');

            DB::commit();
            
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to create doctor record', [
                'error' => $e->getMessage(),
                'email' => $doctorRequest->email ?? 'unknown'
            ]);
        }
    }
}

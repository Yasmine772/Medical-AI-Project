<?php

namespace App\Listeners;

use App\Events\DoctorRegisterEvent;
use App\Mail\DoctorWelcomeMail;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegisterNewDoctorListener
{
    public function handle(DoctorRegisterEvent $event): void
    {
        try {
            DB::beginTransaction();

            $doctorRequest = $event->doctorRequest;
            $user = $event->user;

            $doctor = Doctor::create([
                'user_id' => $user->id,
                'phone' => $doctorRequest->phone,
                'specialization' => $doctorRequest->specialization,
                'years_of_experience' => $doctorRequest->years_of_experience,
                'clinic_phone' => $doctorRequest->clinic_phone,
                'clinic_address' => $doctorRequest->clinic_address,
                'license_number' => $doctorRequest->license_number,
                'license_file' => $doctorRequest->license_file,
                'biography' => $doctorRequest->biography,
                'photo' => $doctorRequest->photo,
                'cv_file' => $doctorRequest->cv_file,
                'is_active' => true,
            ]);
            DB::commit();

            Mail::to($user->email)->send(new DoctorWelcomeMail($user, $doctor));

            $doctorRequest->delete();

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Listener failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}

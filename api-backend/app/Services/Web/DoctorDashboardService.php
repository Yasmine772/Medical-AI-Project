<?php

namespace App\Services\Web;

use App\Models\Doctor;

class DoctorDashboardService 
{
    public function getDoctorSummary()
    {
        $doctor = Doctor::where('user_id', auth()->user()->id)->first();

        if(!$doctor){
            return 'DoctorNotFound';
        }
        return [
            'full_name'    => auth()->user()->full_name, 
            'doctor_photo' => $doctor->photo,
            'date'         => now()->toDateString(),
            'is_available' => $doctor->is_active 
        ];
    }
    //*********************************************** */
    public function updateAvailability()
    {
        $doctor = Doctor::where('user_id', auth()->user()->id)->first();

        if (!$doctor) {
            return 'DoctorNotFound';
        }
        $doctor->update(['is_active' => !$doctor->is_active ]);
        
        return $doctor;
    }

}
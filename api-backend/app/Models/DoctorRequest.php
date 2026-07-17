<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorRequest extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'phone',
        'specialization',
        'years_of_experience',
        'clinic_phone',
        'clinic_address',
        'license_number',
        'biography',
        'photo',
        'cv_file',
        'license_file',
        'status',
        'rejection_reason'
    ];
   

}

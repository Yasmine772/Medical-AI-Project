<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = [
        'full_name',
        'specialization',
        'years_of_experience',
        'photo_path',
        'clinic_phone',
        'is_active'
    ];

    public function diseases()
    {
        return $this->belongsToMany(Disease::class, 'disease_specialists', 'doctor_id', 'disease_id');
    }

}

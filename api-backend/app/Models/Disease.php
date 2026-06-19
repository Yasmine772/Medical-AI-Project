<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    protected $fillable = [
        'name',
        'risk_weight',
        'description',
    ];

    public function symptoms()
    {
        return $this->belongsToMany(Symptom::class, 'disease_symptoms', 'disease_id', 'symptom_id');
    }

    public function advice()
    {
        return $this->belongsToMany(Advice::class, 'disease_advice', 'disease_id', 'advice_id');
    }

    public function doctor()
    {
        return $this->belongsToMany(Doctor::class, 'disease_specialists', 'disease_id', 'doctor_id');
    }

}

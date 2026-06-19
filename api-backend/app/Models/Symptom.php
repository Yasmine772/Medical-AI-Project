<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Symptom extends Model
{
    protected $fillable = [
        'name',
        'category',
    ];

    public function diseases()
    {
        return $this->belongsToMany(Disease::class, 'disease_symptoms', 'symptom_id', 'disease_id');
    }
}

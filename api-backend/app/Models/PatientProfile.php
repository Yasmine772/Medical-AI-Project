<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    protected $fillable = [
        'age',
        'gender',
        'is_smoker',
        'has_diabetes',
        'has_hypertension',
        'is_pregnant',
        'activity_level',
        'last_checkup_date',
        'user_id',
    ];                              

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}

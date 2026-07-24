<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class PatientProfile extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'birth_date',
        'gender',
        'is_smoker',
        'has_diabetes',
        'has_hypertension',
        'is_pregnant',
        'drinks_alcohol',
        'activity_level',
        'last_checkup_date',
        'user_id',
        'occupation'
    ];                              

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'birth_date' => 'date',
        'is_smoker' => 'boolean',
        'has_diabetes' => 'boolean',
        'has_hypertension' => 'boolean',
        'is_pregnant' => 'boolean',
        'drinks_alcohol' => 'boolean',
    ];

}

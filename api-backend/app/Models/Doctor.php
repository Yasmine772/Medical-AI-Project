<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;
    /**
     * The attributes excluded from the audit.
     */
    protected $auditExclude = ['updated_at'];

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

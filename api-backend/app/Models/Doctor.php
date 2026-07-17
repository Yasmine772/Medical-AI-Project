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
        'is_active',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function diseases()
    {
        return $this->belongsToMany(Disease::class, 'disease_specialists', 'doctor_id', 'disease_id');
    }

}

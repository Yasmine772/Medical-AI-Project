<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Doctor extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;
    /**
     * The attributes excluded from the audit.
     */
    protected $auditExclude = ['updated_at'];

    protected $fillable = [
        // 'full_name',
        // 'email',
        // 'password',
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
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPhotoPathAttribute(): ?string
    {
        return $this->photo;
    }

    public function diseases()
    {
        return $this->belongsToMany(Disease::class, 'disease_specialists', 'doctor_id', 'disease_id');
    }

    public function diagnosisSessions()
    {
        return $this->hasMany(DiagnosisSession::class);
    }

    /**
     * Get the doctor's schedules.
     */
    public function schedules()
    {
    return $this->hasMany(DoctorSchedule::class, 'doctor_id');
    }
    


    public function isAvailableAt(Carbon $dateTime): bool
    {
        if (!$this->is_active) {
        return false;
    }
      $dayOfWeek = $dateTime->format('l');
      $timeString = $dateTime->format('H:i:s');

       $schedule = $this->schedules()
        ->where('day_of_week', $dayOfWeek)
        ->where('is_closed', false)
        ->first();

       if (!$schedule) {
        return false; 
    }

      return $timeString >= $schedule->start_time && $timeString <= $schedule->end_time;
    }
}

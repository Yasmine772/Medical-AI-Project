<?php

namespace App\Models;

use App\Notifications\ResetPasswordOTPNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Passwords\CanResetPassword;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable
{
    use HasFactory, Notifiable , HasApiTokens, CanResetPassword , HasRoles;
    use  \OwenIt\Auditing\Auditable;

    /**
     * The attributes excluded from the audit.
     */
    protected $auditExclude = ['password', 'remember_token'];
    
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'role',
        'status',
        'diagnose_num',
        'email_verified_at',
        'avatar',
        'otp',
        'otp_verified_at' ,
        'expires_at',
        'created_at',
        'fcm_token'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

   
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_verified_at' => 'datetime',
            'password' => 'hashed',
            'expires_at' => 'datetime',
        ];
    }


    public function sendPasswordResetNotification($token): void
    {
       $this->notify(new ResetPasswordOTPNotification($token));
    }

    public function profile()
    {
        return $this->hasOne(PatientProfile::class, 'user_id');
    }

    
    /**
     * Get the age attribute based on the birth date.
     * @return int|null
     */
    public function getAgeAttribute()
    {
        if ($this->profile && $this->profile->birth_date) {
            return Carbon::parse($this->profile->birth_date)->age;
        }
        return null;
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function diagnosisSessions()
    {
        return $this->hasMany(DiagnosisSession::class);
    }

    /**
     * Specifies the user's FCM token
     *
     * @return string|array
     */
    public function routeNotificationForFcm()
    {
    return $this->fcm_token;
    }

}

<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable , HasApiTokens;
    
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'role',
        'status',
        'diagnose_num',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

   
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function profile()
    {
        return $this->hasOne(PatientProfile::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function diagnosisSessions()
    {
        return $this->hasMany(DiagnosisSession::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}

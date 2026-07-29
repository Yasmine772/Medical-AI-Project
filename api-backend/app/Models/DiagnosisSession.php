<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosisSession extends Model
{
    protected $fillable = [
        'session_hash',
        'status',
        'pdf_file_path',
        'user_id',
        'started_at',
        'completed_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sessionMessages()
    {
        return $this->hasMany(SessionMessage::class);
    }

    public function payment()
    {
        return $this->hasOne(\App\Models\Payment::class);
    }
}

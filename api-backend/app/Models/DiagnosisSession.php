<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosisSession extends Model
{
    protected $fillable = [
        'session_hash',
        'status',
        'phase',
        'pdf_file_path',
        'user_id',
        'disease_id',
        'doctor_id',
        'started_at',
        'completed_at',
        'doctor_reviewed_at',
        'report_generated_at',
        'doctor_notes',
    ];

    protected $casts = [
        'doctor_reviewed_at'  => 'datetime',
        'report_generated_at' => 'datetime',
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
        return $this->hasOne(Payment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function getWorkflowStepsAttribute(): array
    {
        return [
            [
                'key'     => 'ai_analysis',
                'label'   => 'تحليل الأعراض بالذكاء الاصطناعي',
                'status'  => 'completed',
            ],
            [
                'key'     => 'payment',
                'label'   => 'تم الدفع بنجاح',
                'status'  => 'completed',
            ],
            [
                'key'         => 'doctor_review',
                'label'       => 'مراجعة الطبيب',
                'status'      => $this->doctor_reviewed_at
                    ? 'completed'
                    : ($this->phase === 'doctor_review' ? 'active' : 'pending'),
                'completed_at'=> $this->doctor_reviewed_at,
            ],
            [
                'key'         => 'report',
                'label'       => 'استلام التقرير',
                'status'      => $this->report_generated_at
                    ? 'completed'
                    : ($this->phase === 'report_ready' ? 'active' : 'pending'),
                'completed_at'=> $this->report_generated_at,
            ],
        ];
    }
}

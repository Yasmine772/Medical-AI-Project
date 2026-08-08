<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DiagnosisSession extends Model
{
    public const REVIEW_WINDOW_HOURS = 2;
    public const URGENT_THRESHOLD_MINUTES = 30;

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
        'patient_data',
        'symptoms',
        'ai_result',
        'tips',
        'pdf_url',
        'doctor_edited',
    ];

    protected $casts = [
        'doctor_reviewed_at'  => 'datetime',
        'report_generated_at' => 'datetime',
        'patient_data'        => 'array',
        'symptoms'            => 'array',
        'ai_result'           => 'array',
        'tips'                => 'array',
        'doctor_edited'       => 'boolean',
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

    public function disease()
    {
        return $this->belongsTo(Disease::class);
    }

    public function patient()
    {
    return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewDeadline(): ?Carbon
    {
        $base = $this->report_generated_at;
        if (!$base && $this->started_at) {
            $base = Carbon::parse($this->started_at);
        }
        if (!$base) {
            $base = $this->created_at;
        }

        return $base ? $base->copy()->addHours(self::REVIEW_WINDOW_HOURS) : null;
    }

    public function reviewRemainingMinutes(): ?float
    {
        $deadline = $this->reviewDeadline();

        return $deadline ? now()->diffInMinutes($deadline, false) : null;
    }

    public function isUrgent(): bool
    {
        if ($this->doctor_reviewed_at || in_array($this->phase, ['report_ready', 'completed'])) {
            return false;
        }

        $remaining = $this->reviewRemainingMinutes();

        return $remaining !== null && $remaining < self::URGENT_THRESHOLD_MINUTES;
    }

    public function getWorkflowStepsAttribute(): array
    {
        return $this->workflowSteps('en');
    }

    public function workflowSteps(string $lang = 'en'): array
    {
        $doctorReviewDone = $this->doctor_reviewed_at
            || in_array($this->phase, ['report_ready', 'completed']);

        $labels = [
            'ai_analysis'    => $lang === 'ar' ? 'تحليل الأعراض بالذكاء الاصطناعي' : 'AI Symptom Analysis',
            'payment'        => $lang === 'ar' ? 'تم الدفع بنجاح' : 'Payment Successful',
            'doctor_review'  => $lang === 'ar' ? 'مراجعة الطبيب' : 'Doctor Review',
            'report'         => $lang === 'ar' ? 'استلام التقرير' : 'Receive Report',
        ];

        return [
            [
                'key'     => 'ai_analysis',
                'label'   => $labels['ai_analysis'],
                'status'  => 'completed',
            ],
            [
                'key'     => 'payment',
                'label'   => $labels['payment'],
                'status'  => 'completed',
            ],
            [
                'key'         => 'doctor_review',
                'label'       => $labels['doctor_review'],
                'status'      => $doctorReviewDone
                    ? 'completed'
                    : ($this->phase === 'doctor_review' ? 'active' : 'pending'),
                'completed_at'=> $doctorReviewDone ? $this->doctor_reviewed_at : null,
            ],
            [
                'key'         => 'report',
                'label'       => $labels['report'],
                'status'      => $this->phase === 'completed'
                    ? 'completed'
                    : ($this->phase === 'report_ready' ? 'active' : 'pending'),
                'completed_at'=> $this->phase === 'completed' ? $this->report_generated_at : null,
            ],
        ];
    }
    
    /**
     * Scope to filter diagnosis sessions for a specific doctor.
     */
    public function scopeForDoctor(Builder $query, $doctorId): Builder
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope to filter diagnosis sessions by their phase or status (e.g., urgent, pending review).
     */
    public function scopeByPhase(Builder $query, $phase): Builder
    {
        return $query->where('phase', $phase);
    }

    /**
     * Scope to filter completed diagnosis sessions for today.
     */
    public function scopeCompletedToday(Builder $query): Builder
    {
        return $query->where('phase', 'completed')
                     ->whereDate('updated_at', today());
    }

    /**
     * Scope to filter diagnosis sessions by date (today, this week, this month, etc.).
     */
    public function scopeDateFilter(Builder $query, $filter): Builder
    {
        return match ($filter) {
            'today' => $query->whereDate('created_at', today()),
            'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereMonth('created_at', now()->month),
            default => $query,
        };
    }

}

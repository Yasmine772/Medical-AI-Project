<?php

namespace App\Services\Web;

use App\Models\DiagnosisSession;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;

class DoctorReviewService
{
    private function currentDoctor(): ?Doctor
    {
        return Doctor::where('user_id', auth()->id())->first();
    }

    public function reviews(?string $lang = 'en', ?string $status = null, ?string $dateFilter = null): ?array
    {
        $doctor = $this->currentDoctor();

        if (!$doctor) {
            return null;
        }

        $query = DiagnosisSession::with('user')
            ->where('doctor_id', $doctor->id);

        if ($status === 'pending') {
            $query->where('phase', 'doctor_review')->whereNull('doctor_reviewed_at');
        } elseif ($status === 'completed') {
            $query->whereIn('phase', ['report_ready', 'completed']);
        }

        if ($dateFilter === 'today') {
            $from = now()->startOfDay();
        } elseif ($dateFilter === 'last_7_days') {
            $from = now()->subDays(7);
        } elseif ($dateFilter === 'last_30_days') {
            $from = now()->subDays(30);
        }

        if (isset($from)) {
            $query->where(
                DB::raw('COALESCE(report_generated_at, started_at, created_at)'),
                '>=',
                $from
            );
        }

        $sessions = $query->latest()->get();

        if ($status === 'urgent') {
            $sessions = $sessions->filter(fn ($session) => $session->isUrgent())->values();
        }

        return $sessions
            ->map(fn ($session) => [
                'id'           => $session->id,
                'session_hash' => $session->session_hash,
                'status'       => $session->status,
                'phase'        => $session->phase,
                'patient_name' => $session->user?->full_name,
                'patient'      => $session->patient_data,
                'symptoms'     => $session->symptoms,
                'top_diagnosis' => $session->ai_result[0]['name_ar']
                    ?? $session->ai_result[0]['name_en']
                    ?? null,
                'is_urgent'              => $session->isUrgent(),
                'review_deadline'        => $session->reviewDeadline()?->toDateTimeString(),
                'review_remaining_minutes' => $session->reviewRemainingMinutes(),
                'started_at'         => $session->started_at,
                'doctor_reviewed_at' => $session->doctor_reviewed_at,
                'report_generated_at'=> $session->report_generated_at,
                'doctor_edited'      => $session->doctor_edited,
                'url' => "/doctor/reviews/{$session->session_hash}",
            ])
            ->toArray();
    }

    public function stats(?string $lang = 'en', ?string $dateFilter = 'today'): ?array
    {
        $doctor = $this->currentDoctor();

        if (!$doctor) {
            return null;
        }

        if ($dateFilter === 'today') {
            $from = now()->startOfDay();
        } elseif ($dateFilter === 'last_7_days') {
            $from = now()->subDays(7);
        } elseif ($dateFilter === 'last_30_days') {
            $from = now()->subDays(30);
        } else {
            $from = null;
        }

        $query = DiagnosisSession::where('doctor_id', $doctor->id);

        if ($from) {
            $query->where(
                DB::raw('COALESCE(report_generated_at, started_at, created_at)'),
                '>=',
                $from
            );
        }

        $urgent = 0;
        $pending = 0;
        $completed = 0;

        foreach ($query->get() as $session) {
            $isDone = $session->doctor_reviewed_at
                || in_array($session->phase, ['report_ready', 'completed']);

            if ($isDone) {
                $completed++;
            } elseif ($session->isUrgent()) {
                $urgent++;
            } else {
                $pending++;
            }
        }

        return [
            'date'      => $from ? $from->toDateString() : 'all',
            'total'     => $urgent + $pending + $completed,
            'urgent'    => $urgent,
            'pending'   => $pending,
            'completed' => $completed,
        ];
    }

    public function reviewDetail(string $sessionHash, ?string $lang = 'en'): ?array
    {
        $session = $this->sessionForDoctor($sessionHash);

        if (!$session) {
            return null;
        }

        $patient = $session->patient_data ?? [];
        $profile = $session->user?->profile;

        $patient['blood_type'] = $profile?->blood_type ?? $patient['blood_type'] ?? null;
        $patient['occupation'] = $profile?->occupation ?? $patient['occupation'] ?? null;
        $patient['drinks_alcohol'] = $profile?->drinks_alcohol ?? null;

        return [
            'session' => [
                'id'           => $session->id,
                'session_hash' => $session->session_hash,
                'status'       => $session->status,
                'phase'        => $session->phase,
                'started_at'   => $session->started_at,
                'doctor_reviewed_at'  => $session->doctor_reviewed_at,
                'report_generated_at' => $session->report_generated_at,
                'doctor_edited'       => $session->doctor_edited,
                'doctor_notes'        => $session->doctor_notes,
            ],
            'patient_name' => $session->user?->full_name,
            'patient'  => $patient,
            'symptoms' => $session->symptoms,
            'ai_result'=> $session->ai_result,
            'tips'     => $session->tips,
            'pdf_url'  => $session->pdf_url,
            'doctor_notes' => $session->doctor_notes,
            'workflow_steps' => $session->workflowSteps($lang),
        ];
    }

    public function submitReview(string $sessionHash, array $data): ?array
    {
        $session = $this->sessionForDoctor($sessionHash);

        if (!$session) {
            return null;
        }

        $decision = $data['decision'] ?? 'approve';
        $aiResult = $session->ai_result ?? [];

        if ($decision === 'edit') {
            $aiResult = $this->normalizeEditedDiagnoses($data['ai_result'] ?? []);
        } elseif ($decision === 'new') {
            $aiResult = $this->buildNewDiagnosis($data);
        }

        $session->update([
            'doctor_notes'      => $data['doctor_notes'] ?? '',
            'doctor_reviewed_at'=> now(),
            'doctor_edited'     => $decision !== 'approve',
            'ai_result'         => $aiResult,
            'phase'             => 'report_ready',
        ]);

        // Regenerate the PDF with the reviewed data + doctor footer
        $report = app(\App\Services\Api\AiService::class)->generateDoctorReport($session->refresh(), 'en');

        // Notify the patient (Firebase push + in-app) once the reviewed report is ready
        if ($report !== null) {
            $session->refresh();
            $user = $session->user;

            if ($user && $user->profile?->notifications_enabled !== false) {
                try {
                    $user->notify(new \App\Notifications\ReportReadyNotification($session));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Report ready notification failed: ' . $e->getMessage());
                }
            }
        }

        return $this->reviewDetail($sessionHash, 'en');
    }

    /**
     * Normalize doctor-edited diagnoses (name_en/name_ar + probability 0-100).
     */
    protected function normalizeEditedDiagnoses(array $list): array
    {
        return collect($list)
            ->map(function ($d) {
                $probability = $d['probability'] ?? null;
                if (is_numeric($probability) && $probability > 0 && $probability <= 1) {
                    $probability = (int) round($probability * 100);
                } elseif (is_numeric($probability)) {
                    $probability = (int) $probability;
                } else {
                    $probability = null;
                }

                return [
                    'name_ar'     => $d['name_ar'] ?? $d['disease_name_ar'] ?? $d['name_en'] ?? $d['disease_name'] ?? '',
                    'name_en'     => $d['name_en'] ?? $d['disease_name'] ?? $d['name_ar'] ?? $d['disease_name_ar'] ?? '',
                    'probability' => $probability,
                    'confidence'  => $d['confidence'] ?? ($probability !== null
                        ? ($probability >= 80 ? 'High' : ($probability >= 50 ? 'Medium' : 'Low'))
                        : 'Low'),
                    'specialist'  => $d['specialist'] ?? '',
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Build a single-entry ai_result from a new diagnosis written by the doctor.
     */
    protected function buildNewDiagnosis(array $data): array
    {
        $probability = $data['disease_probability'] ?? null;
        if (is_numeric($probability) && $probability <= 1) {
            $probability = (float) $probability * 100;
        }
        $probability = is_numeric($probability) ? (int) round((float) $probability) : 100;

        $nameEn = $data['disease_name'] ?? $data['disease_name_en'] ?? '';
        $nameAr = $data['disease_name_ar'] ?? $nameEn;

        return [[
            'name_ar'     => $nameAr,
            'name_en'     => $nameEn,
            'probability' => $probability,
            'confidence'  => $data['disease_confidence'] ?? 'High',
            'specialist'  => $data['disease_specialist'] ?? $this->currentDoctor()?->specialization ?? '',
        ]];
    }

    public function sessionForDoctor(string $sessionHash): ?DiagnosisSession
    {
        $doctor = $this->currentDoctor();

        if (!$doctor) {
            return null;
        }

        return DiagnosisSession::with('user')
            ->where('session_hash', $sessionHash)
            ->where('doctor_id', $doctor->id)
            ->first();
    }
}

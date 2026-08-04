<?php

namespace App\Services\Api;

use App\Models\DiagnosisSession;

class DiagnosisTrackingService
{
    public function getTrackingData(int $userId, string $sessionHash, string $lang = 'en'): ?array
    {
        $session = DiagnosisSession::with(['payment', 'doctor.user'])
            ->where('session_hash', $sessionHash)
            ->where('user_id', $userId)
            ->first();

        if (!$session) {
            return null;
        }

        $doctor = $session->doctor;

        return [
            'session' => [
                'id'          => $session->id,
                'session_hash'=> $session->session_hash,
                'status'      => $session->status,
                'phase'       => $session->phase,
                'started_at'  => $session->started_at,
                'completed_at'=> $session->completed_at,
            ],
            'doctor' => $doctor ? [
                'id'             => $doctor->id,
                'full_name'      => $doctor->user?->full_name,
                'specialization' => $doctor->specialization,
                'phone'          => $doctor->phone,
                'message'        => $this->doctorMessage($session, $doctor->user?->full_name, $lang),
            ] : null,
            'payment' => $session->payment ? [
                'status' => $session->payment->status,
                'amount' => $session->payment->amount,
                'paid_at'=> $session->payment->paid_at,
            ] : null,
            'workflow_steps' => $session->workflowSteps($lang),
            'current_step'   => collect($session->workflowSteps($lang))
                ->firstWhere('status', 'active')['key'] ?? null,
            'timestamps' => [
                'doctor_reviewed_at' => $session->doctor_reviewed_at,
                'report_generated_at'=> $session->report_generated_at,
            ],
        ];
    }

    private function doctorMessage(DiagnosisSession $session, ?string $doctorName, string $lang): ?string
    {
        if ($session->phase !== 'doctor_review' || $session->doctor_reviewed_at) {
            return null;
        }

        $name = $doctorName ?? '';
        $title = (preg_match('/^(Dr\.|د\.)/', trim($name))) ? '' : ($lang === 'ar' ? 'د. ' : 'Dr. ');

        if ($lang === 'ar') {
            return trim($title.$name).' عم يراجع تقريرك خلال مدة أقصاها ساعتين';
        }

        return trim($title.$name).' is reviewing your report. You will receive it within 2 hours.';
    }

    public function getUserSessions(int $userId, string $lang = 'en'): array
    {
        return DiagnosisSession::with('doctor.user')
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->map(fn ($session) => [
                'id'           => $session->id,
                'session_hash' => $session->session_hash,
                'phase'        => $session->phase,
                'started_at'   => $session->started_at,
                'doctor' => $session->doctor ? [
                    'id'             => $session->doctor->id,
                    'full_name'      => $session->doctor->user?->full_name,
                    'specialization' => $session->doctor->specialization,
                    'phone'          => $session->doctor->phone,
                ] : null,
                'current_step' => collect($session->workflowSteps($lang))
                    ->firstWhere('status', 'active')['key'] ?? null,
            ])
            ->toArray();
    }
}

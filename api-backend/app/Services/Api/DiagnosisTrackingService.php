<?php

namespace App\Services\Api;

use App\Models\DiagnosisSession;

class DiagnosisTrackingService
{
    public function getTrackingData(int $userId, int $sessionId): ?array
    {
        $session = DiagnosisSession::with(['doctor', 'payment', 'disease'])
            ->where('id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$session) {
            return null;
        }

        return [
            'session' => [
                'id'          => $session->id,
                'status'      => $session->status,
                'phase'       => $session->phase,
                'started_at'  => $session->started_at,
                'completed_at'=> $session->completed_at,
            ],
            'doctor' => $session->doctor ? [
                'full_name'     => $session->doctor->full_name,
                'specialization'=> $session->doctor->specialization,
                'photo'         => $session->doctor->photo,
                'is_active'     => $session->doctor->is_active,
            ] : null,
            'disease' => $session->disease ? [
                'name' => $session->disease->name,
            ] : null,
            'payment' => $session->payment ? [
                'status' => $session->payment->status,
                'amount' => $session->payment->amount,
                'paid_at'=> $session->payment->paid_at,
            ] : null,
            'workflow_steps'   => $session->workflow_steps,
            'current_step'     => collect($session->workflow_steps)
                ->firstWhere('status', 'active')['key'] ?? null,
            'timestamps' => [
                'payment_confirmed_at'     => $session->payment_confirmed_at,
                'ai_analysis_completed_at' => $session->ai_analysis_completed_at,
                'doctor_reviewed_at'       => $session->doctor_reviewed_at,
                'report_generated_at'      => $session->report_generated_at,
            ],
        ];
    }

    public function getUserSessions(int $userId): array
    {
        return DiagnosisSession::with(['doctor', 'disease'])
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->map(fn ($session) => [
                'id'            => $session->id,
                'phase'         => $session->phase,
                'status'        => $session->status,
                'disease_name'  => $session->disease?->name,
                'doctor_name'   => $session->doctor?->full_name,
                'started_at'    => $session->started_at,
                'current_step'  => collect($session->workflow_steps)
                    ->firstWhere('status', 'active')['key'] ?? null,
            ])
            ->toArray();
    }
}

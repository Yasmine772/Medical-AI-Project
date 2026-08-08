<?php

namespace App\Notifications;

use App\Models\DiagnosisSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewDiagnosisAssignedNotification extends Notification
{
    use Queueable;

    protected DiagnosisSession $session;

    public function __construct(DiagnosisSession $session)
    {
        $this->session = $session;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'new_diagnosis_assigned',
            'title' => 'New Diagnosis Assigned',
            'message' => 'A new AI diagnosis has been assigned to you for review.',
            'session_hash' => $this->session->session_hash,
            'patient' => (new \App\Services\Web\DoctorReviewService())->buildPatientData($this->session),
            'symptoms' => $this->session->symptoms,
            'ai_result' => $this->session->ai_result,
            'tips' => $this->session->tips,
            'pdf_url' => $this->session->pdf_url,
            'url' => "/doctor/reviews/{$this->session->session_hash}",
        ];
    }
}

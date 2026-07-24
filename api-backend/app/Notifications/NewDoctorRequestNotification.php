<?php

namespace App\Notifications;

use App\Models\DoctorRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewDoctorRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected DoctorRequest $doctorRequest;

    public function __construct(DoctorRequest $doctorRequest)
    {
        $this->doctorRequest = $doctorRequest;
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
    public function toArray($notifiable): array
    {
        return [
            'id' => $this->doctorRequest->id,
            'full_name' => $this->doctorRequest->full_name,
            'email' => $this->doctorRequest->email,
            'specialization' => $this->doctorRequest->specialization,
            'message' => "New doctor request from {$this->doctorRequest->full_name}",
            'url' => "/admin/doctor-requests/{$this->doctorRequest->id}",
        ];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OTPNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('MediScan')
            ->greeting('Hello!')
            ->line('Use the OTP code below ')
            ->line('**Your OTP Code:** ' . $this->otp)
            ->line('This code is valid for **5 minutes**.')
            ->line('Please note: This OTP will expire in 5 minutes.')
            ->line('Best regards, MediScan Team 💙');
    }
}

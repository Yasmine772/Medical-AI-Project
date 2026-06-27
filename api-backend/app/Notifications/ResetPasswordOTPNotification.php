<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordOTPNotification extends Notification
{
    

    /**
     * Create a new notification instance.
     */
    public function __construct(private string $otp)
    {
        //
    }

        public function via($notifiable): array
    {
        return ['mail'];
    }
     /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

       
        return (new MailMessage)
            ->subject('Reset Your Password - OTP')
            ->greeting('Hello!')
            ->line('You requested to reset your password. Please use the following One-Time Password (OTP) to complete the process:')
            ->line('**' . $this->otp . '**') 
            ->line('This code will expire in 15 minutes.')
            ->line('If you did not request a password reset, no further action is required.')
            ->line('Best regards,')
            ->salutation('MediScan Team 💙');

    }

    

}

<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    

    /**
     * Create a new notification instance.
     */
    public function __construct(private string $token)
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
        $resetUrl = config('app.frontend_url') . '/reset-password?token=' 
                    . $this->token . '&email=' . urlencode($notifiable->email);

            return (new MailMessage)
            ->subject('Reset Your Password')
            ->line('You requested a password reset.')
            ->action('Reset Password', $resetUrl)
            ->line('This link expires in 60 minutes.')
            ->line('If you did not request this, ignore this email.')
            ->line('Best regards, MediScan Team 💙');

    }
    

}

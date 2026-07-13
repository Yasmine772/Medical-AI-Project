<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailCustom extends VerifyEmailBase
{
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('Email Verification Required - MediScan')
            ->greeting('Welcome!')
            ->line('Thank you for registering with MediScan. To complete your registration, please verify your email address.')
            ->action('Verify Email Address', $url)
            ->line('Please note: This verification link will expire in 60 minutes.')
            ->line('If you did not request this, no action is required.')
            ->line('Best regards, MediScan Team 💙');
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Messages\MailMessage;

class DoctorWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $doctorRequest;

    public function __construct($user, $doctorRequest)
    {
        $this->user = $user;
        $this->doctorRequest = $doctorRequest;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to MediAI - Your Doctor Account is Approved!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.doctor-welcome', 
            with: [
                'user' => $this->user,
                'doctorRequest' => $this->doctorRequest,
            ]
        );
    }
}

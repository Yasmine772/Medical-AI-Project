<?php

namespace App\Mail;

use App\Models\DiagnosisSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DoctorDiagnosisMail extends Mailable
{
    use Queueable, SerializesModels;

    public $session;

    public function __construct(DiagnosisSession $session)
    {
        $this->session = $session;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Diagnosis Assigned for Review',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.doctor-diagnosis',
            with: [
                'session' => $this->session,
                'patient' => (new \App\Services\Web\DoctorReviewService())->buildPatientData($this->session),
                'symptoms'=> $this->session->symptoms,
                'aiResult'=> $this->session->ai_result,
                'tips'    => $this->session->tips,
                'pdfUrl'  => $this->session->pdf_url,
            ]
        );
    }
}

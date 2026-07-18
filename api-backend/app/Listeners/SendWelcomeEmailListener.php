<?php

namespace App\Listeners;

use App\Events\DoctorRegisterEvent;
use App\Mail\DoctorWelcomeMail;
use App\Models\DoctorRequest;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailListener
{
    public function handle(DoctorRegisterEvent $event): void
    {
        $doctorRequest = $event->doctorRequest;

        $dReq = DoctorRequest::where('email', $doctorRequest->email)->first();

        if ($dReq) {
            Mail::to($dReq->email)->send(new DoctorWelcomeMail($dReq, $doctorRequest));
        }
    }
}

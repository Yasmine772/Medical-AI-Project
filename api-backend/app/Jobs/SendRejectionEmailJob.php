<?php

namespace App\Jobs;

use App\Models\DoctorRequest;
use App\Mail\DoctorRejectionMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRejectionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $doctorReq;
    protected $rejectionReason;

    public function __construct(DoctorRequest $doctorReq, $rejectionReason)
    {
        $this->doctorReq = $doctorReq;
        $this->rejectionReason = $rejectionReason;
    }

    public function handle()
    {
        Mail::to($this->doctorReq->email)->send(
            new DoctorRejectionMail(
                $this->doctorReq->full_name,
                $this->rejectionReason
            )
        );
    }
}

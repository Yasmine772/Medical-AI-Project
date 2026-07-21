<?php

namespace App\Jobs;

use App\Models\DoctorRequest;
use App\Events\DoctorRegisterEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDoctorApproval implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected DoctorRequest $doctorRequest;

    public function __construct(DoctorRequest $doctorRequest)
    {
        $this->doctorRequest = $doctorRequest;
    }

    public function handle(): void
    {
        $this->doctorRequest->update(['status' => 'approved']);
        event(new DoctorRegisterEvent($this->doctorRequest));
    }
}

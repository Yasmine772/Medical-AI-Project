<?php

namespace App\Jobs;

use App\Models\DoctorRequest;
use App\Events\DoctorRegisterEvent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDoctorApproval implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected DoctorRequest $doctorRequest;
    protected User $user;

    public function __construct(DoctorRequest $doctorRequest , User $user)
    {
        $this->doctorRequest = $doctorRequest;
        $this->user = $user;
    }

    public function handle(): void
    {
        event(new DoctorRegisterEvent($this->doctorRequest, $this->user));
        // $this->doctorRequest->update(['status' => 'approved']);
    }
}

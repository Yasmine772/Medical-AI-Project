<?php

namespace App\Jobs;

use App\Models\DiagnosisSession;
use App\Services\Web\DoctorDashboardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReassignExpiredCases implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $caseId) {}


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $case = DiagnosisSession::find($this->caseId);

        if ($case && $case->phase === 'doctor_review' && $case->doctor_reviewed_at === null) {
            $service = app(DoctorDashboardService::class);
            $service->reassignCase($case->id);
        }
    }
}

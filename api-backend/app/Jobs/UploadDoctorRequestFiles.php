<?php

namespace App\Jobs;

use App\Models\DoctorRequest;
use app\Services\Web\FileService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UploadDoctorRequestFiles implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */

    protected $doctorRequestId;
    protected $filePaths;

    public function __construct(int $doctorRequestId, array $filePaths)
    {
        $this->doctorRequestId = $doctorRequestId;
        $this->filePaths = $filePaths;
    }

    /**
     * Execute the job.
     */
    public function handle(FileService $fileService): void
    {
        $doctorRequest = DoctorRequest::find($this->doctorRequestId);

        if (!$doctorRequest) {
            Log::error('Doctor request not found for file upload', ['id' => $this->doctorRequestId]);
            return;
        }

        $uploadedFiles = [];

        if ($this->filePaths['license_file'] ?? null) {
            $uploadedFiles['license_file_path'] = $fileService->uploadFile(
                $this->filePaths['license_file'],
                'doctor/licenses'
            );
        }

        if ($this->filePaths['cv_file'] ?? null) {
            $uploadedFiles['cv_path'] = $fileService->uploadFile(
                $this->filePaths['cv_file'],
                'doctor/CVs'
            );
        }

        if ($this->filePaths['photo'] ?? null) {
            $uploadedFiles['photo_path'] = $fileService->uploadFile(
                $this->filePaths['photo'],
                'doctor/photos'
            );
        }

        $doctorRequest->update($uploadedFiles);
    }
}

<?php

namespace App\Jobs;

use App\Models\Doctor;
use App\Services\Web\FileService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UploadDoctorProfileFiles implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */

    protected int $doctorId;
    protected array $filePaths;

    public function __construct(int $doctorId, array $filePaths)
    {
        $this->doctorId = $doctorId;
        $this->filePaths = $filePaths;
    }

    /**
     * Execute the job.
     */
    public function handle(FileService $fileService): void
    {
        $doctor = Doctor::find($this->doctorId);

        if (!$doctor) {
            Log::error('Doctor not found for file upload', ['id' => $this->doctorId]);
            return;
        }

        $uploadedFiles = [];

        if ($this->filePaths['license_file'] ?? null) {
            $uploadedFiles['license_file'] = $fileService->moveFile(
                $this->filePaths['license_file'],
                'doctor/licenses'
            );
        }

        if ($this->filePaths['cv_file'] ?? null) {
            $uploadedFiles['cv_file'] = $fileService->moveFile(
                $this->filePaths['cv_file'],
                'doctor/CVs'
            );
        }

        if ($this->filePaths['photo'] ?? null) {
            $uploadedFiles['photo'] = $fileService->moveFile(
                $this->filePaths['photo'],
                'doctor/photos'
            );
        }
        $doctor->update($uploadedFiles);     
    }
}

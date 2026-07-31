<?php

namespace App\Services\Api;

use App\Models\DiagnosisSession;
use App\Models\Doctor;

class DoctorAssignmentService
{
    public function assign(int $diagnosisDbId, string $specialistFromAi): ?int
    {
        $doctor = Doctor::where('specialization', 'LIKE', "%{$specialistFromAi}%")
            ->where('is_active', true)
            ->get()
            ->sortBy(function ($doctor) {
                return $doctor->diagnosisSessions()->where('phase', 'doctor_review')->count();
            })
            ->first();

        if (!$doctor) {
            $doctor = Doctor::where('is_active', true)
                ->get()
                ->sortBy(function ($doctor) {
                    return $doctor->diagnosisSessions()->where('phase', 'doctor_review')->count();
                })
                ->first();
        }

        if (!$doctor) {
            return null;
        }

        DiagnosisSession::where('id', $diagnosisDbId)->update([
            'doctor_id' => $doctor->id,
        ]);

        return $doctor->id;
    }
}

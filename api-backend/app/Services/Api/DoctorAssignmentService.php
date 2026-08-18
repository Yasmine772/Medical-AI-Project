<?php

namespace App\Services\Api;

use App\Models\DiagnosisSession;
use App\Models\Doctor;
use Carbon\Carbon;

class DoctorAssignmentService
{
    public function assign(int $diagnosisDbId, string $specialistFromAi): ?int
    {
        $wanted = strtolower(trim($specialistFromAi));
        $byWorkload = fn ($d) => $d->diagnosisSessions()->where('phase', 'doctor_review')->count();

        // 1) Exact (case-insensitive) specialization match.
        //    Uses equality (not LIKE) so "urologist" does NOT match inside "neurologist".
        $doctor = Doctor::whereRaw('LOWER(specialization) = ?', [$wanted])
            ->where('is_active', true)
            ->get()
            ->sortBy($byWorkload)
            ->first();

        // 2) Fuzzy LIKE fallback when there's no exact match.
        //    Ordered by shortest specialization first, so a search for "urologist"
        //    prefers "Urologist" over the substring-colliding "Neurologist".
        if (!$doctor) {
            $doctor = Doctor::whereRaw('LOWER(specialization) LIKE ?', ['%' . $wanted . '%'])
                ->where('is_active', true)
                ->get()
                ->sortBy(function ($d) {
                    return [
                        strlen($d->specialization),
                        $d->diagnosisSessions()->where('phase', 'doctor_review')->count(),
                    ];
                })
                ->first();
        }

        // 3) Last resort: any active doctor.
        if (!$doctor) {
            $doctor = Doctor::where('is_active', true)
                ->get()
                ->sortBy($byWorkload)
                ->first();
        }

        if (!$doctor) {
            return null;
        }

        DiagnosisSession::where('id', $diagnosisDbId)->update([
            'doctor_id' => $doctor->id,
            'status' => 'COMPLETED',
            
        ]);

        return $doctor->id;
    }
}

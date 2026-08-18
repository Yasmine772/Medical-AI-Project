<?php

namespace App\Services\Web;

use App\Models\Doctor;
use App\Models\PaymentSplit;
use App\Models\DiagnosisSession;
use Illuminate\Support\Facades\Log;

class DoctorDashboardService 
{
    public function getDoctorSummary()
    {
        $doctor = Doctor::where('user_id', auth()->user()->id)->first();

        if(!$doctor){
            return 'DoctorNotFound';
        }
        return [
            'full_name'    => auth()->user()->full_name, 
            'doctor_photo' => $doctor->photo,
            'date'         => now()->toDateString(),
            'is_available' => $doctor->is_active 
        ];
    }
    //*********************************************** */
    public function updateAvailability()
    {
        $doctor = Doctor::where('user_id', auth()->user()->id)->first();

        if (!$doctor) {
            return 'DoctorNotFound';
        }
        $doctor->update(['is_active' => !$doctor->is_active ]);
        
        return $doctor;
    }

    public function getDoctorProfits()
    {
        $doctor = Doctor::where('user_id', auth()->user()->id)->first();

        if (!$doctor) {
            return 'DoctorNotFound';
        }

        $totalCents = (int) PaymentSplit::where('doctor_id', $doctor->id)->sum('doctor_amount');

        return [
            'total_amount' => $totalCents,
            
        ];
    }

    public function getDailyProfits()
    {
        $doctor = Doctor::where('user_id', auth()->user()->id)->first();

        if (!$doctor) {
            return 'DoctorNotFound';
        }

        $dailyCents = (int) PaymentSplit::where('doctor_id', $doctor->id)
            ->whereDate('created_at', now()->toDateString())
            ->sum('doctor_amount');

        return [
            'daily_amount' => $dailyCents,
            'daily_display' => '$' . number_format($dailyCents / 100, 2),
            'currency' => 'usd',
        ];
    }

    public function getMonthlyProfits()
    {
        $doctor = Doctor::where('user_id', auth()->user()->id)->first();

        if (!$doctor) {
            return 'DoctorNotFound';
        }

        $monthlyCents = (int) PaymentSplit::where('doctor_id', $doctor->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('doctor_amount');

        return [
            'monthly_amount' => $monthlyCents,
            'monthly_display' => '$' . number_format($monthlyCents / 100, 2),
            'currency' => 'usd',
        ];
    }

    //************************************ */
    public function todayCases()
    {
        $doctor = Doctor::where('user_id', auth()->user()->id)->first();

        if (!$doctor) {
            return 'DoctorNotFound';
        }
        $todaySessions = DiagnosisSession::where('doctor_id', $doctor->id)
                                        ->whereDate('created_at', today())
                                        ->get();

        if($todaySessions->isEmpty()){
            return null;
        }
        return [
            'total' => $todaySessions->count(),
            'completed' => $todaySessions->where('phase', 'completed')->count(),
            'pending' => $todaySessions->where('phase', 'doctor_review')
                                        ->whereNull('doctor_reviewed_at')
                                        ->count(),
        ];
    }
    //************************ */
    public function monthCases()
    {
        $doctor = Doctor::where('user_id', auth()->user()->id)->first();

        $currentMonth = DiagnosisSession::where('doctor_id', $doctor->id)
                                        ->where('phase', 'completed')
                                        ->whereMonth('updated_at', now()->month)
                                        ->whereYear('updated_at', now()->year)
                                        ->count();

        $lastMonth = DiagnosisSession::where('doctor_id', $doctor->id)
                                    ->where('phase', 'completed')
                                    ->whereMonth('updated_at', now()->subMonth()->month)
                                    ->whereYear('updated_at', now()->subMonth()->year)
                                    ->count();

        return [
            'current_month' => $currentMonth,
            'difference' => $currentMonth - $lastMonth,
        ];
    }
    //************************************ */
    public function recentCompletedCases()
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();

        if (!$doctor) {
            return 'DoctorNotFound'; 
        }

        $cases = DiagnosisSession::where('doctor_id', $doctor->id)
            ->with(['user', 'disease'])
            ->where('phase', 'completed')
            ->whereNotNull('completed_at')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();


        return $cases->map(function ($case) {
            $patientNumber = '#' . $case->user_id;
            $patientName = $case->user?->full_name;

            $diseaseName = $case->disease?->name;

            if (empty($diseaseName)) {
                $aiResult = $case->ai_result;
                if (is_string($aiResult)) {
                    $aiResult = json_decode($aiResult, true);
                }
                if (is_array($aiResult) && count($aiResult) > 0) {
                    $first = $aiResult[0];
                    $diseaseName = $first['disease_name_local']
                                    ?? $first['disease_name']
                                    ?? null;
                }
            }
            // $timeAgo = $this->getTimeAgo($case->completed_at);

            return [
                'id' => $case->id,
                'patient_number' => $patientNumber,
                'patient_name' => $patientName,
                'disease_name' => $diseaseName,
                'status_text' => 'Review Completed',
                // 'time_ago' => $timeAgo,
                'view_url' => "/doctor/case/{$case->id}",
            ];
        })->values()->toArray();
    }

    // private function getTimeAgo($dateTime)
    // {
    //     $diff = abs(now()->diffInMinutes($dateTime));

    //     if ($diff < 1) {
    //         return 'Just now';
    //     } elseif ($diff < 60) {
    //         return $diff . ' minute' . ($diff > 1 ? 's' : '') . ' ago';
    //     } elseif ($diff < 1440) {
    //         $hours = floor($diff / 60);
    //         return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    //     } elseif ($diff < 2880) { 
    //         return 'Yesterday';
    //     } else {
    //         $days = floor($diff / 1440);
    //         return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    //     }
    // }
//*************************************** */
    public function getCase($id)
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();

        if (!$doctor) {
            return 'DoctorNotFound';
        }

        $case = DiagnosisSession::where('doctor_id', $doctor->id)
                                ->with(['user', 'disease'])
                                ->find($id);

        if (!$case) {
            return response()->json([
                'status' => 'error',
                'message' => 'Case not found'
            ], 404);
        }

        $aiResult = $case->ai_result;
        if (is_string($aiResult)) {
            $aiResult = json_decode($aiResult, true);
        }

        $topDiagnosis = null;
        $allDiagnoses = [];

        if (is_array($aiResult) && count($aiResult) > 0) {
            usort($aiResult, function ($a, $b) {
                return $b['probability'] <=> $a['probability'];
            });

            $topDiagnosis = $aiResult[0];
            $allDiagnoses = $aiResult;
        }

        return [
                'id' => $case->id,
                'patient' => [
                    'id' => $case->user_id,
                    'name' => $case->user?->full_name ?? 'null',
                    'email' => $case->user?->email ?? 'null',
                ],
                'status' => $case->phase,
                'status_text' => $case->phase === 'completed' ? 'Review Completed' : 'In Progress',
                'completed_at' => $case->completed_at,

                'diagnosis' => [
                    'final' => $case->disease?->name ?? 'null ',
                    'specialist' => $case->disease?->specialist ?? 'null ',
                ],

                'ai_analysis' => [
                    'top_diagnosis' => $topDiagnosis,
                    'all_diagnoses' => $allDiagnoses,
                ],

                'doctor_notes' => $case->doctor_notes,
                'symptoms' => $case->symptoms,
                'pdf_url' => $case->pdf_url,
            ];
    }

    //*************************************** */
    public function incomingCases()
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();

        if (!$doctor) {
            return 'DoctorNotFound';
        }

        $cases = DiagnosisSession::where('doctor_id', $doctor->id)
            ->with(['user', 'disease'])
            ->where('phase', 'doctor_review')
            ->whereNull('doctor_reviewed_at')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($cases->isEmpty()) {
            return [];
        }

        return $cases->map(function ($case) {

            $diseaseName = $case->disease?->name;
            if (empty($diseaseName)) {
                $aiResult = $case->ai_result;
                if (is_string($aiResult)) {
                    $aiResult = json_decode($aiResult, true);
                }
                if (is_array($aiResult) && count($aiResult) > 0) {
                    $first = $aiResult[0];
                    $diseaseName = $first['disease_name_local']
                                    ?? $first['disease_name']
                                    ?? 'null';
                }
            }

            $symptoms = $case->symptoms;
            if (is_array($symptoms)) {
                $symptoms = implode('، ', $symptoms);
            }

            return [
                'id' => $case->id,
                'session_hash' => $case->session_hash,
                'disease_name' => $diseaseName,
                'probability' => $case->ai_result[0]['probability'] ?? 0,
                'symptoms' => $symptoms ?? 'null',
                'patient' => [
                    'id' => $case->user_id,
                    'name' => $case->user?->full_name ?? 'null',
                    'age' => $case->patient_data['age'] ?? 'null',
                    'gender' => $case->patient_data['gender'] ?? 'null',
                ],
            ];
        })->values()->toArray();
    }
    //*************************************** */
    public function urgentCases()
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();

        if (!$doctor) {
            return 'DoctorNotFound';
        }

        $cases = DiagnosisSession::where('doctor_id', $doctor->id)
            ->with(['user', 'disease'])
            ->where('phase', 'doctor_review')
            ->whereNull('doctor_reviewed_at')
            ->get()
            ->filter(function ($case) {
                return $case->isUrgent();
            })
            ->values();

        if ($cases->isEmpty()) {
            return [];
        }

        return $cases->map(function ($case) {
            $minutesLeft = (int) $case->reviewRemainingMinutes();

            $diseaseName = $case->disease?->name;
            if (empty($diseaseName)) {
                $aiResult = $case->ai_result;
                if (is_string($aiResult)) {
                    $aiResult = json_decode($aiResult, true);
                }
                if (is_array($aiResult) && count($aiResult) > 0) {
                    $first = $aiResult[0];
                    $diseaseName = $first['disease_name_local']
                        ?? $first['disease_name']
                        ?? 'null ';
                }
            }

            $symptoms = $case->symptoms;
            if (is_array($symptoms)) {
                $symptoms = implode('، ', $symptoms);
            }

            return [
                'id' => $case->id,
                'session_hash' => $case->session_hash,
                'disease_name' => $diseaseName,
                'probability' => $case->ai_result[0]['probability'] ?? 0,
                'symptoms' => $symptoms ?? 'null',
                'minutes_left' => $minutesLeft,
                'is_urgent' => true,
                'patient' => [
                    'id' => $case->user_id,
                    'name' => $case->user?->full_name ?? 'null',
                    'age' => $case->patient_data['age'] ?? 'null',
                    'gender' => $case->patient_data['gender'] ?? 'null',
                ],
            ];
        })->values()->toArray();
    }

    //*************************************** */
    public function checkExpiredCases()
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();

        if (!$doctor) {
            return 'DoctorNotFound';
        }

        $expiredCases = DiagnosisSession::where('doctor_id', $doctor->id)
            ->where('phase', 'doctor_review')
            ->whereNull('doctor_reviewed_at')
            ->get()
            ->filter(function ($case) {
                return $case->isReviewExpired();
            });

        if ($expiredCases->isEmpty()) {
            return [
                'message' => 'No expired cases found',
                'reassigned_count' => 0,
                'reassigned_cases' => [],
            ];
        }

        $reassigned = [];

        foreach ($expiredCases as $case) {
            $result = $this->reassignCase($case->id);

            if ($result !== 'NoDoctorAvailable' && $result !== 'NotAuthorized') {
                $reassigned[] = $result;
            }
        }

        return [
            'message' => 'Expired cases checked',
            'reassigned_count' => count($reassigned),
            'reassigned_cases' => $reassigned,
        ];
    }
    //******************************** */
    public function reassignCase($caseId)
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();

        if (!$doctor) {
            return 'DoctorNotFound';
        }

        $case = DiagnosisSession::where('id', $caseId)->first();

        if (!$case) {
            return 'CaseNotFound';
        }

        if ($case->phase !== 'doctor_review' || $case->doctor_reviewed_at !== null) {
            return 'NotAuthorized';
        }

        $specialization = $this->getCaseSpecialization($case);

        $newDoctor = Doctor::where('is_active', true)
            ->where('id', '!=', $case->doctor_id)
            ->where('specialization', $specialization)
            ->whereHas('schedules', function ($query) {
                $query->where('day_of_week', now()->format('l'))
                    ->where('is_closed', false)
                    ->whereTime('start_time', '<=', now()->format('H:i:s'))
                    ->whereTime('end_time', '>=', now()->format('H:i:s'));
            })
            ->withCount(['diagnosisSessions' => function ($query) {
                $query->where('phase', 'doctor_review')
                    ->whereNull('doctor_reviewed_at');
            }])
            ->orderBy('diagnosis_sessions_count', 'asc')
            ->first();

        if (!$newDoctor) {
            $newDoctor = Doctor::where('is_active', true)
                ->where('id', '!=', $case->doctor_id)
                ->where('specialization', $specialization)
                ->withCount(['diagnosisSessions' => function ($query) {
                    $query->where('phase', 'doctor_review')
                        ->whereNull('doctor_reviewed_at');
                }])
                ->orderBy('diagnosis_sessions_count', 'asc')
                ->first();
        }

        if (!$newDoctor) {
            Log::warning('No available doctor for reassignment', [
                'case_id' => $case->id,
                'specialization' => $specialization,
            ]);
            return 'NoDoctorAvailable';
        }

        $oldDoctorId = $case->doctor_id;
        $case->doctor_id = $newDoctor->id;
        $case->phase = 'doctor_review';
        $case->status = 'ACTIVE';
        $case->save();
        
        Log::warning('Case reassigned', [
            'case_id' => $case->id,
            'old_doctor_id' => $oldDoctorId,
            'new_doctor_id' => $newDoctor->id,
        ]);

        return [
            'id' => $case->id,
            'session_hash' => $case->session_hash,
            'old_doctor_id' => $oldDoctorId,
            'new_doctor_id' => $newDoctor->id,
            'new_doctor_name' => $newDoctor->user?->full_name ?? 'Unknown',
            'specialization' => $specialization,
            'reassigned_at' => now(),
        ];
    }

    private function getCaseSpecialization($case): string
    {
        if ($case->disease_id && $case->disease) {
            return $case->disease->specialist;
        }

        $aiResult = $case->ai_result;
        if (is_string($aiResult)) {
            $aiResult = json_decode($aiResult, true);
        }

        if (is_array($aiResult) && count($aiResult) > 0) {
            $first = $aiResult[0];
            if (isset($first['specialist'])) {
                return $first['specialist'];
            }
        }

        return 'General Physician';
    }
}
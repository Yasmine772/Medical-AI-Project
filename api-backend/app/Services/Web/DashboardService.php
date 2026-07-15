<?php 
namespace App\Services\Web;

use App\Models\DiagnosisSession;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Disease;
use App\Models\Symptom;
use App\Models\Advice;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getUserStats()
    {
        return [
            'active_users' => User::where('status', true)->count(),
        ];
    }

    public function getDoctorStats()
    {
        return [
            'active_doctors' => Doctor::where('is_active', true)->count(),
        ];
    }

    public function getDailyDiagnosesCount()
    {
        return DiagnosisSession::where('created_at', '>=', now()->startOfDay())->count();
    }

    public function getNewContentCount()
    {
        $last24Hours = now()->subHours(24);
        return Disease::where('created_at', '>=', $last24Hours)->count() +
               Symptom::where('created_at', '>=', $last24Hours)->count() +
               Advice::where('created_at', '>=', $last24Hours)->count();
    }

    public function getPatientStats()
    {
        return [
            'now_patients' => DiagnosisSession::where('status', 'ACTIVE')->count(),
            'regular_patients' => User::has('diagnosisSessions', '>=', 2)->count(),
        ];
    }

    public function getTopDiseases()
    {
        return DiagnosisSession::query()
            ->join('diseases', 'diagnosis_sessions.disease_id', '=', 'diseases.id')
            ->select('diseases.name as disease_name', DB::raw('count(diagnosis_sessions.id) as total_diagnoses'))
            ->groupBy('diseases.name')
            ->orderBy('total_diagnoses', 'desc')
            ->limit(5)
            ->get();
    }


    public function getSessionStatusStats()
    {
        return [
            'active' => DiagnosisSession::where('status', 'ACTIVE')->count(),
            'completed' => DiagnosisSession::where('status', 'COMPLETED')->count(),
            'pending' => DiagnosisSession::where('status', 'PENDING')->count(),
        ];
    }
}
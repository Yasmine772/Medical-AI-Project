<?php

namespace App\Http\Controllers\Web\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DiagnosisSession;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Disease;
use App\Models\Symptom;
use App\Models\Advice;
use Illuminate\Support\Facades\DB;
use App\Services\Web\DashboardService;
use App\Traits\ApiResponseTrait;
use Exception;

class DashboardController extends Controller
{
    use ApiResponseTrait;
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the current date.
     * @return current date in 'Y-m-d' format.
     */
    public function currentDate()
    {
        return now()->toDateString();
    }

    /*
    * Display the count of active users.
    *@return \Illuminate\Http\JsonResponse
    */
    public function userActiveCount()
    {
        try{   
         $users = $this->dashboardService->getUserStats()['active_users'];
          return $this->successResponse(['count' => $users],'User active count retrieved successfully');     
        }
        catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve user active count'], 500);
        }     
    }

    /**
     * Display the count of active doctors.
     * @return \Illuminate\Http\JsonResponse
     */
    public function DoctorActiveCount()
    {
        try{
            $doctors = $this->dashboardService->getDoctorStats();
            return $this->successResponse(['count' => $doctors], 'Doctor active count retrieved successfully');
        }
        catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve doctor active count'], 500);
        }
    }
    /**
     * Display the count of diagnoses made today.
     * @return \Illuminate\Http\JsonResponse
     */
    public function dailyDiagnosesCount()
    {
     try {
        $diagnosesCount = $this->dashboardService->getDailyDiagnosesCount();
        return $this->successResponse(['count' => $diagnosesCount], 'Daily diagnoses count retrieved successfully');
        } 
        catch (\Exception $e) {
                return response()->json(['error' => 'Failed to retrieve daily diagnoses count'], 500);
            }
    }



    /**
     * Display the count of new content items (diseases, symptoms, advices) added in the last 24 hours.
     * @return int
     */
    public function newContentItemsCount()
    {

        try {
            $newContentCount = $this->dashboardService->getNewContentCount();

            return $this->successResponse(['count' => $newContentCount], 'New content items count retrieved successfully');
        } 
        catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve new content items count'], 500);
        }
    }
   

   /**
    * Display the count of patients currently undergoing diagnosis and those who have completed at least two diagnoses.
    * @return \Illuminate\Http\JsonResponse
    */

   public function typeOfPatientCount()
   {
      try {

        $patientStats = $this->dashboardService->getPatientStats();
        return $this->successResponse($patientStats, 'Patient stats retrieved successfully');
      } 
      catch (\Exception $e) {
        return response()->json(['error' => 'Failed to retrieve patient stats'], 500);
      }
        
   }
   
   /**
    * Display the top 5 diseases based on the number of diagnoses made.
    * @return \Illuminate\Support\Collection
    */
    public function getTopDiseasesByDiagnoses()
    {
        try {
            $topDiseases = $this->dashboardService->getTopDiseases();
            return $this->successResponse($topDiseases, 'Top diseases by diagnoses retrieved successfully');
        } 
        catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve top diseases by diagnoses'], 500);
        }
     }

     /**
      * Display the count of diagnosis sessions based on their status (ACTIVE, COMPLETED, PENDING).
     * @return \Illuminate\Http\JsonResponse
      */
     public function diagnosisSessionsStatusCount()
     {
       try{
          $diagnosisSessions = $this->dashboardService->getSessionStatusStats();
          return $this->successResponse($diagnosisSessions , 'Diagnosis sessions status count retrieved successfully');
       }
       catch(Exception $e)
       {
        return response()->json(['error' => 'Failed to retrieve diagnosis sessions status count'], 500);
       }
     }
     
}
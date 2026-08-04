<?php

namespace App\Http\Controllers\Web\Doctor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Doctor\UpdateWeeklySchedule;
use App\Models\DoctorSchedule;
use App\Traits\ApiResponseTrait;

class DoctorScheduleController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        try {
            $schedules = DoctorSchedule::where('doctor_id', auth()->user()->doctor->id)->get();
            return $this->successResponse($schedules);
        } 
        catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve schedules', 500);
        }
    }

    public function update(UpdateWeeklySchedule $request,int $id)
    {
        try {
            $schedule = DoctorSchedule::findOrFail($id);
            
            // Check if the authenticated user is the owner of the schedule
            if ($schedule->doctor_id !== auth()->user()->doctor->id) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $validatedData = $request->validated();

            $schedule->update($validatedData);

            return $this->successResponse($schedule, 'Schedule updated successfully');
        } 
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Schedule not found', 404);
        } 
        catch (\Exception $e) {
            return $this->errorResponse('Failed to update schedule', 500);
        }
    }
}

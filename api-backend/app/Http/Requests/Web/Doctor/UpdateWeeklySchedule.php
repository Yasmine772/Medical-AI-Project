<?php

namespace App\Http\Requests\Web\Doctor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWeeklySchedule extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'day_of_week' => 'sometimes|string|in:Sun,Mon,Tue,Wed,Thu,Fri,Sat',
        'start_time'=> 'sometimes|nullable|date_format:H:i:s',
        'end_time'=> 'sometimes|nullable|date_format:H:i:s|after:start_time',
        'is_closed'=> 'sometimes|boolean',
        ];
    }
}

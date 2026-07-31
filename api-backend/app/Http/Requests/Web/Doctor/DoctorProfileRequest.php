<?php

namespace App\Http\Requests\Web\Doctor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DoctorProfileRequest extends FormRequest
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
            'full_name'           => 'sometimes|string|max:255',
            'email'               => 'sometimes|email|unique:users',
            'phone'               => 'sometimes|string|size:10|starts_with:09,9',
            'specialization'      => 'sometimes|string|max:255',
            'years_of_experience' => 'sometimes|integer|min:0',
            'clinic_phone'        => 'sometimes|digits_between:7,10',
            'clinic_address'      => 'sometimes|string|max:255',
            'license_number'      => 'sometimes|string|max:50',
            'license_file'        => 'sometimes|file|mimes:pdf|max:2048',
            'biography'           => 'sometimes|string|max:1000',
            'photo'               => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
            'cv_file'             => 'sometimes|file|mimes:pdf|max:2048',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed!',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}

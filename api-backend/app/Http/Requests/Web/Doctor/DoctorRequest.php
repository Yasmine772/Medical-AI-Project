<?php

namespace App\Http\Requests\Web\Doctor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DoctorRequest extends FormRequest
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
            'full_name'           => 'required|string|max:255',
            'email'               => 'required|email|unique:doctor_requests,email',
            'password'            => 'required|string|min:8',
          //  'phone'               => 'required|string|max:10|regex:(09|9)[0-9]{8}$/',
            'phone'               => 'required|string|max:10',

            'specialization'      => 'required|string|max:255',
            'years_of_experience' => 'required|integer|min:0',
          //  'clinic_phone'        => 'nullable|string|max:10|regex:(09|9|0[1-9]|[1-9])[0-9]{7,8}$/',
            'clinic_phone'        => 'nullable|string|max:10',

            'clinic_address'      => 'nullable|string|max:255',
            'license_number'      => 'required|string|max:255',
            'license_file'        => 'required|file|mimes:pdf|max:2048',
            'biography'           => 'required|string|max:1000',
            'photo'               => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'cv_file'             => 'required|file|mimes:pdf|max:2048',
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


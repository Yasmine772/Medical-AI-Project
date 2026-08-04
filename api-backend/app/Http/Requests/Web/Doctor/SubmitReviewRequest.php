<?php

namespace App\Http\Requests\Web\Doctor;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmitReviewRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'decision'                => ['required', 'string', 'in:approve,edit,new'],
            'doctor_notes'            => ['required', 'string'],
            'ai_result'               => ['required_if:decision,edit', 'array'],
            'ai_result.*.name_en'     => ['nullable', 'string'],
            'ai_result.*.name_ar'     => ['nullable', 'string'],
            'ai_result.*.probability' => ['nullable', 'numeric'],
            'ai_result.*.confidence'  => ['nullable', 'string'],
            'ai_result.*.specialist'  => ['nullable', 'string'],
            'disease_name'            => ['required_if:decision,new', 'string'],
            'disease_name_ar'         => ['nullable', 'string'],
            'disease_probability'     => ['nullable', 'numeric'],
            'disease_specialist'      => ['nullable', 'string'],
            'disease_confidence'      => ['nullable', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed!',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}

<?php

namespace App\Http\Requests\User\Ai;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StartDiagnoseRequest extends FormRequest
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
            'gender'           => 'required|string|in:male,female',
            'is_smoker'        => 'required|boolean',
            'has_diabetes'     => 'required|boolean',
            'has_hypertension' => 'required|boolean',
            'is_pregnant'      => 'required|boolean',
            'is_alcoholic'     => 'required|boolean',
            'patient_job'      => 'required|string|max:255',
            'activity_level'   => 'required|string|in:sedentary,moderate,active',
            'assessment_for'   => 'required|string|in:myself,other',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            if (($data['gender']) === 'male' && ($data['is_pregnant']) == 1) {
                $validator->errors()->add('is_pregnant', 'A man cannot be pregnant.');
            }
        });
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



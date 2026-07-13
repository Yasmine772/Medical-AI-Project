<?php

namespace App\Http\Requests\User\Ai;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StartDiagnoseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gender'           => 'required|string|in:male,female',
            'is_smoker'        => 'required|boolean',
            'has_diabetes'     => 'required|boolean',
            'has_hypertension' => 'required|boolean',
            'is_pregnant'      => 'required|boolean',
            'activity_level'   => 'required|string|in:sedentary,moderate,active',
            'assessment_for'   => 'nullable|string|in:myself,child,elderly,other',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            if (($data['gender']) === 'male' && ($data['is_pregnant'] ?? false) === true) {
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

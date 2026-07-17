<?php

namespace App\Http\Requests\User\Ai;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmitSymptomAnswersRequest extends FormRequest
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
            'symptom_name'          => 'required|string|max:255',
            'answers'               => 'required|array|min:1',
            'answers.*.question_id' => 'required|string',
            'answers.*.answer'      => 'required|string',
            'symptoms_complete'     => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->isJson()) {
            $this->merge($this->json()->all());
        }
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

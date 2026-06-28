<?php

namespace App\Http\Requests\User\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class UpdateProfileRequest extends FormRequest
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
            'full_name'        => 'sometimes|string|max:255',
            'avatar'           => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'birth_date'       => 'sometimes|date',
            'gender'           => 'sometimes|in:male,female',
            'is_smoker'        => 'sometimes|boolean',
            'has_diabetes'     => 'sometimes|boolean',
            'has_hypertension' => 'sometimes|boolean',
            'is_pregnant'      => 'sometimes|boolean',
            'activity_level'   => 'sometimes|in:sedentary,moderate,active',
            'last_checkup_date'=> 'sometimes|date',
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

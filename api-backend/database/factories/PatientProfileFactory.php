<?php

namespace Database\Factories;

use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientProfile>
 */
class PatientProfileFactory extends Factory
{
    protected $model = PatientProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-18 years'),
            'is_smoker' => fake()->boolean(),
            'has_diabetes' => fake()->boolean(),
            'has_hypertension' => fake()->boolean(),
            'is_pregnant' => false,
            'drinks_alcohol' => fake()->boolean(),
            'activity_level' => fake()->randomElement(['sedentary', 'light', 'moderate', 'active', 'very_active']),
            'occupation' => fake()->jobTitle(),
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'full_name' => $this->faker->name(),          
        'is_active' => $this->faker->boolean(80), 
        'specialization' => $this->faker->word(), 
        'years_of_experience'=> $this->faker->numberBetween(1, 30),
        'photo_path' => $this->faker->imageUrl(640, 480),
        'clinic_phone' => $this->faker->phoneNumber(),
        ];
    }
}

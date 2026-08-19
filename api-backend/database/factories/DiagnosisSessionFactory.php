<?php

namespace Database\Factories;

use App\Models\DiagnosisSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiagnosisSession>
 */
class DiagnosisSessionFactory extends Factory
{
    protected $model = DiagnosisSession::class;

    public function definition(): array
    {
        return [
            'session_hash' => fake()->uuid(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['ACTIVE', 'COMPLETED', 'CANCELLED']),
            'phase' => fake()->randomElement(['diagnosis', 'doctor_review', 'report_ready', 'completed']),
            'pdf_file_path' => null,
            'disease_id' => null,
            'doctor_id' => null,
            'started_at' => now(),
            'completed_at' => null,
            'doctor_reviewed_at' => null,
            'report_generated_at' => null,
            'doctor_notes' => null,
            'patient_data' => null,
            'symptoms' => null,
            'ai_result' => null,
            'tips' => null,
            'pdf_url' => null,
            'doctor_edited' => false,
        ];
    }
}

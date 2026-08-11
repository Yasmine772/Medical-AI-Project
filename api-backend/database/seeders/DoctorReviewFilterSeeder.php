<?php

namespace Database\Seeders;

use App\Models\DiagnosisSession;
use App\Models\Disease;
use App\Models\Doctor;
use App\Models\User;
use App\Models\PatientProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorReviewFilterSeeder extends Seeder
{
    /**
     * Seed sessions covering all review filter states for a test doctor.
     *
     * Statuses: urgent (< 30 min left of the 2h review window),
     *           pending (phase doctor_review, not urgent),
     *           completed (report_ready / completed).
     *
     * Uses fixed session hashes + updateOrCreate so re-running refreshes
     * the same rows instead of duplicating them.
     */
    public function run(): void
    {
        $doctor = Doctor::whereHas('user', fn ($q) => $q->where('email', 'sara@test.com'))->first();

        if (!$doctor) {
            $this->command->warn('Doctor sara@test.com not found. Run TestTrackingSeeder first.');
            return;
        }

        $user = User::where('email', 'userTest@gmail.com')->first();
        if (!$user) {
            $this->command->warn('Test user not found. Run TestTrackingSeeder first.');
            return;
        }

        $hypertensionId = Disease::where('name', 'Hypertension')->value('id');
        $migraineId = Disease::where('name', 'Migraine')->value('id');

        // AI payloads so the review detail page shows real-looking data
        $aiData = [
            'Hypertension' => [
                // 'patient' => ['age' => 48, 'gender' => 'female', 'smoker' => false, 'diabetes' => false, 'hypertension' => true, 'pregnant' => null, 'activity_level' => 'light'],
                'symptoms' => ['صداع في مؤخرة الرأس', 'دوخة', 'ارتفاع ضغط الدم'],
                'ai_result' => [
                    ['disease_name_local' => 'ارتفاع ضغط الدم الأساسي', 'disease_name' => 'Essential Hypertension', 'probability' => 91, 'confidence' => 'High', 'specialist' => 'Cardiologist'],
                    ['disease_name_local' => 'ارتفاع ضغط الدم الثانوي', 'disease_name' => 'Secondary Hypertension', 'probability' => 6, 'confidence' => 'Low', 'specialist' => 'Cardiologist'],
                    ['disease_name_local' => 'الصداع النصفي', 'disease_name' => 'Migraine', 'probability' => 3, 'confidence' => 'Low', 'specialist' => 'Neurologist'],
                ],
                'tips' => ['قلل من تناول الملح', 'تجنب التوتر والقلق', 'قيس ضغطك في نفس الوقت يومياً'],
            ],
            'Migraine' => [
                // 'patient' => ['age' => 32, 'gender' => 'male', 'smoker' => true, 'diabetes' => false, 'hypertension' => false, 'pregnant' => null, 'activity_level' => 'moderate'],
                'symptoms' => ['صداع نصفي شديد', 'حساسية من الضوء', 'غثيان'],
            'ai_result' => [
                    ['disease_name_local' => 'الصداع النصفي', 'disease_name' => 'Migraine', 'probability' => 84, 'confidence' => 'High', 'specialist' => 'Neurologist'],
                    ['disease_name_local' => 'صداع التوتر', 'disease_name' => 'Tension Headache', 'probability' => 12, 'confidence' => 'Medium', 'specialist' => 'Neurologist'],
                    ['disease_name_local' => 'التهاب الجيوب الأنفية', 'disease_name' => 'Sinusitis', 'probability' => 4, 'confidence' => 'Low', 'specialist' => 'General Physician'],
                ],
                'tips' => ['الراحة في غرفة مظلمة وهادئة', 'شرب كمية كافية من الماء', 'تجنب مثيرات الصداع لفترات طويلة'],
            ],
        ];

        // Key    -> session_hash, phase, timestamps relative to now, disease name
        $cases = [
            'urgent' => [
                'session_hash'        => 'rev-urgent-ht',
                'phase'               => 'doctor_review',
                'status'              => 'ACTIVE',
                'disease'             => 'Hypertension',
                'started_at'          => now()->subHours(3),
                'report_generated_at' => now()->subMinutes(100), // ~20 min left  -> urgent
                'doctor_reviewed_at'  => null,
            ],
            'pending' => [
                'session_hash'        => 'rev-pending-ht',
                'phase'               => 'doctor_review',
                'status'              => 'ACTIVE',
                'disease'             => 'Hypertension',
                'started_at'          => now()->subHours(2),
                'report_generated_at' => now()->subMinutes(20), // ~100 min left -> pending, not urgent
                'doctor_reviewed_at'  => null,
            ],
            'completed_ready' => [
                'session_hash'        => 'rev-completed-ready',
                'phase'               => 'report_ready',
                'status'              => 'ACTIVE',
                'disease'             => 'Migraine',
                'started_at'          => now()->subDays(1),
                'report_generated_at' => now()->subHours(8),
                'doctor_reviewed_at'  => now()->subHours(6),
            ],
            'completed_done' => [
                'session_hash'        => 'rev-completed-done',
                'phase'               => 'completed',
                'status'              => 'COMPLETED',
                'disease'             => 'Hypertension',
                'started_at'          => now()->subDays(2),
                'report_generated_at' => now()->subDays(2)->addHours(2),
                'doctor_reviewed_at'  => now()->subDays(2)->addHours(4),
            ],
        ];

        

        foreach ($cases as $key => $c) {
            $data = $aiData[$c['disease']];
            DiagnosisSession::updateOrCreate(
                ['session_hash' => $c['session_hash']],
                [
                    'status'               => $c['status'],
                    'phase'                => $c['phase'],
                    'user_id'              => $user->id,
                    'disease_id'           => Disease::where('name', $c['disease'])->value('id'),
                    'doctor_id'            => $doctor->id,
                    'started_at'           => $c['started_at'],
                    'report_generated_at'  => $c['report_generated_at'],
                    'doctor_reviewed_at'   => $c['doctor_reviewed_at'],
                    'symptoms'             => $data['symptoms'],
                    'ai_result'            => $data['ai_result'],
                    'tips'                 => $data['tips'],
                ]
            );
            $this->command->info("Seeded review case [{$key}] -> {$c['session_hash']} ({$c['phase']})");
        }
    }
}

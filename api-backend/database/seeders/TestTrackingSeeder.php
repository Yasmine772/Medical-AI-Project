<?php

namespace Database\Seeders;

use App\Models\DiagnosisSession;
use App\Models\Disease;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestTrackingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create or update diseases with specialists
        $diseases = [
            ['name' => 'Diabetes',        'specialist' => 'Endocrinologist'],
            ['name' => 'Hypertension',    'specialist' => 'Cardiologist'],
            ['name' => 'Migraine',        'specialist' => 'Neurologist'],
            ['name' => 'Fungal infection','specialist' => 'Dermatologist'],
            ['name' => 'Allergy',         'specialist' => 'Allergist / Immunologist'],
            ['name' => 'GERD',            'specialist' => 'Gastroenterologist'],
            ['name' => 'Pneumonia',       'specialist' => 'Pulmonologist'],
        ];

        foreach ($diseases as $d) {
            Disease::updateOrCreate(
                ['name' => $d['name']],
                ['specialist' => $d['specialist'], 'risk_weight' => 5, 'description' => '']
            );
        }
        $this->command->info('Seeded ' . count($diseases) . ' diseases with specialists.');

        // 2. Create test doctors with real specializations
        $doctorData = [
            ['full_name' => 'Dr. Ahmed Hassan',   'email' => 'ahmed@test.com',     'specialization' => 'Dermatologist'],
            ['full_name' => 'Dr. Sara Ali',       'email' => 'sara@test.com',      'specialization' => 'Cardiologist'],
            ['full_name' => 'Dr. Omar Yousef',    'email' => 'omar@test.com',      'specialization' => 'Neurologist'],
            ['full_name' => 'Dr. Lina Khaled',    'email' => 'lina@test.com',      'specialization' => 'Gastroenterologist'],
            ['full_name' => 'Dr. Nour Ibrahim',   'email' => 'nour@test.com',      'specialization' => 'Pulmonologist'],
            ['full_name' => 'Dr. Huda Mahmoud',   'email' => 'huda@test.com',      'specialization' => 'Endocrinologist'],
            ['full_name' => 'Dr. Khaled Waleed',  'email' => 'khaled@test.com',    'specialization' => 'General Physician'],
        ];

        foreach ($doctorData as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'full_name' => $d['full_name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('doctor');

            Doctor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'specialization' => $d['specialization'],
                    'is_active' => true,
                    'phone' => '0599' . random_int(100000, 999999),
                    'years_of_experience' => random_int(3, 20),
                ]
            );
        }
        $this->command->info('Seeded ' . count($doctorData) . ' doctors.');

        // 3. Create or update test user 
        /**
         * Note: The test user is created with a known email and password for testing purposes.
         * you should replace the email and password with your own test credentials in a real application
         */
        $yassmin = User::firstOrCreate(
            ['email' => 'TestUser@gmail.com'],
            [
                'full_name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $yassmin->assignRole('patient');

        // 4. Create test diagnosis sessions
        $diseaseIds = Disease::pluck('id', 'name');

        $sessions = [
            [
                'session_hash' => 'test-dm-' . uniqid(),
                'status' => 'ACTIVE',
                'phase' => 'doctor_review',
                'user_id' => $yassmin->id,
                'disease_id' => $diseaseIds['Diabetes'] ?? null,
                'started_at' => now()->subHours(2),
            ],
            [
                'session_hash' => 'test-ht-' . uniqid(),
                'status' => 'ACTIVE',
                'phase' => 'report_ready',
                'user_id' => $yassmin->id,
                'disease_id' => $diseaseIds['Hypertension'] ?? null,
                'started_at' => now()->subDay(),
                'report_generated_at' => now()->subHours(6),
            ],
            [
                'session_hash' => 'test-mg-' . uniqid(),
                'status' => 'COMPLETED',
                'phase' => 'completed',
                'user_id' => $yassmin->id,
                'disease_id' => $diseaseIds['Migraine'] ?? null,
                'started_at' => now()->subDays(3),
                'report_generated_at' => now()->subDays(2),
                'doctor_reviewed_at' => now()->subDays(2)->addHours(3),
            ],
        ];

        foreach ($sessions as $s) {
            $existing = DiagnosisSession::where('session_hash', $s['session_hash'])->first();
            if (!$existing) {
                DiagnosisSession::create($s);
            }
        }
        $this->command->info('Seeded ' . count($sessions) . ' test sessions.');

       // 5. Create or update doctor weekly schedules by specialization (2 doctors per specialization splitting the week)
        $specializations = Doctor::select('specialization')->distinct()->pluck('specialization');
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        foreach ($specializations as $spec) {
            $specDoctors = Doctor::where('specialization', $spec)->take(2)->get();

            if ($specDoctors->count() > 0) {
                foreach ($days as $index => $day) {
                    if ($index >= 5) {
                        foreach ($specDoctors as $doc) {
                            \App\Models\DoctorSchedule::updateOrCreate(
                                ['doctor_id' => $doc->id, 'day_of_week' => $day],
                                ['start_time' => null, 'end_time' => null, 'is_closed' => true]
                            );
                        }
                        continue;
                    }

                    $assignedDoctor = ($index % 2 == 0) ? $specDoctors[0] : ($specDoctors->count() > 1 ? $specDoctors[1] : $specDoctors[0]);
                    $otherDoctor = ($specDoctors->count() > 1) ? (($index % 2 == 0) ? $specDoctors[1] : $specDoctors[0]) : null;

                    \App\Models\DoctorSchedule::updateOrCreate(
                        ['doctor_id' => $assignedDoctor->id, 'day_of_week' => $day],
                        ['start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_closed' => false]
                    );

                    if ($otherDoctor) {
                        \App\Models\DoctorSchedule::updateOrCreate(
                            ['doctor_id' => $otherDoctor->id, 'day_of_week' => $day],
                            ['start_time' => null, 'end_time' => null, 'is_closed' => true]
                        );
                    }
                }
            }
        }
        $this->command->info('Seeded split weekly schedules for doctors by specialization.');
    }

    
}

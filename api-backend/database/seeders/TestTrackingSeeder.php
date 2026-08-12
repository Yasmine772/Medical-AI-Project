<?php

namespace Database\Seeders;

use App\Models\DiagnosisSession;
use App\Models\Disease;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Models\PatientProfile;
use App\Notifications\NewDiagnosisAssignedNotification;
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

        // 2b. Seed weekly schedules (Sun-Thu 09:00-17:00, closed Fri-Sat)
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        foreach (Doctor::all() as $doctor) {
            foreach ($days as $day) {
                $isClosed = in_array($day, ['Friday', 'Saturday']);
                DoctorSchedule::updateOrCreate(
                    ['doctor_id' => $doctor->id, 'day_of_week' => $day],
                    [
                        'start_time' => $isClosed ? null : '09:00:00',
                        'end_time'   => $isClosed ? null : '17:00:00',
                        'is_closed'  => $isClosed,
                    ]
                );
            }
        }
        $this->command->info('Seeded weekly schedules for all doctors.');

        // 3. Create or update test user 
        /**
         * Note: The test user is created with a known email and password for testing purposes.
         * you should replace the email and password with your own test credentials in a real application
         */
        $user = User::firstOrCreate(
            ['email' => 'userTest@gmail.com'],
            [
                'full_name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole('patient');

        // Create patient profile for the test user
        PatientProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'birth_date' => '1990-05-15',
                'gender' => 'male',
                'is_smoker' => false,
                'has_diabetes' => false,
                'has_hypertension' => false,
                'is_pregnant' => false,
                'drinks_alcohol' => false,
                'activity_level' => 'moderate',
                'blood_type' => 'O+',
                'occupation' => 'engineer',
            ]
        );

        // 4. Create test diagnosis sessions
        $diseaseIds = Disease::pluck('id', 'name');

        // 4a. AI diagnosis payloads per disease (so data shows in notifications without FastAPI)
        $aiData = [
            'Diabetes' => [
                // 'patient' => ['age' => 55, 'gender' => 'male', 'smoker' => false, 'diabetes' => true, 'hypertension' => false, 'pregnant' => null, 'activity_level' => 'sedentary'],
                'symptoms' => ['الشعور بالعطش الشديد', 'كثرة التبول', 'فقدان الوزن المفاجئ'],
                'ai_result' => [
                    ['disease_name_local' => 'السكري من النوع الثاني', 'disease_name' => 'Type 2 Diabetes', 'probability' => 87, 'confidence' => 'High', 'specialist' => 'Endocrinologist'],
                    ['disease_name_local' => 'مقدمات السكري', 'disease_name' => 'Prediabetes', 'probability' => 9, 'confidence' => 'Medium', 'specialist' => 'Endocrinologist'],
                    ['disease_name_local' => 'فرط نشاط الغدة الدرقية', 'disease_name' => 'Hyperthyroidism', 'probability' => 4, 'confidence' => 'Low', 'specialist' => 'Endocrinologist'],
                ],
                'tips' => ['راقب مستوى السكر في الدم بانتظام', 'اتبع نظام غذائي متوازن منخفض السكر', 'مارس الرياضة 30 دقيقة يومياً'],
            ],
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

        $fillAiData = function (DiagnosisSession $session, string $diseaseName) use ($aiData) {
            if (!isset($aiData[$diseaseName])) {
                return;
            }
            $data = $aiData[$diseaseName];
            $session->update([
                'symptoms'     => $data['symptoms'],
                'ai_result'    => $data['ai_result'],
                'tips'         => $data['tips'],
            ]);
        };

        $doctorEmailByDisease = [
            'Diabetes'     => 'huda@test.com',
            'Hypertension' => 'sara@test.com',
            'Migraine'     => 'omar@test.com',
        ];

        $sessions = [
            [
                'session_hash' => 'test-dm-' . uniqid(),
                'status' => 'ACTIVE',
                'phase' => 'doctor_review',
                'user_id' => $user->id,
                'disease_id' => $diseaseIds['Diabetes'] ?? null,
                'doctor_id' => Doctor::whereHas('user', fn ($q) => $q->where('email', $doctorEmailByDisease['Diabetes']))->value('id'),
                'started_at' => now()->subHours(2),
            ],
            [
                'session_hash' => 'test-ht-' . uniqid(),
                'status' => 'ACTIVE',
                'phase' => 'report_ready',
                'user_id' => $user->id,
                'disease_id' => $diseaseIds['Hypertension'] ?? null,
                'doctor_id' => Doctor::whereHas('user', fn ($q) => $q->where('email', $doctorEmailByDisease['Hypertension']))->value('id'),
                'started_at' => now()->subDay(),
                'report_generated_at' => now()->subHours(6),
            ],
            [
                'session_hash' => 'test-mg-' . uniqid(),
                'status' => 'COMPLETED',
                'phase' => 'completed',
                'user_id' => $user->id,
                'disease_id' => $diseaseIds['Migraine'] ?? null,
                'doctor_id' => Doctor::whereHas('user', fn ($q) => $q->where('email', $doctorEmailByDisease['Migraine']))->value('id'),
                'started_at' => now()->subDays(3),
                'report_generated_at' => now()->subDays(2),
                'doctor_reviewed_at' => now()->subDays(2)->addHours(3),
            ],
        ];

        $createdSessions = [];

        foreach ($sessions as $s) {
            $existing = DiagnosisSession::where('session_hash', $s['session_hash'])->first();
            if (!$existing) {
                $session = DiagnosisSession::create($s);
                $diseaseName = Disease::find($session->disease_id)?->name;
                $fillAiData($session, $diseaseName ?? '');
                $createdSessions[] = $session;
            }
        }
        $this->command->info('Seeded ' . count($sessions) . ' test sessions.');

        // 4c. Notify each assigned doctor so the notification is visible right after seeding
        foreach ($createdSessions as $session) {
            $doctor = $session->load('doctor.user')->doctor;
            if ($doctor && $doctor->user) {
                $doctor->user->notify(new NewDiagnosisAssignedNotification($session));
            }
        }
        $this->command->info('Notified ' . count($createdSessions) . ' doctors about new diagnoses.');

        // 4b. Fill AI data into existing sessions (e.g. test-pay-*) so notifications show data without FastAPI
        foreach ($aiData as $diseaseName => $data) {
            $diseaseId = $diseaseIds[$diseaseName] ?? null;
            if (!$diseaseId) {
                continue;
            }
            DiagnosisSession::where('disease_id', $diseaseId)
                ->whereNull('ai_result')
                ->get()
                ->each(fn ($session) => $fillAiData($session, $diseaseName));
        }
        $this->command->info('Filled AI data into sessions by disease.');

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

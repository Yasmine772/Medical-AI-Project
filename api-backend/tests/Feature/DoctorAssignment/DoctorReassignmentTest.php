<?php

namespace Tests\Feature\DoctorAssignment;

use App\Jobs\ReassignExpiredCases;
use App\Models\DiagnosisSession;
use App\Models\Disease;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Services\Api\DiagnosisTrackingService;
use App\Services\Web\DoctorDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorReassignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'doctor', 'guard_name' => 'web']);
        Role::create(['name' => 'patient', 'guard_name' => 'web']);
    }

    private function createDoctor(string $specialization, bool $active = true): Doctor
    {
        $user = User::create([
            'full_name' => "Dr. {$specialization}-" . uniqid(),
            'email' => "doc-" . uniqid() . "@test.com",
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('doctor');

        $doctor = Doctor::create([
            'user_id' => $user->id,
            'specialization' => $specialization,
            'is_active' => $active,
            'phone' => '0598' . random_int(100000, 999999),
            'years_of_experience' => 5,
            'photo' => null,
        ]);

        $today = now()->format('l');
        DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => $today,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_closed' => false,
        ]);

        return $doctor;
    }

    private function createPatient(): User
    {
        $user = User::create([
            'full_name' => 'Test Patient',
            'email' => 'patient-' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('patient');

        return $user;
    }

    private function createSession(User $patient, Doctor $doctor, Disease $disease): DiagnosisSession
    {
        return DiagnosisSession::create([
            'session_hash' => 'test-' . uniqid(),
            'status' => 'ACTIVE',
            'phase' => 'doctor_review',
            'user_id' => $patient->id,
            'disease_id' => $disease->id,
            'doctor_id' => $doctor->id,
            'started_at' => now()->subHours(2),
            'symptoms' => ['fever', 'cough'],
            'ai_result' => [
                ['disease_name' => 'Flu', 'probability' => 85, 'specialist' => 'General Physician'],
            ],
        ]);
    }

    // ─── Reassignment Tests ─────────────────────────────────────

    public function test_reassign_finds_same_specialty_doctor(): void
    {
        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'General Physician']);
        $patient = $this->createPatient();
        $doctor1 = $this->createDoctor('General Physician');
        $doctor2 = $this->createDoctor('General Physician');

        $session = $this->createSession($patient, $doctor1, $disease);

        $service = app(DoctorDashboardService::class);
        $result = $service->reassignCase($session->id);

        $this->assertIsArray($result);
        $this->assertEquals($doctor2->id, $result['new_doctor_id']);
        $this->assertEquals($doctor1->id, $result['old_doctor_id']);

        $session->refresh();
        $this->assertEquals($doctor2->id, $session->doctor_id);
    }

    public function test_reassign_skips_inactive_doctors(): void
    {
        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'General Physician']);
        $patient = $this->createPatient();
        $doctor1 = $this->createDoctor('General Physician');
        $inactiveDoctor = $this->createDoctor('General Physician', active: false);
        $doctor2 = $this->createDoctor('General Physician');

        $session = $this->createSession($patient, $doctor1, $disease);

        $service = app(DoctorDashboardService::class);
        $result = $service->reassignCase($session->id);

        $this->assertIsArray($result);
        $this->assertEquals($doctor2->id, $result['new_doctor_id']);
        $this->assertNotEquals($inactiveDoctor->id, $result['new_doctor_id']);
    }

    public function test_reassign_works_with_case_insensitive_specialization(): void
    {
        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'general physician']);
        $patient = $this->createPatient();
        $doctor1 = Doctor::create([
            'user_id' => User::create([
                'full_name' => 'Dr. Lower',
                'email' => 'lower-' . uniqid() . '@test.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ])->id,
            'specialization' => 'General Physician',
            'is_active' => true,
            'phone' => '0598111111',
            'years_of_experience' => 5,
        ]);

        $user2 = User::create([
            'full_name' => 'Dr. Lower2',
            'email' => 'lower2-' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $user2->assignRole('doctor');
        $doctor2 = Doctor::create([
            'user_id' => $user2->id,
            'specialization' => 'General Physician',
            'is_active' => true,
            'phone' => '0598222222',
            'years_of_experience' => 5,
        ]);

        $session = $this->createSession($patient, $doctor1, $disease);

        $service = app(DoctorDashboardService::class);
        $result = $service->reassignCase($session->id);

        $this->assertIsArray($result);
        $this->assertEquals($doctor2->id, $result['new_doctor_id']);
    }

    public function test_reassign_skips_current_doctor(): void
    {
        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'Cardiologist']);
        $patient = $this->createPatient();
        $doctor1 = $this->createDoctor('Cardiologist');

        $session = $this->createSession($patient, $doctor1, $disease);

        $service = app(DoctorDashboardService::class);
        $result = $service->reassignCase($session->id);

        // Only one active cardiologist (doctor1), so no one else available
        $this->assertEquals('NoDoctorAvailable', $result);
    }

    public function test_reassign_returns_no_doctor_when_none_available(): void
    {
        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'NonexistentSpecialty']);
        $patient = $this->createPatient();
        $doctor1 = $this->createDoctor('Cardiologist');

        $session = $this->createSession($patient, $doctor1, $disease);

        $service = app(DoctorDashboardService::class);
        $result = $service->reassignCase($session->id);

        $this->assertEquals('NoDoctorAvailable', $result);
    }

    public function test_reassign_does_nothing_if_already_reviewed(): void
    {
        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'General Physician']);
        $patient = $this->createPatient();
        $doctor1 = $this->createDoctor('General Physician');

        $session = $this->createSession($patient, $doctor1, $disease);
        $session->update(['doctor_reviewed_at' => now()]);

        $service = app(DoctorDashboardService::class);
        $result = $service->reassignCase($session->id);

        $this->assertEquals('NotAuthorized', $result);
    }

    public function test_reassign_returns_no_doctor_when_all_inactive(): void
    {
        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'Cardiologist']);
        $patient = $this->createPatient();
        $doctor1 = $this->createDoctor('Cardiologist', active: true);
        $doctor2 = $this->createDoctor('Cardiologist', active: false);

        $session = $this->createSession($patient, $doctor1, $disease);

        $service = app(DoctorDashboardService::class);
        $result = $service->reassignCase($session->id);

        $this->assertEquals('NoDoctorAvailable', $result);
    }

    public function test_reassign_falls_back_to_any_active_doctor(): void
    {
        // No exact specialty match, but there's a general doctor available
        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'Rare Specialist']);
        $patient = $this->createPatient();
        $doctor1 = $this->createDoctor('Cardiologist');
        $doctor2 = $this->createDoctor('Neurologist');

        $session = $this->createSession($patient, $doctor1, $disease);

        $service = app(DoctorDashboardService::class);
        $result = $service->reassignCase($session->id);

        $this->assertIsArray($result);
        $this->assertContains($result['new_doctor_id'], [$doctor2->id]);
    }

    // ─── Job Dispatch Tests ─────────────────────────────────────

    public function test_job_is_dispatched_with_75_minute_delay(): void
    {
        Queue::fake();

        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'General Physician']);
        $patient = $this->createPatient();
        $doctor = $this->createDoctor('General Physician');

        $session = DiagnosisSession::create([
            'session_hash' => 'test-' . uniqid(),
            'status' => 'ACTIVE',
            'phase' => 'doctor_review',
            'user_id' => $patient->id,
            'disease_id' => $disease->id,
            'doctor_id' => $doctor->id,
            'started_at' => now(),
        ]);

        ReassignExpiredCases::dispatch($session->id)->delay(now()->addMinutes(75));

        Queue::assertPushed(ReassignExpiredCases::class, function ($job) use ($session) {
            return $job->caseId === $session->id;
        });
    }

    public function test_job_skips_already_reviewed_case(): void
    {
        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'General Physician']);
        $patient = $this->createPatient();
        $doctor1 = $this->createDoctor('General Physician');
        $doctor2 = $this->createDoctor('General Physician');

        $session = $this->createSession($patient, $doctor1, $disease);
        $session->update(['doctor_reviewed_at' => now()]);

        $job = new ReassignExpiredCases($session->id);
        $job->handle();

        $session->refresh();
        $this->assertEquals($doctor1->id, $session->doctor_id);
    }

    // ─── Tracking Tests ─────────────────────────────────────────

    public function test_tracking_shows_reassigned_doctor(): void
    {
        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'General Physician']);
        $patient = $this->createPatient();
        $doctor1 = $this->createDoctor('General Physician');
        $doctor2 = $this->createDoctor('General Physician');

        $session = $this->createSession($patient, $doctor1, $disease);

        // Reassign
        $service = app(DoctorDashboardService::class);
        $service->reassignCase($session->id);

        $session->refresh();

        $trackingService = new DiagnosisTrackingService();
        $tracking = $trackingService->getTrackingData($patient->id, $session->session_hash);

        $this->assertNotNull($tracking);
        $this->assertEquals($doctor2->id, $tracking['doctor']['id']);
        $this->assertEquals($doctor2->user->full_name, $tracking['doctor']['full_name']);
    }

    public function test_tracking_shows_original_doctor_when_not_reassigned(): void
    {
        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'General Physician']);
        $patient = $this->createPatient();
        $doctor = $this->createDoctor('General Physician');

        $session = $this->createSession($patient, $doctor, $disease);

        $trackingService = new DiagnosisTrackingService();
        $tracking = $trackingService->getTrackingData($patient->id, $session->session_hash);

        $this->assertNotNull($tracking);
        $this->assertEquals($doctor->id, $tracking['doctor']['id']);
    }

    public function test_tracking_message_shows_reassigned_doctor_name(): void
    {
        $disease = Disease::create(['name' => 'Flu', 'specialist' => 'General Physician']);
        $patient = $this->createPatient();
        $doctor1 = $this->createDoctor('General Physician');
        $doctor2 = $this->createDoctor('General Physician');

        $session = $this->createSession($patient, $doctor1, $disease);

        $service = app(DoctorDashboardService::class);
        $service->reassignCase($session->id);

        $session->refresh();

        $trackingService = new DiagnosisTrackingService();
        $tracking = $trackingService->getTrackingData($patient->id, $session->session_hash);

        $this->assertStringContainsString($doctor2->user->full_name, $tracking['doctor']['message']);
        $this->assertStringNotContainsString($doctor1->user->full_name, $tracking['doctor']['message']);
    }
}

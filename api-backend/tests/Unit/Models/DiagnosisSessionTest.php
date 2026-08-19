<?php

namespace Tests\Unit\Models;

use App\Models\DiagnosisSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosisSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnosis_session_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $session = DiagnosisSession::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $session->user);
        $this->assertEquals($user->id, $session->user->id);
    }

    public function test_diagnosis_session_has_payment(): void
    {
        $session = DiagnosisSession::factory()->create();

        $this->assertNull($session->payment);
    }

    public function test_diagnosis_session_casts_array_fields(): void
    {
        $session = new DiagnosisSession();

        $this->assertArrayHasKey('patient_data', $session->getCasts());
        $this->assertArrayHasKey('symptoms', $session->getCasts());
        $this->assertArrayHasKey('ai_result', $session->getCasts());
        $this->assertArrayHasKey('tips', $session->getCasts());
    }

    public function test_diagnosis_session_casts_datetime_fields(): void
    {
        $session = new DiagnosisSession();

        $this->assertEquals('datetime', $session->getCasts()['doctor_reviewed_at']);
        $this->assertEquals('datetime', $session->getCasts()['report_generated_at']);
    }

    public function test_diagnosis_session_casts_boolean_field(): void
    {
        $session = new DiagnosisSession();

        $this->assertEquals('boolean', $session->getCasts()['doctor_edited']);
    }

    public function test_diagnosis_session_workflow_steps_returns_array(): void
    {
        $session = DiagnosisSession::factory()->create([
            'phase' => 'doctor_review',
        ]);

        $steps = $session->workflowSteps('en');

        $this->assertIsArray($steps);
        $this->assertCount(4, $steps);
        $this->assertEquals('ai_analysis', $steps[0]['key']);
        $this->assertEquals('payment', $steps[1]['key']);
        $this->assertEquals('doctor_review', $steps[2]['key']);
        $this->assertEquals('report', $steps[3]['key']);
    }

    public function test_diagnosis_session_is_urgent_when_near_deadline(): void
    {
        $session = DiagnosisSession::factory()->create([
            'phase' => 'doctor_review',
            'doctor_reviewed_at' => null,
            'report_generated_at' => now()->subMinutes(100),
        ]);

        $this->assertTrue($session->isUrgent());
    }

    public function test_diagnosis_session_is_not_urgent_when_reviewed(): void
    {
        $session = DiagnosisSession::factory()->create([
            'phase' => 'completed',
            'doctor_reviewed_at' => now(),
        ]);

        $this->assertFalse($session->isUrgent());
    }

    public function test_diagnosis_session_scope_for_doctor(): void
    {
        $doctorId = 1;
        $session1 = DiagnosisSession::factory()->create(['doctor_id' => $doctorId]);
        $session2 = DiagnosisSession::factory()->create(['doctor_id' => 2]);

        $results = DiagnosisSession::forDoctor($doctorId)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($session1->id, $results->first()->id);
    }

    public function test_diagnosis_session_scope_by_phase(): void
    {
        $session1 = DiagnosisSession::factory()->create(['phase' => 'doctor_review']);
        $session2 = DiagnosisSession::factory()->create(['phase' => 'completed']);

        $results = DiagnosisSession::byPhase('doctor_review')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($session1->id, $results->first()->id);
    }
}

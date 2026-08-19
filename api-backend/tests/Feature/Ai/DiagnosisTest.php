<?php

namespace Tests\Feature\Ai;

use App\Models\DiagnosisSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosisTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithToken(): User
    {
        $user = User::factory()->create();
        $user->assignRole('patient');

        return $user;
    }

    public function test_unauthenticated_user_cannot_start_diagnosis(): void
    {
        $response = $this->postJson('/api/v1/auth/diagnosis/start', [
            'gender' => 'male',
            'birth_date' => '1990-01-01',
            'is_smoker' => false,
            'has_diabetes' => false,
            'has_hypertension' => false,
            'activity_level' => 'moderate',
            'assessment_for' => 'myself',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_profile(): void
    {
        $user = $this->createUserWithToken();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
            ]);
    }

    public function test_authenticated_user_can_check_auth(): void
    {
        $user = $this->createUserWithToken();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/check-auth');

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['status' => 'authenticated'],
            ]);
    }

    public function test_unauthenticated_user_cannot_check_auth(): void
    {
        $response = $this->getJson('/api/v1/auth/check-auth');

        $response->assertStatus(401);
    }

    public function test_user_can_get_diagnosis_history(): void
    {
        $user = $this->createUserWithToken();
        $token = $user->createToken('auth-token')->plainTextToken;

        DiagnosisSession::factory()->create([
            'user_id' => $user->id,
            'status' => 'COMPLETED',
            'phase' => 'completed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/diagnose/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
            ]);
    }

    public function test_user_can_view_tracking(): void
    {
        $user = $this->createUserWithToken();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/tracking');

        $response->assertStatus(200);
    }
}

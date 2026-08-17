<?php

namespace Tests\Feature\Payment;

use App\Models\DiagnosisSession;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithToken(): User
    {
        $user = User::factory()->create();
        $user->assignRole('patient');

        return $user;
    }

    public function test_unauthenticated_user_cannot_access_payment(): void
    {
        $response = $this->getJson('/api/v1/auth/payments/cost?session_hash=test');

        $response->assertStatus(401);
    }

    public function test_user_can_get_cost_for_valid_session(): void
    {
        $user = $this->createUserWithToken();
        $token = $user->createToken('auth-token')->plainTextToken;

        $session = DiagnosisSession::factory()->create([
            'user_id' => $user->id,
            'session_hash' => 'test-hash-123',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/payments/cost?session_hash=test-hash-123');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'payment_id',
                    'amount',
                    'amount_display',
                    'currency',
                ],
            ]);
    }

    public function test_user_cannot_get_cost_for_invalid_session(): void
    {
        $user = $this->createUserWithToken();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/payments/cost?session_hash=nonexistent');

        $response->assertStatus(404);
    }

    public function test_user_can_get_payment_status(): void
    {
        $user = $this->createUserWithToken();
        $token = $user->createToken('auth-token')->plainTextToken;

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'stripe_payment_intent_id' => 'pi_test_123',
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/payments/pi_test_123/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'status',
                    'amount',
                    'currency',
                ],
            ]);
    }

    public function test_user_cannot_get_status_for_nonexistent_payment(): void
    {
        $user = $this->createUserWithToken();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/payments/pi_nonexistent/status');

        $response->assertStatus(404);
    }
}

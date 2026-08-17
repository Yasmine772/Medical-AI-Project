<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_diagnosis_sessions(): void
    {
        $user = User::factory()->create();
        $user->diagnosisSessions()->createMany([
            ['session_hash' => 'hash1', 'status' => 'ACTIVE', 'phase' => 'diagnosis'],
            ['session_hash' => 'hash2', 'status' => 'COMPLETED', 'phase' => 'completed'],
        ]);

        $this->assertCount(2, $user->diagnosisSessions);
    }

    public function test_user_has_one_profile(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->profile);
    }

    public function test_user_has_many_payments(): void
    {
        $user = User::factory()->create();

        $this->assertCount(0, $user->payments);
    }

    public function test_user_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_user_hidden_attributes(): void
    {
        $user = new User();

        $this->assertContains('password', $user->getHidden());
        $this->assertContains('remember_token', $user->getHidden());
    }

    public function test_user_has_api_tokens(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token');
        $this->assertNotNull($token);
        $this->assertNotEmpty($token->plainTextToken);
    }

    public function test_user_can_assign_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('patient');

        $this->assertTrue($user->hasRole('patient'));
    }

    public function test_user_fillable_attributes(): void
    {
        $user = new User();

        $expected = [
            'full_name',
            'email',
            'password',
            'status',
            'diagnose_num',
            'email_verified_at',
            'avatar',
            'otp',
            'otp_verified_at',
            'expires_at',
            'created_at',
            'fcm_token',
            'stripe_id',
            'pm_type',
            'pm_last_four',
            'trial_ends_at',
        ];

        $this->assertEquals($expected, $user->getFillable());
    }

    public function test_user_get_age_attribute(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'birth_date' => now()->subYears(25),
        ]);

        $this->assertEquals(25, $user->age);
    }

    public function test_user_get_age_attribute_returns_null_without_profile(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->age);
    }
}

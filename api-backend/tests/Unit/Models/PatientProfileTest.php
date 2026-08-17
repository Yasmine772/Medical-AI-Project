<?php

namespace Tests\Unit\Models;

use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_profile_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $profile = PatientProfile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $profile->user);
        $this->assertEquals($user->id, $profile->user->id);
    }

    public function test_patient_profile_fillable_attributes(): void
    {
        $profile = new PatientProfile();

        $expected = [
            'birth_date',
            'gender',
            'is_smoker',
            'has_diabetes',
            'has_hypertension',
            'is_pregnant',
            'drinks_alcohol',
            'activity_level',
            'last_checkup_date',
            'user_id',
            'occupation',
            'blood_type',
        ];

        $this->assertEquals($expected, $profile->getFillable());
    }

    public function test_patient_profile_casts_boolean_fields(): void
    {
        $profile = new PatientProfile();

        $this->assertEquals('boolean', $profile->getCasts()['is_smoker']);
        $this->assertEquals('boolean', $profile->getCasts()['has_diabetes']);
        $this->assertEquals('boolean', $profile->getCasts()['has_hypertension']);
        $this->assertEquals('boolean', $profile->getCasts()['is_pregnant']);
        $this->assertEquals('boolean', $profile->getCasts()['drinks_alcohol']);
    }

    public function test_patient_profile_casts_date_field(): void
    {
        $profile = new PatientProfile();

        $this->assertEquals('date', $profile->getCasts()['birth_date']);
    }

    public function test_patient_profile_creates_with_valid_data(): void
    {
        $user = User::factory()->create();
        $profile = PatientProfile::create([
            'user_id' => $user->id,
            'gender' => 'male',
            'birth_date' => '1990-01-01',
            'is_smoker' => false,
            'has_diabetes' => false,
            'has_hypertension' => false,
            'is_pregnant' => false,
            'drinks_alcohol' => false,
            'activity_level' => 'moderate',
            'occupation' => 'Engineer',
            'blood_type' => 'A+',
        ]);

        $this->assertDatabaseHas('patient_profiles', [
            'user_id' => $user->id,
            'gender' => 'male',
        ]);
    }
}

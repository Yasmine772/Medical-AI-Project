<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'razangung@gmail.com'],
            [
                'full_name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        $patient = User::firstOrCreate(
            ['email' => 'patient@mediscan.com'],
            [
                'full_name' => 'Patient',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $patient->assignRole('patient');

        $user_1 = User::firstOrCreate(
            ['email' => 'ramaalwanni83@gmail.com'],
            [
                'full_name' => 'Doctor',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user_1->assignRole('doctor');

        Doctor::updateOrCreate(
            ['user_id' => $user_1->id],
            [
                'phone' => '0983409535',
                'specialization' => 'Cardiology',
                'years_of_experience' => 5,
                'clinic_phone' => '0111234567',
                'clinic_address' => 'Damascus, Syria',
                'license_number' => 'LIC-12345',
                'biography' => 'Experienced cardiologist with 5 years of practice.',
                'photo' => null,
                'cv_file' => null,
                'license_file' => null,
                'is_active' => true,
            ]
        );

        if (User::count() < 60) {
            User::factory(50)->create()->each(function ($user) {
                $user->assignRole('patient');
            });
        }
    }
}

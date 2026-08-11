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
        $admin = User::where('email', 'admin@mediscan.com')->first();
        if (! $admin) {
            $admin = User::create([
                'full_name' => 'Admin',
                // 'email' => 'admin@mediscan.com',
                'email' => 'razangung@gmail.com',
                'password' => Hash::make('password'),
            ]);
        }
        $admin->assignRole('admin');

        $patient = User::where('email', 'patient@mediscan.com')->first();
        if (! $patient) {
            $patient = User::create([
                'full_name' => 'Patient',
                'email' => 'patient@mediscan.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
        $patient->assignRole('patient');

        $user_1 = User::create([
            'full_name' => 'Doctor',
            'email' => 'ramaalwanni83@gmail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $user_1->assignRole('doctor');

        Doctor::create([
            'user_id' => $user_1->id,
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
        ]);

        User::factory(50)->create()->each(function ($user) {
            $user->assignRole('patient');
        });
    }
}

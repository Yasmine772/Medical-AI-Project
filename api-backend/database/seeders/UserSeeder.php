<?php

namespace Database\Seeders;

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
        if (!$admin) {
            $admin = User::create([
                'full_name' => 'Admin',
                'email' => 'admin@mediscan.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
        $admin->assignRole('admin');

        $patient = User::where('email', 'patient@mediscan.com')->first();
        if (!$patient) {
            $patient = User::create([
                'full_name' => 'Patient',
                'email' => 'patient@mediscan.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
        $patient->assignRole('patient');

        // $doctor = User::create([
        //     'full_name' => 'Doctor',
        //     'email' => 'doctor@mediscan.com',
        //     'password' => Hash::make('password'),
        //     'email_verified_at' => now(),
        // ]);
        // $doctor->assignRole('doctor');

        User::factory(50)->create()->each(function ($user) {
        $user->assignRole('patient');
    });
    }
}

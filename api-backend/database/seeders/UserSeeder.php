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

        $user_1 = User::create([
                'full_name' => 'Doctor',
                'email' => 'razankhaderr@gmail.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                ]);
        $user_1->assignRole('doctor');

        Doctor::updateOrCreate(
            ['user_id' => $user_1->id],
            [
                'phone' => '0983409535',
                'specialization' => 'Cardiologist',
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

        $specializations = [
            'Cardiologist', 'Neurologist', 'Dermatologist', 'Gastroenterologist',
            'Pulmonologist', 'Endocrinologist', 'General Physician',
            'Pediatrician', 'Orthopedic Surgeon',
        ];

        $names = [
            ['John', 'Doe'], ['Sara', 'White'], ['Michael', 'Brown'],
            ['Emily', 'Davis'], ['David', 'Miller'], ['Jessica', 'Wilson'],
            ['James', 'Moore'], ['Laura', 'Taylor'], ['Robert', 'Anderson'],
            ['Maria', 'Thomas'], ['William', 'Jackson'], ['Linda', 'Martin'],
            ['Richard', 'Lee'], ['Sophia', 'Perez'], ['Joseph', 'Thompson'],
            ['Olivia', 'Clark'], ['Thomas', 'Rodriguez'], ['Ava', 'Lewis'],
            ['Charles', 'Walker'], ['Emma', 'Hall'], ['Daniel', 'Young'],
            ['Grace', 'Allen'], ['Matthew', 'King'], ['Chloe', 'Wright'],
            ['Andrew', 'Scott'], ['Mia', 'Adams'], ['Joshua', 'Baker'],
            ['Isabella', 'Nelson'], ['Ryan', 'Carter'], ['Amelia', 'Mitchell'],
        ];

        $nameIndex = 0;
        foreach ($specializations as $spec) {
            for ($i = 0; $i < 3; $i++) {
                [$first, $last] = $names[$nameIndex++];
                $email = strtolower($first . '.' . $last) . '@mediscan.com';

                $doctorUser = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'full_name' => $first . ' ' . $last,
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                    ]
                );
                $doctorUser->assignRole('doctor');

                Doctor::updateOrCreate(
                    ['user_id' => $doctorUser->id],
                    [
                        'phone' => '09' . str_pad((string) rand(0, 99999999), 8, '0'),
                        'specialization' => $spec,
                        'years_of_experience' => rand(1, 25),
                        'clinic_phone' => '011' . str_pad((string) rand(0, 999999), 6, '0'),
                        'clinic_address' => 'Damascus, Syria',
                        'license_number' => 'LIC-' . strtoupper(substr(md5($email), 0, 8)),
                        'biography' => $first . ' ' . $last . ' is a licensed ' . $spec . ' with professional clinical experience.',
                        'photo' => null,
                        'cv_file' => null,
                        'license_file' => null,
                        'is_active' => true,
                    ]
                );
            }
        }

        if (User::count() < 60) {
            User::factory(50)->create()->each(function ($user) {
                $user->assignRole('patient');
            });
        }
    }
}

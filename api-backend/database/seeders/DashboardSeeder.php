<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;
use App\Models\DiagnosisSession;
use App\Models\Disease;
use App\Models\Symptom;
use App\Models\Advice;


class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->count(10)->create(['status' => true]);
        User::factory()->count(5)->create(['status' => false]);

        Doctor::factory()->count(5)->create(['is_active' => true]);
        Doctor::factory()->count(2)->create(['is_active' => false]);

        $user = User::first();
        DiagnosisSession::create(['user_id' => $user->id, 'status' => 'ACTIVE', 'created_at' => now()]);
        DiagnosisSession::create(['user_id' => $user->id, 'status' => 'COMPLETED', 'created_at' => now()->subDays(2)]);
        DiagnosisSession::create(['user_id' => $user->id, 'status' => 'COMPLETED', 'created_at' => now()->subDays(3)]);

        Disease::create(['name' => 'Flu', 'created_at' => now()]);
        Symptom::create(['name' => 'Fever', 'created_at' => now()->subHours(5)]);
        Advice::create(['title' => 'Hydration', 'content' => 'Drink water', 'category' => 'General', 'created_at' => now()->subHours(10)]);
        
        $specialties = ['Cardiology', 'Pediatrics', 'Neurology', 'Dermatology', 'Oncology'];
        foreach ($specialties as $name) {
         Doctor::factory()->count(2)->create([
            'is_active' => true,
            'specialization' => $name  
        ]);
        }

      

        $diseases = ['Flu', 'Diabetes', 'Hypertension', 'Asthma'];
        foreach ($diseases as $name) {
            Disease::create(['name' => $name]);
        }

        $doctors = Doctor::all();
        $diseases = Disease::all();

        for ($i = 0; $i < 50; $i++) {
         DiagnosisSession::create([
            'user_id' => User::inRandomOrder()->first()->id ?? 1,
            'doctor_id' => $doctors->random()->id,
            'disease_id' => $diseases->random()->id,
            'status' => 'COMPLETED',
            'started_at' => now(),
        ]);

        
    }
}
}
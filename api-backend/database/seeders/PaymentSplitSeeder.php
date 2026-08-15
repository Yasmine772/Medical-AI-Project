<?php

namespace Database\Seeders;

use App\Models\DiagnosisSession;
use App\Models\Doctor;
use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSplitSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'ramaalwanni83@gmail.com')->first();
        $doctor = $user ? Doctor::where('user_id', $user->id)->first() : null;

        if (!$user || !$doctor) {
            $this->command->warn('Test doctor not found. Run UserSeeder first.');
            return;
        }

        $splits = [
            ['amount' => 5000, 'created_at' => now()],
            ['amount' => 6000, 'created_at' => now()->subDays(5)],
            ['amount' => 8000, 'created_at' => now()->subDays(40)],
        ];

        foreach ($splits as $index => $split) {
            $createdAt = $split['created_at'];

            $session = DiagnosisSession::create([
                'user_id' => $user->id,
                'doctor_id' => $doctor->id,
                'status' => 'COMPLETED',
                'phase' => 'completed',
                'started_at' => $createdAt,
                'completed_at' => $createdAt,
            ]);
            $session->created_at = $createdAt;
            $session->save();

            $payment = Payment::create([
                'user_id' => $user->id,
                'diagnosis_session_id' => $session->id,
                'stripe_payment_intent_id' => 'pi_seed_' . ($index + 1),
                'amount' => $split['amount'],
                'currency' => 'usd',
                'status' => 'succeeded',
                'paid_at' => $createdAt,
            ]);
            $payment->created_at = $createdAt;
            $payment->save();

            $platformAmount = intdiv($split['amount'], 2);

            $splitRecord = new PaymentSplit([
                'payment_id' => $payment->id,
                'doctor_id' => $doctor->id,
                'platform_amount' => $platformAmount,
                'doctor_amount' => $split['amount'] - $platformAmount,
            ]);
            $splitRecord->created_at = $createdAt;
            $splitRecord->updated_at = $createdAt;
            $splitRecord->save();
        }

        $this->command->info('Test payment splits seeded successfully.');
    }
}

<?php

namespace App\Services\Api;

use App\Mail\DoctorDiagnosisMail;
use App\Models\DiagnosisSession;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\NewDiagnosisAssignedNotification;
use App\Services\Api\DoctorAssignmentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private const DIAGNOSIS_AMOUNT = 500;

    public function createPaymentIntent(User $user, string $sessionHash): ?array
    {
        $session = DiagnosisSession::where('session_hash', $sessionHash)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return null;
        }

        try {
            if (!$user->hasStripeId()) {
                $user->createOrGetStripeCustomer();
            }

            $payment = $user->pay(self::DIAGNOSIS_AMOUNT, [
                'metadata' => [
                    'session_hash' => $sessionHash,
                    'user_id' => $user->id,
                ],
            ]);

            $record = Payment::create([
                'user_id' => $user->id,
                'diagnosis_session_id' => $session->id,
                'stripe_payment_intent_id' => $payment->id,
                'amount' => self::DIAGNOSIS_AMOUNT,
                'currency' => 'usd',
                'status' => 'pending',
                'paid_at'=> now(),
            ]);

            return [
                'client_secret' => $payment->client_secret,
                'payment_intent_id' => $payment->id,
                'payment_id' => $record->id,
            ];

        } catch (\Exception $e) {
            Log::error('Payment intent creation failed', [
                'user_id' => $user->id,
                'session_hash' => $sessionHash,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function handlePaymentSucceeded(string $paymentIntentId): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (!$payment) {
            Log::warning('Payment record not found for intent', ['payment_intent_id' => $paymentIntentId]);
            return;
        }

        $payment->update([
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);

        User::where('id', $payment->user_id)
            ->increment('diagnose_num');

        $this->assignDoctorAfterPayment($payment);
    }

    private function assignDoctorAfterPayment(Payment $payment): void
    {
        $session = DiagnosisSession::find($payment->diagnosis_session_id);

        if (!$session || $session->doctor_id) {
            return;
        }

        $specialist = $this->getSpecialist($session);

        if (!$specialist) {
            Log::warning('Doctor assignment: no specialist found', [
                'session_hash' => $session->session_hash,
            ]);
            return;
        }

        try {
            $doctorId = app(DoctorAssignmentService::class)->assign($session->id, $specialist);

            Log::info('Doctor assigned after payment', [
                'session_hash' => $session->session_hash,
                'specialist' => $specialist,
                'doctor_id' => $doctorId,
            ]);

            if ($doctorId) {
                $this->notifyAssignedDoctor($session->refresh());
            }

        } catch (\Exception $e) {
            Log::error('Doctor assignment after payment failed', [
                'session_hash' => $session->session_hash,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyAssignedDoctor(DiagnosisSession $session): void
    {
        try {
            app(DiagnosisDataService::class)->store($session);

            $doctor = $session->load('doctor.user')->doctor;

            if ($doctor && $doctor->user) {
                $doctor->user->notify(new NewDiagnosisAssignedNotification($session->refresh()));

                Log::info('Doctor notified about new diagnosis', [
                    'session_hash' => $session->session_hash,
                    'doctor_id' => $doctor->id,
                    'doctor_email' => $doctor->user->email,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Doctor notification failed', [
                'session_hash' => $session->session_hash,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getSpecialist(DiagnosisSession $session): ?string
    {
        try {
            $fastApiUrl = config('services.fastapi.url');
            $preview = Http::timeout(config('services.fastapi.timeout', 10))
                ->get($fastApiUrl."/reports/{$session->session_hash}/preview", ['language_code' => 'en']);

            if ($preview->successful()) {
                $diagnoses = $preview->json()['diagnoses'] ?? [];
                $specialist = $diagnoses[0]['specialist'] ?? null;
                if ($specialist) {
                    return $specialist;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Doctor assignment: FastAPI preview unavailable, falling back to disease specialist', [
                'session_hash' => $session->session_hash,
                'error' => $e->getMessage(),
            ]);
        }

        return $session->disease?->specialist;
    }

    public function getPaymentStatus(string $paymentIntentId): ?array
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
            ->with(['user:id,full_name,email', 'diagnosisSession:id,status'])
            ->first();
        
        if (!$payment) {
            return null;
        }

        return [
            'id' => $payment->id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'paid_at' => $payment->paid_at,
            'diagnosis_session_id' => $payment->diagnosis_session_id,
        ];
    }
}

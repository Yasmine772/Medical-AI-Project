<?php

namespace App\Services\Api;

use App\Models\DiagnosisSession;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private const DIAGNOSIS_AMOUNT = 500;

    public function createPaymentIntent(User $user, int $diagnosisSessionId): ?array
    {
        $session = DiagnosisSession::where('id', $diagnosisSessionId)
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
                    'diagnosis_session_id' => $diagnosisSessionId,
                    'user_id' => $user->id,
                ],
            ]);

            $record = Payment::create([
                'user_id' => $user->id,
                'diagnosis_session_id' => $diagnosisSessionId,
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
                'session_id' => $diagnosisSessionId,
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

        DiagnosisSession::where('id', $payment->diagnosis_session_id)
            ->update(['status' => 'COMPLETED']);

        User::where('id', $payment->user_id)
            ->increment('diagnose_num');
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

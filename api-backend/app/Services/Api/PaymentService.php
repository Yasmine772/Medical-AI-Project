<?php

namespace App\Services\Api;

use App\Models\DiagnosisSession;
use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\User;
use App\Notifications\NewDiagnosisAssignedNotification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Jobs\ReassignExpiredCases; 


class PaymentService
{
    private const DIAGNOSIS_AMOUNT = 500;

    private int $maxRetries;

    private int $retryDelay;

    public function __construct()
    {
        $this->maxRetries = config('services.fastapi.max_retries', 3);
        $this->retryDelay = config('services.fastapi.retry_delay', 1);
    }

    // ── Retry helper ──────────────────────────────────────────
    private function withRetry(callable $fn): mixed
    {
        $lastException = null;

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $fn();
            } catch (ConnectionException $e) {
                $lastException = $e;
                if ($attempt < $this->maxRetries) {
                    $delay = $this->retryDelay * pow(2, $attempt);
                    Log::warning("Payment retry {$attempt}/{$this->maxRetries}", [
                        'error' => $e->getMessage(),
                        'retry_in' => "{$delay}s",
                    ]);
                    sleep($delay);
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }

        throw $lastException;
    }

    public function getCost(User $user, string $sessionHash): ?array
    {
        $session = DiagnosisSession::where('session_hash', $sessionHash)
            ->where('user_id', $user->id)
            ->first();

        if (! $session) {
            return null;
        }

        $payment = Payment::where('diagnosis_session_id', $session->id)
            ->where('status', 'pending')
            ->first();

        if (! $payment) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'diagnosis_session_id' => $session->id,
                'stripe_payment_intent_id' => null,
                'amount' => self::DIAGNOSIS_AMOUNT,
                'currency' => 'usd',
                'status' => 'pending',
            ]);
        }

        return [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'amount_display' => '$'.number_format($payment->amount / 100, 2),
            'currency' => $payment->currency,
        ];
    }

    public function createPaymentIntent(User $user, string $sessionHash, ?string $idempotencyKey = null): ?array
    {
        $session = DiagnosisSession::where('session_hash', $sessionHash)
            ->where('user_id', $user->id)
            ->first();

        if (! $session) {
            return null;
        }

        $cacheKey = "payment:{$user->id}:{$sessionHash}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::info('Payment cache hit', ['user_id' => $user->id, 'session' => $sessionHash]);

            return $cached;
        }

        $existingPayment = Payment::where('diagnosis_session_id', $session->id)
            ->where('status', 'pending')
            ->whereNotNull('stripe_payment_intent_id')
            ->first();

        if ($existingPayment) {
            Log::info('Payment intent already exists', [
                'payment_id' => $existingPayment->id,
                'session' => $sessionHash,
            ]);

            $result = [
                'client_secret' => $existingPayment->stripe_payment_intent_id,
                'payment_intent_id' => $existingPayment->stripe_payment_intent_id,
                'payment_id' => $existingPayment->id,
            ];

            Cache::put($cacheKey, $result, 300);

            return $result;
        }

        try {
            $result = $this->withRetry(function () use ($user, $sessionHash, $idempotencyKey) {
                if (! $user->hasStripeId()) {
                    $user->createOrGetStripeCustomer();
                }

                $payment = $user->stripe()->paymentIntents->create([
                    'amount' => self::DIAGNOSIS_AMOUNT,
                    'currency' => 'usd',
                    'customer' => $user->stripe_id,
                    'automatic_payment_methods' => ['enabled' => true],
                    'metadata' => [
                        'session_hash' => $sessionHash,
                        'user_id' => $user->id,
                    ],
                ], [
                    'idempotency_key' => $idempotencyKey,
                ]);

                return $payment;
            });

            $session = DiagnosisSession::where('session_hash', $sessionHash)->first();

            $record = Payment::updateOrCreate(
                [
                    'diagnosis_session_id' => $session->id,
                    'status' => 'pending',
                ],
                [
                    'user_id' => $user->id,
                    'stripe_payment_intent_id' => $result->id,
                    'amount' => self::DIAGNOSIS_AMOUNT,
                    'currency' => 'usd',
                    'paid_at' => now(),
                ]
            );

            $response = [
                'client_secret' => $result->client_secret,
                'payment_intent_id' => $result->id,
                'payment_id' => $record->id,
            ];

            Cache::put($cacheKey, $response, 300);

            return $response;

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

        if (! $payment) {
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
        $this->createSplit($payment);
    }

    private function createSplit(Payment $payment): void
    {
        try {
            $session = DiagnosisSession::find($payment->diagnosis_session_id);

            PaymentSplit::updateOrCreate(
                ['payment_id' => $payment->id],
                [
                    'doctor_id' => $session?->doctor_id,
                    'platform_amount' => intdiv($payment->amount, 2),
                    'doctor_amount' => $payment->amount - intdiv($payment->amount, 2),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Payment split creation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function assignDoctorAfterPayment(Payment $payment): void
    {
        $session = DiagnosisSession::find($payment->diagnosis_session_id);

        if (! $session || $session->doctor_id) {
            return;
        }

        $specialist = $this->getSpecialist($session);

        if (! $specialist) {
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
                ReassignExpiredCases::dispatch($session->id)->delay(now()->addMinutes(45));
            }

        } catch (\Exception $e) {
            Log::error('Doctor assignment after payment failed', [
                'session_hash' => $session->session_hash,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyAssignedDoctor(DiagnosisSession $session): void
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

        if (! $payment) {
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

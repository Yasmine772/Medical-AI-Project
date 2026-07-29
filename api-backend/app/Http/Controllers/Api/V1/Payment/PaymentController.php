<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Payment\CreatePaymentIntentRequest;
use App\Services\Api\PaymentService;
use App\Traits\ApiResponseTrait;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function createIntent(CreatePaymentIntentRequest $request)
    {
        $user = $request->user();
        $sessionHash = $request->input('session_hash');
        
        $result = $this->paymentService->createPaymentIntent($user, $sessionHash);
      
        if ($result === null) {
            return $this->errorResponse('Failed to create payment intent. Please try again.', null, 500);
        }

        return $this->successResponse($result, 'Payment intent created successfully', 201);
    }

    public function handleWebhook()
    {
        $payload = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $this->paymentService->handlePaymentSucceeded($event->data->object->id);
        }

        return response('Webhook handled', 200);
    }

    public function status(string $paymentIntentId)
    {
        $result = $this->paymentService->getPaymentStatus($paymentIntentId);

        if ($result === null) {
            return $this->errorResponse('Payment not found', null, 404);
        }

        return $this->successResponse($result, 'Payment status retrieved successfully', 200);
    }
}

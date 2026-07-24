<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\LoginRequest;
use App\Http\Requests\User\OTP\VerifyOTPRequest;
use App\Services\Web\AuthService;
use App\Traits\ApiResponseTrait;

class AuthController extends Controller
{
    use ApiResponseTrait;
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function adminLogin(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        return match ($result) {
            'unauthorized' => $this->errorResponse('Invalid email or password', null, 401),
            'AccessDenied' => $this->errorResponse('Access denied. This system is for administrators only.', null, 403),
            'EmailNotVerified' => $this->errorResponse('Please verify your email first. Check your email for OTP code.', null, 403),

            default => $this->successResponse([
                    'user'         => $result['Admin'],
                    'access_token' => $result['access_token'],
                    'token_type'   => 'Bearer',
                    'expires_at'   => '1 day',
            ], 'Admin login successfully', 200)
        };
    }

    public function doctorLogin(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        return match ($result) {
            'unauthorized' => $this->errorResponse('Invalid email or password', null, 401),
            'AccessDenied' => $this->errorResponse('Access denied. This system is for administrators only.', null, 403),
            'EmailNotVerified' => $this->errorResponse('Please verify your email first. Check your email for OTP code.', null, 403),

            default => $this->successResponse([
                'user'         => $result['user'],
                'access_token' => $result['access_token'],
                'token_type'   => 'Bearer',
                'expires_at'   => '1 day',
            ], 'Doctor login successfully', 200)
        };
    }

    public function adminVerifyOtp(VerifyOTPRequest $request)
    {
        $result = $this->authService->verifyOtp($request->validated(), 'admin');

        return match ($result) {
            'UserNotFound' => $this->errorResponse('User not found!', null, 404),
            'NotValidOTP' => $this->errorResponse('Not valid OTP!', null, 422),
            'OTPHasExpired' => $this->errorResponse('OTP has expired!', null, 400),
            'OTP used' => $this->errorResponse('You have been used it!', null, 422),
            default => $this->successResponse($result, 'OTP verified successfully', 200)
        };
    }


    public function doctorVerifyOtp(VerifyOTPRequest $request)
    {
        $result = $this->authService->verifyOtp($request->validated(), 'doctor');

        return match ($result) {
            'UserNotFound' => $this->errorResponse('User not found!', null, 404),
            'NotValidOTP' => $this->errorResponse('Not valid OTP!', null, 422),
            'OTPHasExpired' => $this->errorResponse('OTP has expired!', null, 400),
            'OTP used' => $this->errorResponse('You have been used it!', null, 422),
            default => $this->successResponse($result, 'OTP verified successfully', 200)
        };
    }


}

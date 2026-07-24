<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\LoginRequest;
use App\Http\Requests\User\OTP\VerifyOTPRequest;
use App\Models\User;
use App\Services\Api\OTPService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponseTrait;
    protected OTPService $otpService;

    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }
    ///////////////////////////////////////////////////////////
    public function adminLogin(LoginRequest $request)
    {
        try {
            $credentials = $request->validated();

            if (!Auth::attempt($credentials)) {
                return $this->errorResponse('Error', 'Invalid email or password', 401);
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user->hasRole('admin')) {
                return $this->errorResponse('Error', 'Access denied. This system is for administrators only.', 403);
            }

            if (is_null($user->email_verified_at)) {
                $this->otpService->sendOTP($user);
                return $this->errorResponse('Error', 'Please verify your email first. Check your email for OTP code', 403);
            }

            $accessTokenExpiresAt = Carbon::now()->addDays(1);
            $accessToken = $user->createToken('access_token', ['admin'], $accessTokenExpiresAt)->plainTextToken;

            $data = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'accessToken' => $accessToken,
                    'token_type' => 'Bearer'
                ];

            return $this->successResponse($data , 'Admin login successfuly' , 200);

        } catch (\Throwable $e) {
            return $this->errorResponse('Error', $e->getMessage(), 500);
        }
    }
    //*************************************************** */
    public function verifyOtp(VerifyOTPRequest $request)
    {
        $user = User::where('email' , $request['email'])->first();

        if (!$user) {
            $this->errorResponse('User not found!', null, 422);
        }

        $isValid = $this->otpService->verifyOTP($request->validated());

        if ($isValid === 'CorrectOTP') {
            Auth::login($user);

            $token = $user->createToken('auth_token', ['admin'])->plainTextToken;

            $data = ['email' => $request->email, 'token' => $token];

            return $this->successResponse($data, 'OTP verified successfully', 200);
        }

        return match ($isValid) {
            'UserNotFound' => $this->errorResponse('User not found!', null, 422),
            'NotValidOTP' => $this->errorResponse('Not valid OTP!', null, 422),
            'OTPHasExpired' => $this->errorResponse('OTP has expired!', null, 422),
            'OTP used' => $this->errorResponse('You have been used it!', null, 422),
            default => $this->successResponse(null, 'OTP verified successfully', 200)
        };
    }
}

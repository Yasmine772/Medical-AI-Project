<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\LoginRequest;
use App\Http\Requests\User\Auth\RegisterRequest;
use App\Http\Requests\User\OTP\ResendOTPRequest;
use App\Http\Requests\User\OTP\VerifyOTPRequest;
use App\Http\Requests\User\Password\ResetPasswordRequest;
use App\Http\Requests\User\Profile\UpdateProfileRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\User;
use App\Services\Api\AuthService;
use App\Services\Api\OTPService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthController extends Controller
{
    use ApiResponseTrait;

    protected AuthService $authService;

    protected OTPService $otpService;

    public function __construct(AuthService $authService, OTPService $otpService)
    {
        $this->authService = $authService;
        $this->otpService = $otpService;
    }

    // -------------------------------------------------------------------------------------------
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->register($request->validated());

            $this->otpService->sendOTP($user);

            return $this->successResponse(new UserResource($user), 'User registered successfully. Please check your email, we sent OTP', 201);

        } catch (Throwable $e) {
            return $this->errorResponse('Failed to register user', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------------------------
    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        // if ($result != 'unauthorized') {
        //     $user = $result['user'];
        //     if ($user->hasRole('admin')) {
        //         $this->otpService->sendOTP($user);
        //     }
        // }
        return match ($result) {
            'unauthorized' => $this->errorResponse('Email or password not correct!', null, 422),

            default => $this->successResponse([
                'user' => new UserResource($result['user']),
                'access_token' => $result['access_token'],
                'access_token_expires_at' => $result['access_token_expires_at'],
                'token_type' => $result['token_type'],
            ], 'User login successfully', 200)
        };
    }

    // -------------------------------------------------------------------------------------------
    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();

        return $this->successResponse(null, 'User logout successfully', 200);
    }

    // -------------------------------------------------------------------------------------------
    public function verifyOtp(VerifyOTPRequest $request)
    {
        $result = $this->otpService->verifyOtp($request->validated());

        if ($result === 'CorrectOTP') {
            Cache::put('password_reset_'.$request->email, true, now()->addMinutes(5));

            return $this->successResponse(['email' => $request->email], 'OTP verified successfully', 200);
        }

        return match ($result) {
            'UserNotFound' => $this->errorResponse('User not found!', null, 422),
            'NotValidOTP' => $this->errorResponse('Not valid OTP!', null, 422),
            'OTPHasExpired' => $this->errorResponse('OTP has expired!', null, 422),
            'OTP used' => $this->errorResponse('You have been used it!', null, 422),
            default => $this->successResponse(null, 'OTP verified successfully', 200)
        };
    }

    // -------------------------------------------------------------------------------------------
    public function resendOtp(ResendOTPRequest $request)
    {
        $result = $this->otpService->resendOtp($request->validated());

        return match ($result) {
            'UserNotFound' => $this->errorResponse('User not found!', null, 422),
            default => $this->successResponse(null, 'OTP resent successfully. Please check your email.', 200)
        };
    }

    // -------------------------------------------------------------------------------------------
    /**
     * Send an OTP to the specified user email.
     *
     * @return JsonResponse
     */
    public function forgetPassword(Request $request)
    {
        try {

            $request->validate(['email' => 'required|email']);

            $user = User::where('email', $request->email)->first();

            if (! $user) {
                return $this->errorResponse('Email not found!', null, 422);
            }

            $this->otpService->sendOTP($user);

            return $this->successResponse(null, 'OTP has been sent to your email', 200);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to send OTP', $e->getMessage(), 500);
        }

    }

    /**
     * Reset the user's password using the provided  OTP.
     *
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request)
    {

        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();
        if (! $user->otp_verified_at) {
            return $this->errorResponse('Please verify your OTP first', null, 403);
        }
        $user->update([
            'password' => Hash::make($data['password']),
            'otp_verified_at' => null,
        ]);

        return $this->successResponse(null, 'Password reset successfully', 200);
    }

    /**
     * View the profile details of the authenticated user.
     */
    public function viewProfile()
    {
        $user = auth()->authenticate();

        $user->load('profile');

        $profileData = $this->authService->getUserProfile($user);

        return $this->successResponse(new UserResource($profileData), 'Success', 200);
    }

    /**
     * Update the profile details of the authenticated user.
     *
     * @return JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $validatedData = $request->validated();

        $avatarFile = $request->file('avatar') ?? $request->file('Avatar');

        $user = $this->authService->updateProfile(auth()->authenticate(), $validatedData, $avatarFile);

        return $this->successResponse(new UserResource($user), 'Profile updated successfully', 200);
    }

    /**
     * Check if the current user is authenticated or not
     *
     * @return JsonResponse
     */
    public function checkAuthentication()
    {
        try {
            $user = auth('sanctum')->user();

            if (! $user) {
                return $this->errorResponse('Unauthenticated', null, 401);
            }

            return $this->successResponse(['status' => 'authenticated'], 'Success', 200);

        } catch (Throwable $e) {

            return $this->errorResponse('An error occurred during authentication check', null, 500);
        }
    }
}

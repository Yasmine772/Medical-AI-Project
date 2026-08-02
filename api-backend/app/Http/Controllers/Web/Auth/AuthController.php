<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\LoginRequest;
use App\Http\Requests\User\OTP\VerifyOTPRequest;
use App\Http\Requests\Web\Doctor\DoctorProfileRequest;
use App\Models\Doctor;
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
//************************************************************************************* */
    public function adminLogin(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated(), 'admin');

        return match ($result) {
            'unauthorized' => $this->errorResponse('Invalid email or password', null, 401),
            'adminOnly' => $this->errorResponse('Access denied. This system is for administrators only.', null, 403),
            'doctorOnly' => $this->errorResponse('Access denied. This system is for doctors only.', null, 403),
            'EmailNotVerified' => $this->errorResponse('Please verify your email first. Check your email for OTP code.', null, 403),

            default => $this->successResponse([
                    'user'         => $result['user'],
                    'access_token' => $result['access_token'],
                    'token_type'   => 'Bearer',
                    'expires_at'   => '1 day',
                    'role'         => $result['role']
            ], 'Admin login successfully', 200)
        };
    }
//*********************************************************************** */
    public function doctorLogin(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated(), 'doctor');

        return match ($result) {
            'unauthorized' => $this->errorResponse('Invalid email or password', null, 401),
            'adminOnly' => $this->errorResponse('Access denied. This system is for administrators only.', null, 403),
            'doctorOnly' => $this->errorResponse('Access denied. This system is for doctors only.', null, 403),
            'EmailNotVerified' => $this->errorResponse('Please verify your email first. Check your email for OTP code.', null, 403),

            default => $this->successResponse([
                'user'         => $result['user'],
                'access_token' => $result['access_token'],
                'token_type'   => 'Bearer',
                'expires_at'   => '1 day',
                'role'         => $result['role']
            ], 'Doctor login successfully', 200)
        };
    }
//*************************************************************************************** */
    public function adminVerifyOtp(VerifyOTPRequest $request)
    {
        $result = $this->authService->verifyOtp($request->validated(), 'admin', 'email');

        return match ($result) {
            'UserNotFound' => $this->errorResponse('User not found!', null, 404),
            'NotValidOTP' => $this->errorResponse('Not valid OTP!', null, 422),
            'OTPHasExpired' => $this->errorResponse('OTP has expired!', null, 400),
            'OTP used' => $this->errorResponse('You have been used it!', null, 422),
            'emailVerified' => $this->errorResponse('Your email has been verified!', null, 422),
            default => $this->successResponse($result, 'OTP verified successfully', 200)
        };
    }
//************************************************************************************* */
    public function doctorVerifyOtpForEmail(VerifyOTPRequest $request)
    {
        $result = $this->authService->verifyOtp($request->validated(), 'doctor' , 'email');

        return match ($result) {
            'UserNotFound' => $this->errorResponse('User not found!', null, 404),
            'NotValidOTP' => $this->errorResponse('Not valid OTP!', null, 422),
            'OTPHasExpired' => $this->errorResponse('OTP has expired!', null, 400),
            'OTP used' => $this->errorResponse('You have been used it!', null, 422),
            'emailVerified' => $this->errorResponse('Your email has been verified!', null, 422),
            default => $this->successResponse($result, 'OTP verified successfully', 200)
        };
    }
    //*************************************************************************************** */
    public function doctorVerifyOtpForPassword(VerifyOTPRequest $request)
    {
        $result = $this->authService->verifyOtp($request->validated(), 'doctor', 'password');

        return match ($result) {
            'UserNotFound' => $this->errorResponse('User not found!', null, 404),
            'NotValidOTP' => $this->errorResponse('Not valid OTP!', null, 422),
            'OTPHasExpired' => $this->errorResponse('OTP has expired!', null, 400),
            'OTP used' => $this->errorResponse('You have been used it!', null, 422),
            'emailVerified' => $this->errorResponse('Your email has been verified!', null, 422),
            default => $this->successResponse($result, 'OTP verified successfully', 200)
        };
    }
    //*************************************************************************************** */
    public function viewProfile()
    {
        $doctor_profile = $this->authService->viewProfile();

        if($doctor_profile == null){
            return $this->errorResponse('Doctor not found!', null, 404);
        }
        return $this->successResponse($doctor_profile , 'Doctor profile retrieved successfully', 200);
    }
    //***************************************************************************************** */
    public function updateProfile(DoctorProfileRequest $request)
    {
        $doctor_profile = $this->authService->updateProfile($request->validated());

        if ($doctor_profile == null) {
            return $this->errorResponse('Doctor not found!', null, 404);
        }
        return $this->successResponse($doctor_profile, 'Doctor profile updated successfully', 200);
    }







}

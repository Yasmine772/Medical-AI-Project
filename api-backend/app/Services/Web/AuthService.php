<?php

namespace App\Services\Web;

use App\Models\Doctor;
use App\Models\User;
use App\Services\Api\OTPService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService 
{
    protected OTPService $otpService;
    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }
    /////////////////////////////////////////////
    public function login(array $data)
    {
        $credentials = ['email' => $data['email'],'password' => $data['password']];

        if (!Auth::attempt($credentials)) {
            return 'unauthorized';
        }

        $user = User::where('email', $data['email'])->first();

        if (!$user->hasRole('admin')) {
            return 'AccessDenied';
        }

        if (is_null($user->email_verified_at)) {
            $this->otpService->sendOTP($user);
            return 'EmailNotVerified';
        }

        $user->tokens()->delete();
        $accessTokenExpiresAt = Carbon::now()->addDays(1);
        $accessToken = $user->createToken('access_token', ['*'], $accessTokenExpiresAt)->plainTextToken;

        return [
            'Admin' => ['email' => $user->email],
            'access_token' =>  $accessToken,
        ];
    }

    //****************************** */
    public function doctorLogin(array $data)
    {
        $doctor = Doctor::where('email', $data['email'])->first();

        if (!$doctor) {
            return 'unauthorized';
        }
        if (!Hash::check($data['password'], $doctor->password)) {
            return 'unauthorized';
        }

        if (!$doctor->hasRole('doctor')) {
            return 'AccessDenied';
        }

        if (is_null($doctor->email_verified_at)) {
            $this->otpService->sendOTP($doctor);
            return 'EmailNotVerified';
        }

        $accessTokenExpiresAt = Carbon::now()->addDays(1);
        $accessToken = $doctor->createToken('access_token', ['doctor'], $accessTokenExpiresAt)->plainTextToken;

        return [
            'Doctor' => ['email' => $doctor->email],
            'access_token' =>  $accessToken,
        ];
    }
    //**************************************************** */
    public function verifyOtp(array $request ,string $role)
    {
        $user = User::where('email', $request['email'])->first();

        $result = $this->otpService->verifyOTP($request);

        if ($result === 'CorrectOTP') 
        {
            $accessTokenExpiresAt = Carbon::now()->addDays(1);
            $token = $user->createToken('access_token', [$role], $accessTokenExpiresAt)->plainTextToken;

            $data = ['email' => $request['email'], 'token' => $token];
            return  $data;
        }
        return $result;
    }
}
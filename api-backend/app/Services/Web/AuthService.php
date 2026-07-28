<?php

namespace App\Services\Web;

use App\Models\User;
use App\Services\Api\OTPService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AuthService 
{
    protected OTPService $otpService;
    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }
    /////////////////////////////////////////////
    public function login(array $data , string $role)
    {
        $credentials = ['email' => $data['email'],'password' => $data['password']];

        if (!Auth::attempt($credentials)) {
            return 'unauthorized';
        }

        $user = User::where('email', $data['email'])->first();

        if (!$user->hasRole($role)) {
            if($role == 'admin'){
                return 'adminOnly';
            }
            return 'doctorOnly';
        }

        if (is_null($user->email_verified_at)) {
            $this->otpService->sendOTP($user);
            return 'EmailNotVerified';
        }

        $user->tokens()->delete();
        $accessTokenExpiresAt = Carbon::now()->addDays(1);
        $accessToken = $user->createToken('access_token', [$role], $accessTokenExpiresAt)->plainTextToken;

        return [
            'user' => ['email' => $user->email],
            'access_token' =>  $accessToken,
        ];
    }
    //**************************************************** */
    public function verifyOtp(array $request ,string $role)
    {
        $user = User::where('email', $request['email'])->first();

        if($user->email_verified_at === null )
        {
            $result = $this->otpService->verifyOTP($request);

            if ($result === 'CorrectOTP') {
                $accessTokenExpiresAt = Carbon::now()->addDays(1);
                $token = $user->createToken('access_token', [$role], $accessTokenExpiresAt)->plainTextToken;

                $data = ['email' => $request['email'], 'token' => $token];
                return  $data;
            }
            return $result;
        }
        return 'emailVerified';
    }
}
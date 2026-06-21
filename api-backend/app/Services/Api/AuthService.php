<?php

namespace app\Services\Api;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function register(array $data)
    {
        $user = User::create([
            'full_name' => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
        ]);

        $user->sendEmailVerificationNotification();

        return $user;
    }
    /////////////////////////////////////////////////////////////////////////////////////
    public function login(array $data) 
    {
        $credentials = ['email' => $data['email'],
                        'password' => $data['password']];

        if(!Auth::attempt($credentials)){
            return 'unauthorized';
        }

        $user = User::where('email' , $data['email'])->first();

        if (!$user->hasVerifiedEmail()) {
            return 'unVerifiedEmail';
        }

        $user->tokens()->delete(); 

        $accessTokenExpiresAt = Carbon::now()->addMinutes(60);
        $refreshTokenExpiresAt = Carbon::now()->addDays(7);

        $accessToken = $user->createToken('access_token', ['*'], $accessTokenExpiresAt)->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['refresh'], $refreshTokenExpiresAt)->plainTextToken;

        $result = [ 'user' => $user,
                    'access_token' => $accessToken,
                    'access_token_expires_at' => $accessTokenExpiresAt,
                    'refresh_token' => $refreshToken,
                    'refresh_token_expires_at' => $refreshTokenExpiresAt,
                    'token_type' => 'Bearer',
                ];

        return $result;
    }
    ///////////////////////////////////////////////////////////////////////////////////////
    public function refreshToken(array $request)
    {
        $currentRefreshToken = $request['refresh_token'];
        $refreshToken = PersonalAccessToken::findToken($currentRefreshToken);

        if (!$refreshToken || $refreshToken->name !== 'refresh_token' || $refreshToken->expires_at->isPast()) {
            return 'InvalidOrExpiredRefreshToken';
        }

        $user = $refreshToken->tokenable;
        $refreshToken->delete();

        $accessTokenExpiresAt = Carbon::now()->addMinutes(60);
        $refreshTokenExpiresAt = Carbon::now()->addDays(7);

        $newAccessToken = $user->createToken('access_token', ['*'], $accessTokenExpiresAt)->plainTextToken;
        $newRefreshToken = $user->createToken('refresh_token', ['refresh'], $refreshTokenExpiresAt)->plainTextToken;

        $result = [
            'access_token' => $newAccessToken,
            'access_token_expires_at' => $accessTokenExpiresAt,
            'refresh_token' => $newRefreshToken,
            'refresh_token_expires_at' => $refreshTokenExpiresAt,
            'token_type' => 'Bearer',
        ];

        return $result;
    }
    ///////////////////////////////////////////////////////////////////////////////////////
    public function verify(int $id ,string $hash) 
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return 'InvalidLinkError';
        }

        if ($user->hasVerifiedEmail()) {
            return 'Emailverified';
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            return true;
        }

    }
    ////////////////////////////////////////////////////////////////////////////////////
    public function resend(array $request)
    {
        $user = User::where('email', $request['email'])->first();

        if ($user->hasVerifiedEmail()) {
           return 'EmailVerified';
        }

        $user->sendEmailVerificationNotification();
        return true;

    }
    











}
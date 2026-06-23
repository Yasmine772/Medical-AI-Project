<?php

namespace app\Services\Api;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Arr;


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

        return [
        'user' => $user,
        'access_token' => $user->createToken('access_token', ['*'], $accessTokenExpiresAt)->plainTextToken,
        'access_token_expires_at' => $accessTokenExpiresAt,
        'refresh_token' => $user->createToken('refresh_token', ['refresh'], $refreshTokenExpiresAt)->plainTextToken,
        'refresh_token_expires_at' => $refreshTokenExpiresAt,
        'token_type' => 'Bearer',
    ];
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

    /**
    * Processes the password reset request.
    * * Logic:
    * 1. Checks if user exists via the Broker.
    * 2. If exists, generates a secure token and saves it to 'password_reset_tokens'.
    * 3. Dispatches a notification to the user.
    * * @param string $email
    * @return bool True if the link was sent, false otherwise.
    * @throws Exception If mail server is unreachable.
    */
    public function forgetPassword(string $email)
    {
        $status = Password::sendResetLink(['email' => $email]);

        return $status ;
    }

    /**
     * Reset the user password using the reset token.
     * * Logic:
     * 1. Validates the token and email using Laravel's Password Broker.
     * 2. Updates the user's password if the token is valid.
     * 3. Invalidates all existing tokens to force a fresh session.
     *
     * @param array $data Contains email, password, password_confirmation, and token.
     * @return bool True on success, false on failure.
     */
    public function resetPassword(array $data): bool
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Revoke all existing tokens for security
                $user->tokens()->delete();
            }
        );

        return $status === Password::PASSWORD_RESET;
    }

    /**
    * Get the profile details of the given user.
    *
    * @param \App\Models\User $user
    * @return \App\Models\User
    */
    public function getUserProfile(User $user)
    {
        return $user->load('profile'); 
    }

    /**
     * Update the profile details of the given user.
     * @param \App\Models\User $user
     * @param array $data
     * @return \App\Models\User
     */

   public function updateProfile(User $user, array $data)
{
    $userData = array_intersect_key($data, array_flip(['full_name', 'birth_date', 'gender', 'avatar']));
    
    $medicalData = Arr::except($data, ['full_name', 'birth_date', 'gender']);  

    $user->update($userData);

    $user->profile()->updateOrCreate(
        ['user_id' => $user->id], 
        $medicalData              
    );

    return $user->fresh()->load('profile');
}

}
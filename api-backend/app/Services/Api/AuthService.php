<?php

namespace app\Services\Api;

use App\Http\Requests\User\VerifyOTPRequest;
use App\Models\User;
use App\Notifications\OTPNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Arr;
use App\Services\Api\OTPService;
use Illuminate\Support\Facades\DB;
use App\Notifications\ResetPasswordOTPNotification;




class AuthService
{
    public function register(array $data)
    {
        $user = User::create([
            'full_name' => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
        ]);
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

        $user->tokens()->delete(); 

        $accessTokenExpiresAt = Carbon::now()->addDays(1);

        $accessToken = $user->createToken('access_token', ['*'], $accessTokenExpiresAt)->plainTextToken;

        return [
        'user' => $user,
        'access_token' =>  $accessToken,
        'access_token_expires_at' => '1 day',
        'token_type' => 'Bearer',
    ];
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
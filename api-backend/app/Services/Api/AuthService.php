<?php

namespace app\Services\Api;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        $credentails = ['email' => $data['email'],
                        'password' => $data['password']];

        if(!Auth::attempt($credentails)){
            return $user = null;
        }

        $user = User::where('email' , $data['email'])->first();

        $token = $user->createToken('token')->plainTextToken;

        $result = [ 'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => config('sanctum.expiration')
                ];

        return $result;
    }
    











}
<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if(!$user){
            return response()->json([
                'status' => 'fail',
                'message' => 'User not registered successfully',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'data' => $user,
        ], 201);

    }
//******************************************************************** */
    public function login(LoginRequest $request)
    {
        
    }
//********************************************************************* */
    public function logout(Request $request)
    {
       
    }
}

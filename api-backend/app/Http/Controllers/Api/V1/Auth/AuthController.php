<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\Api\AuthService;
use Illuminate\Http\Request;
use Throwable;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
//-------------------------------------------------------------------------------------------
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->register($request->validated());

            return response()->json([
                'Status'  => 'Success',
                'message' => 'User has been registered successfully',
                'data'    => new UserResource($user)
            ], 201);
            
        } catch (Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
//-------------------------------------------------------------------------------------------
    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        if (!$result) {
            return response()->json([
                'Status'  => 'Error',
                'message' => 'Email or password not correct!',
                'data'    => null
            ], 422);                
        }

        return response()->json([
            'Status'  => 'Success',
            'message' => 'User login successfully',
            'User_info' => [
                'personal_info' => $result['user'],
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_in' => $result['expires_in']
            ],
        ], 200);
    }
//-------------------------------------------------------------------------------------------
    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();
        return response()->json([
            'Status'  => 'Success',
            'message' => 'User logout successfully',
        ], 200);
    }
}

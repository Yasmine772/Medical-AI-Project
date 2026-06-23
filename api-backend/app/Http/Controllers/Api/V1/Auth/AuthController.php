<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\Api\AuthService;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use Throwable;


class AuthController extends Controller
{
   
    use ApiResponseTrait;
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

            return $this->successResponse(new UserResource($user), 'User has been registered successfully', 201);
            
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to register user', $e->getMessage(), 500);
        }
    }
//-------------------------------------------------------------------------------------------
    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        if (!is_array($result)) {
        return $this->errorResponse('Email or password not correct!', null, 422);
        }
    
        return $this->successResponse([
        'user' => new UserResource($result['user']),
        'access_token' => $result['access_token'],
        'token_type'   => $result['token_type'],
        'expires_in'   => 3600 
         ], 'User login successfully', 200);
    }
//-------------------------------------------------------------------------------------------
    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();
        return $this->successResponse(null, 'User logout successfully', 200);
    }

    /**
     * Send a password reset link to the specified user email.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
     public function forgetPassword(Request $request)
     {
        try{
        $request->validate(['email' => 'required|email']); 
        
        $result = $this->authService->forgetPassword($request->email);
        
       if(!$result)
        {
            return $this->errorResponse('Email not found!', null, 422);
        }

        return $this->successResponse(null, 'Password reset link sent to your email', 200);
        }
        catch(Throwable $e)
        {
            return $this->errorResponse('Failed to send password reset link', $e->getMessage(), 500);
        }
        
    }
    /**
     * Reset the user's password using the provided token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function resetPassword(Request $request)
    {
        try {
        $request->validate([
            'token'                 => 'required|string',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed', 
        ]);

        $result = $this->authService->resetPassword($request->only('email', 'password', 'password_confirmation', 'token'));

        if (!$result) {
            return $this->errorResponse('Invalid or expired token', null, 422);
        }

        return $this->successResponse(null, 'Password reset successfully', 200);

        } 
     catch (Throwable $e) {
        return $this->errorResponse('Failed to reset password', $e->getMessage(), 500);

        }
    } 
    /**
     * View the profile details of the authenticated user.
     */

    public function viewProfile()
    {
    $user = auth()->user()->load('profile');

    if (!$user) {
        return $this->errorResponse('Unauthenticated', null, 401);
    }

    $profileData = $this->authService->getUserProfile($user);
    
    return $this->successResponse(new UserResource($profileData), 'Success', 200);
   }

    /**
     * Update the profile details of the authenticated user.
     * @param UpdateProfileRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
     $validatedData = $request->validated();
    
    $user = $this->authService->updateProfile(auth()->user(), $validatedData);
    
    return $this->successResponse(new UserResource($user), 'Profile updated successfully', 200);
    }
}

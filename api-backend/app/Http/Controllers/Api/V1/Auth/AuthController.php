<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\RefreshTokenRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Requests\User\ResendEmailVerificationRequest;
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
                'message' => 'User has been registered successfully and verification email has been sent✔',
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
        try {
            $result = $this->authService->login($request->validated());

            return match ($result) {
                'unVerifiedEmail' => response()->json([
                                        'Status' => 'Error',
                                        'message' => 'Please verify your email before logging in'
                                    ], 403),

                'unauthorized' => response()->json([
                                        'Status' => 'Error',
                                        'message' => 'Email or password not correct!'
                                    ], 401),

                $result => response()->json([
                            'Status'  => 'Success',
                            'message' => 'User login successfully',
                            'User_info' => $result
                        ], 200)
            };
        } 
        catch(Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //-------------------------------------------------------------------------------------------
    public function refreshToken(RefreshTokenRequest $request)
    {
        try {
            $result = $this->authService->refreshToken($request->validated());

            return match ($result) {
                'InvalidOrExpiredRefreshToken' => response()->json([
                                        'Status' => 'Error',
                                        'message' => 'Invalid or expired refresh token'
                                    ], 401),

                $result => response()->json([
                                    'Status'  => 'Success',
                                    'message' => 'Token refreshed successfully',
                                    'data' => $result
                                ],200)
            };
        } 
        catch (Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
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
    //-------------------------------------------------------------------------------------------
    public function verify(int $id , string $hash)
    {
        try {
            $result =  $this->authService->verify($id ,$hash);

            return match($result) {
                'InvalidLinkError' => response()->json([
                                            'Status' => 'Error',
                                            'message' => 'Invalid verification link'
                                            ], 400),

                'Emailverified' => response()->json([
                                            'Status' => 'Error',
                                            'message' => 'Email already verified'
                                            ], 409),

                true => response()->json([
                                    'Status' => 'Success',
                                    'message' => 'Email verified successfully'
                                    ], 200),
            };
            
        } catch (Throwable $e) {
            return response()->json([
                'Status' => 'Error',
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }
    //*********************************************************************** */
    public function resend(ResendEmailVerificationRequest $request)
    {
        try {
            $result = $this->authService->resend($request->validated());

            return match ($result) {
                'EmailVerified' => response()->json([
                                    'Status' => 'Error',
                                    'message' => 'Email already verified!'
                                ], 409),

                true => response()->json([
                                'Status' => 'Success',
                                'message' => 'Verification email sent'
                            ], 200)
            };

        } catch (Throwable $e) {
            return response()->json([
                'Status' => 'Error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

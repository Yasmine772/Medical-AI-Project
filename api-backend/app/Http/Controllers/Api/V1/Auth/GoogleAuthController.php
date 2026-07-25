<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use App\Services\Api\SocialAuthService;
use App\Traits\ApiResponseTrait;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Throwable;

class GoogleAuthController extends Controller
{
    use ApiResponseTrait;

    protected SocialAuthService $socialAuthService;

    public function __construct(SocialAuthService $socialAuthService)
    {
        $this->socialAuthService = $socialAuthService;
    }
     /**
      * Redirect the user to the Google authentication page.
      * @return \Illuminate\Http\JsonResponse
      */
    public function redirectToGoogle()
    {
        try {
            $url = Socialite::driver('google')
                ->stateless()  
                ->redirect()
                ->getTargetUrl();

            return $this->successResponse(
                ['url' => $url],
                'Google redirect URL generated',
                200
            );
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to generate Google URL', $e->getMessage(), 500);
        }
    }
 
    /**
     * Handle the callback from Google after user authentication.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleGoogleCallback(Request $request)
    {
    try {
        
        $googleUser = Socialite::driver('google')
            ->stateless()
            ->user();

        $user = $this->socialAuthService->findOrCreateUser($googleUser);
        $token = $user->createToken('google-auth-token')->plainTextToken;

        return $this->successResponse([
            'user'  => new UserResource($user),
            'token' => $token,
        ], 'Google login successful', 200);

    } catch (Throwable $e) {
        return $this->errorResponse('Google authentication failed', $e->getMessage(), 500);
    }
}
}
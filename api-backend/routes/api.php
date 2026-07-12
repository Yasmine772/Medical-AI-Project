<?php

use App\Http\Controllers\Api\V1\Ai\AiController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\settingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Forget and Reset Password
    Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // OTP
    Route::post('/verifyOtp', [AuthController::class, 'verifyOtp']);
    Route::post('/resendOtp', [AuthController::class, 'resendOtp']);

    //setting 
    Route::get('/latestUpdatesUrl', [settingController::class, 'latestUpdatesUrl']);
    Route::get('/termsOfUse', [settingController::class, 'termsOfUseUrl']);
    Route::get('/privacyPolicy', [settingController::class, 'privacyPolicyUrl']);


    Route::middleware(['auth:sanctum'])->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])->middleware('permission:user-logout');
        // Profile routes
        Route::get('/profile', [AuthController::class, 'viewProfile'])->middleware('permission:view-profile');
        Route::patch('/profile', [AuthController::class, 'updateProfile'])->middleware('permission:edit-profile');

        //AI routes
        Route::get('/search', [AiController::class, 'search'])->middleware('permission:search-symptom');
        Route::post('/diagnose/start', [AiController::class, 'start'])->middleware('permission:start-diagnose');
        Route::post('/diagnose/continue', [AiController::class, 'continue'])->middleware('permission:continue-diagnose');

        // Check if the user is authenticated
        Route::get('/check-auth', [AuthController::class, 'checkAuthentication']);

       
    });
});

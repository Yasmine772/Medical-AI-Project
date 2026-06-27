<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;





Route::prefix('v1/auth')->group(function (){
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Google OAuth
    Route::get('/google/redirect',  [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback',  [GoogleAuthController::class, 'handleGoogleCallback']);

    //Forget and Reset Password
    Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);




    Route::middleware(['auth:sanctum'])->group(function () {

       Route::post('/logout', [AuthController::class, 'logout']);
       //Profile routes
       Route::get('/profile/',[AuthController::class,'viewProfile']);
       Route::patch('/profile/',[AuthController::class,'updateProfile']);

       //OTP 
        Route::post('/verifyOtp', [AuthController::class, 'verifyOtp']);
        Route::post('/resendOtp', [AuthController::class, 'resendOtp']); 

          //Check if the user is authenticated
        Route::get('/check-auth', [AuthController::class, 'checkAuthentication']);

    });
});

 


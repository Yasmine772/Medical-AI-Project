<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;





Route::prefix('v1/auth')->group(function (){
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refreshToken']);

    //email verification:
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->name('verification.verify')
                                                                              ->middleware(['signed']);
    Route::post('/email/resend', [AuthController::class, 'resend']);

    // Google OAuth
    Route::get('/auth/google/redirect',  [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback',  [GoogleAuthController::class, 'handleGoogleCallback']);

    //Forget and Reset Password
    Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);


    Route::middleware(['auth:sanctum'])->group(function () {

       Route::post('/auth/logout', [AuthController::class, 'logout']);
       //Profile routes
       Route::get('/profile/',[AuthController::class,'viewProfile']);
       Route::patch('/profile/',[AuthController::class,'updateProfile']);


    });

 
});
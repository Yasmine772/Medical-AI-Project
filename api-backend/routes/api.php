<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;





Route::prefix('v1')->group(function (){
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

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
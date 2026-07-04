<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;





Route::prefix('v1/auth')->group(function (){
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    //Forget and Reset Password
    Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);


    //OTP 
    Route::post('/verifyOtp', [AuthController::class, 'verifyOtp']);
    Route::post('/resendOtp', [AuthController::class, 'resendOtp']); 

    Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('permission:logout');
    //Profile routes
    Route::get('/profile',[AuthController::class,'viewProfile'])->middleware('permission:view-profile');
    Route::patch('/profile',[AuthController::class,'updateProfile'])->middleware('permission:edit-profile');

    //Check if the user is authenticated
    Route::get('/check-auth', [AuthController::class, 'checkAuthentication']);

    });
});

 


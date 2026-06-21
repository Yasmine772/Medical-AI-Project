<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;



Route::prefix('v1/auth/user')->group(function (){
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refreshToken']);

    //email verification:
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->name('verification.verify')
                                                                              ->middleware(['signed']);
    Route::post('/email/resend', [AuthController::class, 'resend']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });


    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        //Any route needs email verification
    });
});
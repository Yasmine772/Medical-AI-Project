<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;





Route::prefix('v1')->group(function (){
    Route::post('/auth/user/register', [AuthController::class, 'register']);
    Route::post('/auth/user/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/auth/user/logout', [AuthController::class, 'logout']);
    });
});
<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Reports\ReportController;
use App\Http\Controllers\Api\V1\settingController;
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

        Route::post('/logout', [AuthController::class, 'logout'])->middleware('permission:user-logout');
        // Profile routes
        Route::get('/profile', [AuthController::class, 'viewProfile'])->middleware('permission:view-profile');
        Route::patch('/profile', [AuthController::class, 'updateProfile'])->middleware('permission:edit-profile');

        //AI routes
        Route::post('/diagnosis/start', [AiController::class, 'startDiagnosis'])->middleware('permission:start-diagnose');
        Route::get('/symptoms', [AiController::class, 'searchSymptoms'])->middleware('permission:search-symptom');
        Route::get('/symptoms/questions/{sessionId}', [AiController::class, 'getSymptomQuestions'])->middleware('permission:view-symptom-questions');
        Route::get('/follow-up/next/{sessionId}', [AiController::class, 'getNextDiagnosisQuestion'])->middleware('permission:continue-diagnose');
        Route::post('/follow-up/answer/{sessionId}', [AiController::class, 'submitDiagnosisAnswer'])->middleware('permission:continue-diagnose');
        Route::post('/symptoms/answers', [AiController::class, 'submitSymptomAnswers'])->middleware('permission:submit-symptom-answers');
        Route::get('/diagnose/history', [AiController::class, 'getDiagnosisHistory'])->middleware('permission:view-diagnosis-history');

    });

 
});
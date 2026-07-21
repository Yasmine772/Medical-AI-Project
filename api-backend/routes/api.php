<?php

use App\Http\Controllers\Api\V1\Ai\AiController;

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Payment\PaymentController;
use App\Http\Controllers\Api\V1\Reports\ReportController;
use App\Http\Controllers\Api\V1\settingController;
use App\Http\Controllers\Api\V1\User\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/stripe/webhook', [PaymentController::class, 'handleWebhook']);

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Forget and Reset Password
    Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // OTP
    Route::post('/verifyOtp', [AuthController::class, 'verifyOtp']);
    Route::post('/resendOtp', [AuthController::class, 'resendOtp']);

    // setting
    Route::get('/latestUpdatesUrl', [settingController::class, 'latestUpdatesUrl']);
    Route::get('/termsOfUse', [settingController::class, 'termsOfUseUrl']);
    Route::get('/privacyPolicy', [settingController::class, 'privacyPolicyUrl']);

    Route::middleware(['auth:sanctum'])->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])->middleware('permission:user-logout');
        // Profile routes
        Route::get('/profile', [AuthController::class, 'viewProfile'])->middleware('permission:view-profile');
        Route::patch('/profile', [AuthController::class, 'updateProfile'])->middleware('permission:edit-profile');

        // AI routes
        Route::post('/diagnosis/start', [AiController::class, 'startDiagnosis'])->middleware('permission:start-diagnose');
        Route::get('/symptoms', [AiController::class, 'searchSymptoms'])->middleware('permission:search-symptom');
        Route::post('/symptom/select', [AiController::class, 'getSymptomQuestions'])->middleware('permission:view-symptom-questions');
        Route::get('/follow-up/next', [AiController::class, 'getNextDiagnosisQuestion'])->middleware('permission:continue-diagnose');
        Route::post('/follow-up/answer', [AiController::class, 'submitDiagnosisAnswer'])->middleware('permission:continue-diagnose');
        Route::get('/diagnose/history', [AiController::class, 'getDiagnosisHistory'])->middleware('permission:view-medical-history');

        // Report routes
        Route::post('/reports/{sessionId}/generate', [ReportController::class, 'generate'])->middleware('permission:download-report');
        Route::get('/reports/{sessionId}/download', [ReportController::class, 'download'])->middleware('permission:download-report');
        Route::get('/reports/{sessionId}/preview', [ReportController::class, 'preview'])->middleware('permission:download-report');

        // Payment routes
        Route::post('/payments/create-intent', [PaymentController::class, 'createIntent']);
        Route::get('/payments/{paymentIntentId}/status', [PaymentController::class, 'status']);

        // Check if the user is authenticated
        Route::get('/check-auth', [AuthController::class, 'checkAuthentication']);

        // Notifications routes
       Route::prefix('notifications')->group(function () {
       Route::get('/', [NotificationController::class, 'index']);
       Route::patch('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
       Route::delete('/destroy-all', [NotificationController::class, 'destroyAll']);
       Route::get('/count-unread', [NotificationController::class, 'countUnreadNotifications']);

       Route::patch('/{notificationId}/read', [NotificationController::class, 'markAsRead']);
       Route::patch('/{notificationId}/unread', [NotificationController::class, 'markAsUnread']);
       Route::delete('/{notificationId}', [NotificationController::class, 'destroy']);
     });
        
    });
    
});

<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\settingController;
use App\Http\Controllers\Api\V1\User\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\web\Admin\AuditLogs\AuditContoller;
use App\Http\Controllers\Web\Admin\Dashboard\DashboardController;
use App\Http\Controllers\web\Admin\UserManagement\UserController;
use App\Http\Controllers\Web\Auth\AuthController as WebAuthController;
use App\Http\Controllers\Web\DoctorManagement\DoctorController;

Route::get('/', function () {
    return view('welcome');
});

//setting
Route::get('/legal/terms-of-use', [settingController::class, 'termsOfUse']);
Route::get('/legal/privacy-policy', [settingController::class, 'privacyPolicy']);
Route::get('/app/updates/latest', [settingController::class, 'latestUpdates']);


Route::prefix('admin')->group(function () {
    Route::post('/login', [WebAuthController::class, 'adminLogin']);
    Route::post('/verifyOtp', [WebAuthController::class, 'adminVerifyOtp']);
    Route::post('/resendOtp', [AuthController::class, 'resendOtp']);

    Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    // Route::patch('doctor-requests/approve/{id}', [DoctorController::class, 'approve']);
    Route::patch('doctor-requests/reject/{id}', [DoctorController::class, 'reject']);


    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])->middleware('permission:admin-logout');
        // Profile routes
        Route::get('/profile', [AuthController::class, 'viewProfile'])->middleware('permission:view-profile');
        Route::patch('/profile', [AuthController::class, 'updateProfile'])->middleware('permission:edit-profile');

       // User Management 
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:view-users');
        Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:toggle-user');

        // Audit Logs
        Route::prefix('audit-logs')->group(function () {
            Route::get('/', [AuditContoller::class, 'showLogs']);
            Route::get('/count', [AuditContoller::class, 'countLogs']);
            Route::get('/changes', [AuditContoller::class, 'changeLogs']);
        });

        // Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('/current-date', [DashboardController::class, 'currentDate']);
            Route::get('/type-of-patient-count',[DashboardController::class, 'typeOfPatientCount']);
            Route::get('/user-active-count', [DashboardController::class, 'userActiveCount']);
            Route::get('/doctor-active-count', [DashboardController::class, 'DoctorActiveCount']);
            Route::get('/daily-diagnoses-count', [DashboardController::class, 'dailyDiagnosesCount']);
            Route::get('/new-content-items-count', [DashboardController::class, 'newContentItemsCount']);
            Route::get('/top-specialties-by-diagnoses', [DashboardController::class, 'getTopDiseasesByDiagnoses']);
            Route::get('/diagnosis-sessions-status-count', [DashboardController::class, 'diagnosisSessionsStatusCount']);
        });

        //Doctor management 
        Route::prefix('doctor-requests')->group(function () {
            Route::get('/', [DoctorController::class, 'index'])->middleware('permission:show-doctor-requests');
            Route::get('/{id}', [DoctorController::class, 'show'])->middleware('permission:show-doctor-request-details');
            Route::patch('approve/{id}', [DoctorController::class, 'approve'])->middleware('permission:approve-doctor-request');
            // Route::patch('reject/{id}', [DoctorController::class, 'reject'])->middleware('permission:reject-doctor-request');
        });
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/count-unread', [NotificationController::class, 'countUnreadNotifications']);
        Route::patch('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
        Route::patch('/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    });
});



Route::prefix('doctor')->group(function () {
    Route::post('/sendJoinRequest', [DoctorController::class, 'sendJoinRequest']);
    Route::post('/login', [WebAuthController::class, 'doctorLogin']);
    Route::post('/verifyOtp', [WebAuthController::class, 'doctorVerifyOtp']);
    Route::post('/resendOtp', [AuthController::class, 'resendOtp']);


    Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

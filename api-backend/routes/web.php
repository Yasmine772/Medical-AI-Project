<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\settingController;
use App\Http\Controllers\Api\V1\User\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Admin\AuditLogs\AuditContoller;
use App\Http\Controllers\Web\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Web\Admin\UserManagement\UserController;
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

    // Route::get('doctor-requests/', [DoctorController::class, 'index']);
    // Route::patch('doctor-requests/approve/{id}', [DoctorController::class, 'approve']);
    // Route::patch('doctor-requests/reject/{id}', [DoctorController::class, 'reject']);

    // Route::get('/notifications', [NotificationController::class, 'index']);
    // Route::get('notifications/count-unread', [NotificationController::class, 'countUnreadNotifications']);
    // Route::patch('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    // Route::patch('notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);


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
            Route::get('/', [AuditContoller::class, 'showLogs'])->middleware('permission:show-logs');
            Route::get('/count', [AuditContoller::class, 'countLogs'])->middleware('permission:count-logs');
            Route::get('/changes', [AuditContoller::class, 'changeLogs'])->middleware('permission:change-logs');
        });

        // Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('/current-date', [DashboardController::class, 'currentDate'])->middleware('permission:show-currentDate');
            Route::get('/type-of-patient-count',[DashboardController::class, 'typeOfPatientCount'])->middleware('permission:type-of-patient-count');
            Route::get('/user-active-count', [DashboardController::class, 'userActiveCount'])->middleware('permission:user-active-count');
            Route::get('/doctor-active-count', [DashboardController::class, 'DoctorActiveCount'])->middleware('permission:doctor-active-count');
            Route::get('/daily-diagnoses-count', [DashboardController::class, 'dailyDiagnosesCount'])->middleware('permission:daily-diagnoses-count');
            Route::get('/new-content-items-count', [DashboardController::class, 'newContentItemsCount'])->middleware('permission:new-content-items-count');
            Route::get('/top-specialties-by-diagnoses', [DashboardController::class, 'getTopDiseasesByDiagnoses'])->middleware('permission:top-specialties-by-diagnoses');
            Route::get('/diagnosis-sessions-status-count', [DashboardController::class, 'diagnosisSessionsStatusCount'])->middleware('permission:diagnosis-sessions-status-count');
        });

        //Doctor management 
        Route::prefix('doctor-requests')->group(function () {
            Route::get('/', [DoctorController::class, 'index'])->middleware('permission:show-doctor-requests');
            Route::get('/{id}', [DoctorController::class, 'show'])->middleware('permission:show-doctor-request-details');
            Route::patch('approve/{id}', [DoctorController::class, 'approve'])->middleware('permission:approve-doctor-request');
            Route::patch('reject/{id}', [DoctorController::class, 'reject'])->middleware('permission:reject-doctor-request');
        });
        
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->middleware('permission:show-all-notifications');
        Route::get('/count-unread', [NotificationController::class, 'countUnreadNotifications'])->middleware('permission:show-count-unread-notifications');
        Route::patch('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->middleware('permission:mark-all-as-read-notifications');
        Route::patch('/{notificationId}/read', [NotificationController::class, 'markAsRead'])->middleware('permission:mark-as-read-notifications');
    });
});

Route::prefix('doctor')->group(function () {
    Route::post('/sendJoinRequest', [DoctorController::class, 'sendJoinRequest']);
    Route::post('/login', [WebAuthController::class, 'doctorLogin']);
    Route::post('/verifyOtp', [WebAuthController::class, 'doctorVerifyOtp']);
    Route::post('/resendOtp', [AuthController::class, 'resendOtp']);


    Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('permission:doctor-logout');
    });
});

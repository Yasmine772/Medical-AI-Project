<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\settingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Admin\UserManagement\UserController;
use App\Http\Controllers\web\Admin\AuditLogs\AuditContoller;
use App\Http\Controllers\Web\Admin\Dashboard\DashboardController;
Route::get('/', function () {
    return view('welcome');
});

//setting
Route::get('/legal/terms-of-use', [settingController::class, 'termsOfUse']);
Route::get('/legal/privacy-policy', [settingController::class, 'privacyPolicy']);
Route::get('/app/updates/latest', [settingController::class, 'latestUpdates']);


Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verifyOtp', [AuthController::class, 'verifyOtp']);
    Route::post('/resendOtp', [AuthController::class, 'resendOtp']);
    Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);


    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])->middleware('permission:logout');
        // Profile routes
        // Route::get('/profile', [AuthController::class, 'viewProfile'])->middleware('permission:view-profile');
        // Route::patch('/profile', [AuthController::class, 'updateProfile'])->middleware('permission:edit-profile');

       // User Management 
       Route::get('/users', [UserController::class, 'index']);
       Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);

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
    });
});
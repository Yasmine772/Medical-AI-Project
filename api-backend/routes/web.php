<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\settingController;
use App\Http\Controllers\Web\DoctorManagement\DoctorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\web\UserManagement\UserController;
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

        Route::post('/logout', [AuthController::class, 'logout'])->middleware('permission:admin-logout');
        // Profile routes
        // Route::get('/profile', [AuthController::class, 'viewProfile'])->middleware('permission:view-profile');
        // Route::patch('/profile', [AuthController::class, 'updateProfile'])->middleware('permission:edit-profile');

       // User Management 
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:view-users');
        Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:toggle-user');

        // Doctor Requests Management
        Route::get('/doctor-requests', [DoctorController::class, 'index']);
        //->middleware('permission:show-doctor-requests');
        Route::get('/doctor-requests/{id}', [DoctorController::class, 'show']);
        //->middleware('permission:show-doctor-request-details');
        Route::patch('/doctor-requests/approve/{id}', [DoctorController::class, 'approve']);
        //->middleware('permission:approve-doctor-request');
        Route::patch('/doctor-requests/reject/{id}', [DoctorController::class, 'reject']);
        //->middleware('permission:reject-doctor-request');
    }); 
});



Route::prefix('doctor')->group(function () {
    Route::post('/sendJoinRequest', [DoctorController::class, 'sendJoinRequest']);

});
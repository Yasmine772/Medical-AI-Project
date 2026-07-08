<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\settingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//setting
Route::get('/legal/terms-of-use', [settingController::class, 'termsOfUse']);
Route::get('/legal/privacy-policy', [settingController::class, 'privacyPolicy']);
Route::get('/app/updates/latest', [settingController::class, 'latestUpdates']);

<?php

use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('register', [RegisterController::class, 'store']);
    Route::post('login', [LoginController::class, 'store']);
    Route::post('forgot-password', [ForgotPasswordController::class, 'store']);
    Route::post('reset-password', [ResetPasswordController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [MeController::class, 'show']);
        Route::post('logout', [LoginController::class, 'destroy']);
    });
});

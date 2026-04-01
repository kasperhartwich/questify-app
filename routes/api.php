<?php

use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\GameplayController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\QuestController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Controllers\Api\V1\UserProfileController;
use App\Http\Controllers\Api\V1\UserQuestController;
use App\Http\Controllers\Api\V1\UserSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/health', HealthController::class);

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/register', [RegisterController::class, 'store'])->name('auth.register');
        Route::post('/login', [LoginController::class, 'store'])->name('auth.login');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('auth.forgot-password');
        Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('auth.reset-password');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [MeController::class, 'show'])->name('auth.me');
            Route::post('/logout', [LoginController::class, 'destroy'])->name('auth.logout');
            Route::delete('/social/{provider}', [SocialAuthController::class, 'unlink'])->name('auth.social.unlink');
        });
    });

    // Guest-accessible
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/quests', [QuestController::class, 'index'])->name('quests.index');
    Route::get('/quests/nearby', [QuestController::class, 'nearby'])->name('quests.nearby');
    Route::get('/quests/{quest}', [QuestController::class, 'show'])->name('quests.show');
    Route::post('/quests/{quest}/flag', [QuestController::class, 'flag'])->name('quests.flag');

    // Guest-accessible session/gameplay
    Route::get('/sessions/{code}', [SessionController::class, 'show'])->name('sessions.show');
    Route::post('/sessions/{code}/join', [SessionController::class, 'join'])->name('sessions.join');
    Route::post('/sessions/{code}/arrived', [GameplayController::class, 'arrived'])->name('gameplay.arrived');
    Route::post('/sessions/{code}/answer', [GameplayController::class, 'answer'])->name('gameplay.answer');
    Route::get('/sessions/{code}/leaderboard', [GameplayController::class, 'leaderboard'])->name('gameplay.leaderboard');

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        // Quests
        Route::post('/quests', [QuestController::class, 'store'])->name('quests.store');
        Route::put('/quests/{quest}', [QuestController::class, 'update'])->name('quests.update');
        Route::delete('/quests/{quest}', [QuestController::class, 'destroy'])->name('quests.destroy');
        Route::post('/quests/{quest}/publish', [QuestController::class, 'publish'])->name('quests.publish');
        Route::post('/quests/{quest}/rate', [QuestController::class, 'rate'])->name('quests.rate');

        // Sessions
        Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
        Route::post('/sessions/{code}/start', [SessionController::class, 'start'])->name('sessions.start');
        Route::post('/sessions/{code}/end', [SessionController::class, 'end'])->name('sessions.end');
        Route::get('/sessions/{code}/dashboard', [SessionController::class, 'dashboard'])->name('sessions.dashboard');

        // User
        Route::get('/user/quests', [UserQuestController::class, 'index'])->name('user.quests');
        Route::get('/user/sessions', [UserSessionController::class, 'index'])->name('user.sessions');
        Route::put('/user/profile', [UserProfileController::class, 'update'])->name('user.profile.update');
        Route::delete('/user', [UserProfileController::class, 'destroy'])->name('user.destroy');
    });

}); // end v1 prefix

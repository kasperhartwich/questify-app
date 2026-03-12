<?php

use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckpointController;
use App\Http\Controllers\Api\V1\GameplayController;
use App\Http\Controllers\Api\V1\QuestController;
use App\Http\Controllers\Api\V1\QuestionController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Controllers\Api\V1\UserProfileController;
use App\Http\Controllers\Api\V1\UserQuestController;
use App\Http\Controllers\Api\V1\UserSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [RegisterController::class, 'store']);
        Route::post('login', [LoginController::class, 'store']);
        Route::post('forgot-password', [ForgotPasswordController::class, 'store']);
        Route::post('reset-password', [ResetPasswordController::class, 'store']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [MeController::class, 'show']);
            Route::post('logout', [LoginController::class, 'destroy']);
        });
    });

    // Public routes
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('sessions/{code}', [SessionController::class, 'show'])->name('sessions.show');

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {

        // Quests
        Route::apiResource('quests', QuestController::class);
        Route::post('quests/{quest}/publish', [QuestController::class, 'publish'])->name('quests.publish');
        Route::post('quests/{quest}/rate', [QuestController::class, 'rate'])->name('quests.rate');
        Route::post('quests/{quest}/flag', [QuestController::class, 'flag'])->name('quests.flag');

        // Checkpoints (nested under quests)
        Route::apiResource('quests.checkpoints', CheckpointController::class);
        Route::post('quests/{quest}/checkpoints/reorder', [CheckpointController::class, 'reorder'])
            ->name('quests.checkpoints.reorder');

        // Questions (nested under quests.checkpoints)
        Route::apiResource('quests.checkpoints.questions', QuestionController::class);

        // Sessions
        Route::post('sessions', [SessionController::class, 'store'])->name('sessions.store');
        Route::post('sessions/{code}/join', [SessionController::class, 'join'])->name('sessions.join');
        Route::post('sessions/{code}/start', [SessionController::class, 'start'])->name('sessions.start');
        Route::post('sessions/{code}/end', [SessionController::class, 'end'])->name('sessions.end');
        Route::get('sessions/{code}/dashboard', [SessionController::class, 'dashboard'])->name('sessions.dashboard');

        // Gameplay
        Route::post('sessions/{code}/checkpoints/{checkpoint}/arrived', [GameplayController::class, 'arrived'])
            ->name('gameplay.arrived');
        Route::post('sessions/{code}/checkpoints/{checkpoint}/questions/{question}/answer', [GameplayController::class, 'answer'])
            ->name('gameplay.answer');
        Route::get('sessions/{code}/leaderboard', [GameplayController::class, 'leaderboard'])
            ->name('gameplay.leaderboard');

        // User quests
        Route::get('user/quests/created', [UserQuestController::class, 'created'])->name('user.quests.created');
        Route::get('user/quests/played', [UserQuestController::class, 'played'])->name('user.quests.played');

        // User sessions
        Route::get('user/sessions', [UserSessionController::class, 'index'])->name('user.sessions.index');

        // User profile
        Route::put('user/profile', [UserProfileController::class, 'update'])->name('user.profile.update');
        Route::delete('user/profile', [UserProfileController::class, 'destroy'])->name('user.profile.destroy');
    });
});

<?php

use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;

// Welcome (guest landing page)
Route::livewire('/', 'pages::welcome.index')->name('welcome');

// Auth routes
Route::livewire('/login', 'pages::auth.login')->name('login');
Route::livewire('/register', 'pages::auth.register')->name('register');

// OAuth routes
Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect']);
Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback']);

// Discover (public)
Route::livewire('/discover/list', 'pages::discover.quest-list')->name('discover.list');
Route::livewire('/discover/map', 'pages::discover.quest-map')->name('discover.map');
Route::livewire('/quests/{quest}', 'pages::discover.quest-detail')->name('quests.show');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // My Quests
    Route::livewire('/my-quests', 'pages::my-quests.played-quests')->name('my-quests');
    Route::livewire('/my-quests/created', 'pages::my-quests.created-quests')->name('my-quests.created');

    // Profile
    Route::livewire('/profile', 'pages::profile.settings')->name('profile');
});

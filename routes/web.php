<?php

use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Middleware\ClearEdgeComponents;
use Illuminate\Support\Facades\Route;

// Welcome (guest landing page)
Route::livewire('/', 'pages::welcome.index')->name('welcome');

// Locale toggle
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'da'])) {
        session(['locale' => $locale]);
    }

    return redirect('/');
})->name('locale.switch');

// Join (guest-accessible)
Route::livewire('/join', 'pages::join.index')->name('join');
Route::livewire('/join/{code}/name', 'pages::join.display-name')->name('join.name');

// Auth routes
Route::livewire('/login', 'pages::auth.login')->name('login');
Route::livewire('/register', 'pages::auth.register')->name('register');
Route::livewire('/forgot-password', 'pages::auth.forgot-password')->name('password.request');

// OAuth routes
Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect']);
Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback']);

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Discover
    Route::livewire('/discover/list', 'pages::discover.quest-list')->name('discover.list');
    Route::livewire('/discover/map', 'pages::discover.quest-map')->name('discover.map')->middleware(ClearEdgeComponents::class);
    Route::livewire('/quests/{quest}', 'pages::discover.quest-detail')->name('discover.quest');

    // My Quests
    Route::livewire('/my-quests', 'pages::my-quests.played-quests')->name('my-quests');
    Route::livewire('/my-quests/created', 'pages::my-quests.created-quests')->name('my-quests.created');

    // Quest Creation
    Route::livewire('/create', 'pages::create.quest-wizard')->name('quests.create');

    // Session / Gameplay
    Route::livewire('/session/{code}', 'pages::session.lobby')->name('session.lobby');
    Route::livewire('/session/{code}/play', 'pages::session.active-quest')->name('session.play');
    Route::livewire('/session/{code}/question/{checkpoint}', 'pages::session.question-screen')->name('session.question');
    Route::livewire('/session/{code}/complete', 'pages::session.quest-complete')->name('session.complete');
    Route::livewire('/session/{code}/host', 'pages::session.host-dashboard')->name('session.host');

    // Profile
    Route::livewire('/profile', 'pages::profile.settings')->name('profile');
});

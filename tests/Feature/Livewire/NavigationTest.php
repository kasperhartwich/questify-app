<?php

use App\Models\User;

it('guest is redirected to login from protected routes', function (string $route) {
    $this->get($route)->assertRedirect('/login');
})->with([
    '/create',
    '/profile',
    '/my-quests',
    '/my-quests/created',
]);

it('authenticated user can access protected routes', function (string $route) {
    $user = User::factory()->create();

    $this->actingAs($user)->get($route)->assertOk();
})->with([
    '/profile',
    '/my-quests',
    '/my-quests/created',
    '/create',
]);

<?php

use App\Models\User;

test('the welcome page renders for guests', function () {
    $this->get('/')->assertOk();
});

test('the welcome page redirects authenticated users to discover', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect('/discover/list');
});

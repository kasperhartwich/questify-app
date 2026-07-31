<?php

use App\Models\User;

test('the welcome page renders for guests', function () {
    $this->get('/')->assertOk();
});

test('the welcome page renders for authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk();
});

<?php

it('guest is redirected to login from protected routes', function (string $route) {
    $this->get($route)->assertRedirect('/login');
})->with([
    '/create',
    '/profile',
    '/my-quests',
    '/my-quests/created',
]);

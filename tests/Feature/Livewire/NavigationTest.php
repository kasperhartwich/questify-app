<?php

it('guest is redirected to welcome from protected routes', function (string $route) {
    $this->get($route)->assertRedirect('/');
})->with([
    '/create',
    '/profile',
    '/my-quests',
    '/my-quests/created',
]);

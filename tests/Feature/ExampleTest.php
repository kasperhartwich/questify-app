<?php

test('the application redirects to discover', function () {
    $response = $this->get('/');

    $response->assertRedirect('/discover/list');
});

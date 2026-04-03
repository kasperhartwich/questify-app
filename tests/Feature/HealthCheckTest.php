<?php

it('returns ok status', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk()
        ->assertExactJson(['status' => 'ok']);
});

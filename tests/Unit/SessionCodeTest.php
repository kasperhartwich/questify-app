<?php

use App\Models\QuestSession;
use Illuminate\Support\Str;

it('generates a 6-character uppercase alphanumeric code', function () {
    $code = strtoupper(Str::random(6));

    expect($code)->toMatch('/^[A-Z0-9]{6}$/');
    expect(strlen($code))->toBe(6);
});

it('generates unique codes across multiple sessions', function () {
    $codes = collect();

    for ($i = 0; $i < 50; $i++) {
        $codes->push(strtoupper(Str::random(6)));
    }

    // All codes should be unique (extremely high probability with 6-char alphanumeric)
    expect($codes->unique()->count())->toBe(50);
});

it('session factory produces valid join codes', function () {
    $session = QuestSession::factory()->create();

    expect($session->join_code)->toMatch('/^[A-Z0-9]{6}$/');
    expect(strlen($session->join_code))->toBe(6);
});

it('session join codes are unique in database', function () {
    $sessions = QuestSession::factory()->count(10)->create();

    $codes = $sessions->pluck('join_code');

    expect($codes->unique()->count())->toBe(10);
});

it('join code is uppercase only', function () {
    $code = strtoupper(Str::random(6));

    expect($code)->toBe(strtoupper($code));
    expect($code)->not->toMatch('/[a-z]/');
});

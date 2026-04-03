<?php

use App\Models\User;

it('uses session locale when set', function () {
    $this->withSession(['locale' => 'da'])
        ->get('/');

    expect(app()->getLocale())->toBe('da');
});

it('falls back to authenticated user locale', function () {
    $user = User::factory()->create(['locale' => 'da']);

    $this->actingAs($user)->get('/');

    expect(app()->getLocale())->toBe('da');
});

it('falls back to accept-language header', function () {
    $this->get('/', ['Accept-Language' => 'da']);

    expect(app()->getLocale())->toBe('da');
});

it('falls back to default locale', function () {
    $this->get('/');

    expect(app()->getLocale())->toBe(config('app.locale'));
});

it('ignores unsupported locales in session', function () {
    $this->withSession(['locale' => 'fr'])
        ->get('/');

    expect(app()->getLocale())->not->toBe('fr');
});

it('ignores unsupported locales from user', function () {
    $user = User::factory()->create(['locale' => 'fr']);

    $this->actingAs($user)->get('/');

    expect(app()->getLocale())->not->toBe('fr');
});

it('prioritises session locale over user locale', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->withSession(['locale' => 'da'])
        ->get('/');

    expect(app()->getLocale())->toBe('da');
});

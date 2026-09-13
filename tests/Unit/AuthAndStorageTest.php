<?php

use App\Auth\ApiTokenUser;
use App\Services\Api\QuestifyApiClient;
use App\Services\MissingTranslationReporter;
use App\Services\TokenStorage;

// --- ApiTokenUser ---

it('reads the account off the api payload', function () {
    $user = new ApiTokenUser([
        'id' => 7,
        'name' => 'Kasper',
        'email' => 'kasper@example.com',
        'avatar_url' => 'https://example.com/a.png',
        'locale' => 'da',
        'created_at' => '2026-01-01T00:00:00Z',
        'linked_providers' => ['apple', 'google'],
    ]);

    expect($user->id)->toBe(7)
        ->and($user->name)->toBe('Kasper')
        ->and($user->email)->toBe('kasper@example.com')
        ->and($user->avatarUrl)->toBe('https://example.com/a.png')
        ->and($user->locale)->toBe('da')
        ->and($user->linkedProviders)->toBe(['apple', 'google']);
});

it('falls back to sensible defaults for a sparse payload', function () {
    $user = new ApiTokenUser(['id' => 7]);

    expect($user->name)->toBeNull()
        ->and($user->email)->toBeNull()
        ->and($user->locale)->toBe('en')
        ->and($user->linkedProviders)->toBe([]);
});

it('identifies itself to the auth system by id', function () {
    $user = new ApiTokenUser(['id' => 7, 'name' => 'Kasper']);

    expect($user->getAuthIdentifierName())->toBe('id')
        ->and($user->getAuthIdentifier())->toBe(7);
});

it('carries no password or remember token', function () {
    // The account lives behind the API; nothing local can be used to sign in.
    $user = new ApiTokenUser(['id' => 7]);

    expect($user->getAuthPassword())->toBe('')
        ->and($user->getRememberToken())->toBeNull()
        ->and($user->getRememberTokenName())->toBe('');

    $user->setRememberToken('anything');

    expect($user->getRememberToken())->toBeNull();
});

it('serialises back to the shape the session stores', function () {
    $payload = [
        'id' => 7,
        'name' => 'Kasper',
        'email' => 'kasper@example.com',
        'avatar_url' => null,
        'locale' => 'da',
        'created_at' => '2026-01-01T00:00:00Z',
    ];

    // Round-tripping matters: the guard rebuilds the user from what it stored.
    expect((new ApiTokenUser($payload))->toArray())->toBe($payload);
});

// --- TokenStorage ---

it('reports no token before anyone signs in', function () {
    expect(TokenStorage::has())->toBeFalse()
        ->and(TokenStorage::get())->toBeNull();
});

it('keeps a token it was given', function () {
    TokenStorage::set('a-token');

    expect(TokenStorage::get())->toBe('a-token')
        ->and(TokenStorage::has())->toBeTrue();
});

it('forgets the token on sign out', function () {
    TokenStorage::set('a-token');
    TokenStorage::forget();

    expect(TokenStorage::has())->toBeFalse();
});

it('replaces the token rather than keeping both', function () {
    TokenStorage::set('first');
    TokenStorage::set('second');

    expect(TokenStorage::get())->toBe('second');
});

// --- MissingTranslationReporter ---

function reporterWith(QuestifyApiClient $client): MissingTranslationReporter
{
    return new MissingTranslationReporter($client);
}

it('reports each missing key once, however often it is seen', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('post')->once()->with('/translations/missing', [
        'keys' => [['key' => 'general.missing', 'locale' => 'da']],
    ])->andReturn(['data' => []]);

    $reporter = reporterWith($client);
    $reporter->report('general.missing', 'da');
    $reporter->report('general.missing', 'da');
    $reporter->flush();
});

it('reports the same key separately for each language', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('post')->once()->with('/translations/missing', [
        'keys' => [
            ['key' => 'general.missing', 'locale' => 'da'],
            ['key' => 'general.missing', 'locale' => 'en'],
        ],
    ])->andReturn(['data' => []]);

    $reporter = reporterWith($client);
    $reporter->report('general.missing', 'da');
    $reporter->report('general.missing', 'en');
    $reporter->flush();
});

it('keeps a dotted key intact rather than splitting on the locale prefix', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('post')->once()->with('/translations/missing', [
        'keys' => [['key' => 'quests.checkpoints.title', 'locale' => 'en']],
    ])->andReturn(['data' => []]);

    $reporter = reporterWith($client);
    $reporter->report('quests.checkpoints.title', 'en');
    $reporter->flush();
});

it('says nothing when nothing is missing', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldNotReceive('post');

    reporterWith($client)->flush();
});

it('clears the queue so a second flush does not report twice', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('post')->once()->andReturn(['data' => []]);

    $reporter = reporterWith($client);
    $reporter->report('general.missing', 'en');
    $reporter->flush();
    $reporter->flush();
});

it('never lets a reporting failure break the screen', function () {
    // This runs while a view is rendering; throwing here would blank the page
    // over a missing label.
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('post')->andThrow(new RuntimeException('API down'));

    $reporter = reporterWith($client);
    $reporter->report('general.missing', 'en');

    $reporter->flush();
})->throwsNoExceptions();

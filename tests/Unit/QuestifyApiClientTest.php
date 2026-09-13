<?php

use App\Exceptions\Api\ApiAuthenticationException;
use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiNotFoundException;
use App\Exceptions\Api\ApiServerException;
use App\Exceptions\Api\ApiValidationException;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\AuthResource;
use App\Services\Api\Resources\CategoryApiResource;
use App\Services\Api\Resources\GameplayApiResource;
use App\Services\Api\Resources\QuestApiResource;
use App\Services\Api\Resources\SessionApiResource;
use App\Services\Api\Resources\UserApiResource;
use App\Services\TokenStorage;
use Illuminate\Support\Facades\Http;

/**
 * Every screen reaches the backend through this client, and how it turns an
 * HTTP status into an exception decides what the player sees: a 401 signs them
 * out, a 422 becomes a field message, anything else becomes an error screen.
 * Getting that mapping wrong shows raw jargon to players.
 */
function apiClient(): QuestifyApiClient
{
    return new QuestifyApiClient;
}

// --- Status mapping ---

it('returns the decoded body on success', function () {
    Http::fake(['*/api/v1/quests' => Http::response(['data' => [['id' => 1]]], 200)]);

    expect(apiClient()->get('/quests'))->toBe(['data' => [['id' => 1]]]);
});

it('treats an empty successful body as an empty array', function () {
    Http::fake(['*/api/v1/quests/1' => Http::response(null, 204)]);

    expect(apiClient()->delete('/quests/1'))->toBe([]);
});

it('maps each failure status to the exception the app knows how to show', function (int $status, string $expected) {
    Http::fake(['*/api/v1/quests' => Http::response(['message' => 'Nope'], $status)]);

    apiClient()->get('/quests');
})->with([
    'rejected token' => [401, ApiAuthenticationException::class],
    'missing thing' => [404, ApiNotFoundException::class],
    'invalid input' => [422, ApiValidationException::class],
    'backend broke' => [500, ApiServerException::class],
    'anything else' => [403, ApiException::class],
])->throws(ApiException::class);

it('raises a rejected token as its own kind of failure', function () {
    Http::fake(['*/api/v1/user' => Http::response(['message' => 'Unauthenticated.'], 401)]);

    apiClient()->get('/user');
})->throws(ApiAuthenticationException::class);

it('raises a validation failure carrying the per-field messages', function () {
    Http::fake(['*/api/v1/quests' => Http::response([
        'message' => 'The given data was invalid.',
        'errors' => ['title' => ['The title field is required.']],
    ], 422)]);

    try {
        apiClient()->post('/quests');
        $this->fail('Expected a validation failure.');
    } catch (ApiValidationException $e) {
        expect($e->errors)->toBe(['title' => ['The title field is required.']]);
    }
});

it('keeps the status on a generic failure so callers can branch on it', function () {
    Http::fake(['*/api/v1/quests/1/favourite' => Http::response(['message' => 'Forbidden'], 403)]);

    try {
        apiClient()->post('/quests/1/favourite');
        $this->fail('Expected a failure.');
    } catch (ApiException $e) {
        expect($e->statusCode)->toBe(403)
            ->and($e->getMessage())->toBe('Forbidden');
    }
});

it('falls back to a generic message when the backend sends none', function () {
    Http::fake(['*/api/v1/quests' => Http::response([], 500)]);

    try {
        apiClient()->get('/quests');
        $this->fail('Expected a failure.');
    } catch (ApiException $e) {
        expect($e->getMessage())->toBe('API request failed');
    }
});

// --- Request shape ---

it('sends the stored token so the backend knows who is asking', function () {
    TokenStorage::set('a-token');
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    apiClient()->get('/user');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer a-token'));
});

it('sends no authorisation header for a signed-out visitor', function () {
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    apiClient()->get('/quests');

    Http::assertSent(fn ($request) => ! $request->hasHeader('Authorization'));
});

it('asks the backend for the language the app is showing', function () {
    app()->setLocale('da');
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    apiClient()->get('/quests');

    Http::assertSent(fn ($request) => $request->hasHeader('Accept-Language', 'da'));
});

it('identifies itself so backend logs can tell app traffic apart', function () {
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    apiClient()->get('/quests');

    Http::assertSent(fn ($request) => str_starts_with($request->header('User-Agent')[0], 'Questify/'));
});

it('passes query parameters through on a read', function () {
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    apiClient()->get('/quests', ['difficulty' => 'easy']);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'difficulty=easy'));
});

it('sends a body on a write', function () {
    Http::fake(['*' => Http::response(['data' => []], 201)]);

    apiClient()->post('/quests', ['title' => 'New quest']);

    Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['title'] === 'New quest');
});

it('uses put for an update', function () {
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    apiClient()->put('/quests/1', ['title' => 'Renamed']);

    Http::assertSent(fn ($request) => $request->method() === 'PUT');
});

it('attaches a file as multipart', function () {
    $path = tempnam(sys_get_temp_dir(), 'questify');
    file_put_contents($path, 'not really an image');

    Http::fake(['*' => Http::response(['data' => []], 200)]);

    apiClient()->postMultipart('/quests', ['title' => 'New'], [
        'cover_image' => ['path' => $path, 'name' => 'cover.jpg'],
    ]);

    Http::assertSent(fn ($request) => str_contains($request->header('Content-Type')[0], 'multipart/form-data'));

    unlink($path);
});

// --- Resource accessors ---

it('hands out each api resource', function (string $accessor, string $class) {
    expect(apiClient()->{$accessor}())->toBeInstanceOf($class);
})->with([
    ['auth', AuthResource::class],
    ['quests', QuestApiResource::class],
    ['categories', CategoryApiResource::class],
    ['sessions', SessionApiResource::class],
    ['gameplay', GameplayApiResource::class],
    ['user', UserApiResource::class],
]);

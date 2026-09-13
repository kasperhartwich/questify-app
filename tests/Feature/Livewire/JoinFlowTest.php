<?php

use App\Exceptions\Api\ApiException;
use App\Models\User;
use App\Services\Api\Resources\SessionApiResource;
use Livewire\Livewire;

/**
 * Joining is the only way a player who did not create the quest gets in, and
 * none of it was covered — not the code validation, not the QR parsing, and
 * not the participant id the rest of gameplay depends on.
 */
beforeEach(fn () => mockFullApiClient());

// --- Entering a code ---

it('rejects an empty join code', function () {
    Livewire::test('pages::join.index')
        ->call('joinByCode')
        ->assertHasErrors('joinCode');
});

it('rejects a code that is not six characters', function (string $code) {
    Livewire::test('pages::join.index')
        ->set('joinCode', $code)
        ->call('joinByCode')
        ->assertHasErrors('joinCode');
})->with(['too short' => 'ABC', 'too long' => 'ABCDEFG']);

it('accepts a six character code and asks for a name next', function () {
    Livewire::test('pages::join.index')
        ->set('joinCode', 'abc123')
        ->call('joinByCode')
        ->assertRedirect('/join/ABC123/name');
});

it('treats a signed-in player as no longer a guest', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::join.index')
        ->assertSet('isGuest', false);
});

it('treats a visitor as a guest', function () {
    Livewire::test('pages::join.index')->assertSet('isGuest', true);
});

// --- Scanning a QR code ---

it('reads a bare code out of a scan', function () {
    Livewire::test('pages::join.index')
        ->call('onCodeScanned', 'XK92PL', 'qr', 'join-scan')
        ->assertRedirect('/join/XK92PL/name');
});

it('reads a code out of a scanned address', function () {
    Livewire::test('pages::join.index')
        ->call('onCodeScanned', 'https://questifyapp.net/join/XK92PL/name', 'qr', 'join-scan')
        ->assertRedirect('/join/XK92PL/name');
});

it('upper-cases a lowercase scan', function () {
    Livewire::test('pages::join.index')
        ->call('onCodeScanned', 'xk92pl', 'qr', 'join-scan')
        ->assertRedirect('/join/XK92PL/name');
});

it('tells the player when a scan holds no code', function () {
    Livewire::test('pages::join.index')
        ->call('onCodeScanned', 'not a code at all', 'qr', 'join-scan')
        ->assertHasErrors('joinCode');
});

it('ignores a scan meant for another screen', function () {
    // Scanner results are broadcast app-wide; only our own id is ours to act on.
    Livewire::test('pages::join.index')
        ->call('onCodeScanned', 'XK92PL', 'qr', 'some-other-scan')
        ->assertNoRedirect();
});

it('falls back to the browser scanner when there is no device camera bridge', function () {
    Livewire::test('pages::join.index')
        ->set('isNative', false)
        ->call('scanQr')
        ->assertDispatched('scan-qr-browser');
});

// --- Choosing a display name ---

function displayNameStep(string $code = 'ABC123')
{
    return Livewire::test('pages::join.display-name', ['code' => $code]);
}

it('shows which quest the player is about to join', function () {
    displayNameStep()->assertSee('Copenhagen History Hunt');
});

it('upper-cases a code typed in lowercase', function () {
    displayNameStep('abc123')->assertSet('code', 'ABC123');
});

it('requires a display name', function () {
    displayNameStep()->call('join')->assertHasErrors('displayName');
});

it('rejects a display name that is too short or too long', function (string $name) {
    displayNameStep()
        ->set('displayName', $name)
        ->call('join')
        ->assertHasErrors('displayName');
})->with([
    'one character' => 'K',
    'over thirty characters' => 'Kasper the Extremely Long Named Player',
]);

it('joins the session and lands in the lobby', function () {
    displayNameStep()
        ->set('displayName', 'Kasper Test')
        ->call('join')
        ->assertRedirect('/session/ABC123');
});

it('remembers the participant id the rest of gameplay depends on', function () {
    displayNameStep()->set('displayName', 'Kasper Test')->call('join');

    expect(session('questify_participant_id'))->toBe(55)
        ->and(session('questify_display_name'))->toBe('Kasper Test');
});

it('stays put when the session refuses the join', function () {
    $sessions = Mockery::mock(SessionApiResource::class);
    $sessions->shouldReceive('show')->andReturn(['data' => ['id' => 1, 'session_code' => 'ABC123', 'quest' => ['title' => 'Copenhagen History Hunt']]]);
    $sessions->shouldReceive('join')->andThrow(new ApiException(422, 'Session is full'));
    swapSessions($sessions);

    displayNameStep()
        ->set('displayName', 'Kasper Test')
        ->call('join')
        ->assertNoRedirect();

    expect(session('questify_participant_id'))->toBeNull();
});

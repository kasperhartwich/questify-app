<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

/**
 * Resetting a password is the one flow that hands out account access without a
 * password, so both halves need to hold: only a real address gets a mail, and
 * only a valid token changes anything.
 */

// --- Requesting a reset ---

it('mails a reset to an address we know', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'kasper@example.com']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'kasper@example.com'])
        ->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('refuses an address we do not know', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])
        ->assertStatus(422);

    Notification::assertNothingSent();
});

it('requires an email address to reset', function () {
    $this->postJson('/api/v1/auth/forgot-password', [])->assertStatus(422);
});

it('rejects something that is not an email address', function () {
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'not-an-email'])
        ->assertStatus(422);
});

// --- Using the reset ---

it('changes the password when the token is valid', function () {
    Event::fake([PasswordReset::class]);
    $user = User::factory()->create(['email' => 'kasper@example.com']);
    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'kasper@example.com',
        'password' => 'a-new-secret',
        'password_confirmation' => 'a-new-secret',
    ])->assertOk();

    expect(Hash::check('a-new-secret', $user->fresh()->password))->toBeTrue();
    Event::assertDispatched(PasswordReset::class);
});

it('leaves the password alone when the token is wrong', function () {
    $user = User::factory()->create([
        'email' => 'kasper@example.com',
        'password' => Hash::make('the-old-secret'),
    ]);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'a-made-up-token',
        'email' => 'kasper@example.com',
        'password' => 'a-new-secret',
        'password_confirmation' => 'a-new-secret',
    ])->assertStatus(422);

    expect(Hash::check('the-old-secret', $user->fresh()->password))->toBeTrue();
});

it('refuses a token issued for someone else', function () {
    // Otherwise a valid token would open any account whose address you know.
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    $victim = User::factory()->create([
        'email' => 'victim@example.com',
        'password' => Hash::make('untouched'),
    ]);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => Password::createToken($owner),
        'email' => 'victim@example.com',
        'password' => 'a-new-secret',
        'password_confirmation' => 'a-new-secret',
    ])->assertStatus(422);

    expect(Hash::check('untouched', $victim->fresh()->password))->toBeTrue();
});

it('requires the password to be typed twice the same', function () {
    $user = User::factory()->create(['email' => 'kasper@example.com']);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => Password::createToken($user),
        'email' => 'kasper@example.com',
        'password' => 'a-new-secret',
        'password_confirmation' => 'something-else',
    ])->assertStatus(422);
});

it('will not burn the same token twice', function () {
    $user = User::factory()->create(['email' => 'kasper@example.com']);
    $token = Password::createToken($user);

    $payload = [
        'token' => $token,
        'email' => 'kasper@example.com',
        'password' => 'a-new-secret',
        'password_confirmation' => 'a-new-secret',
    ];

    $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk();
    $this->postJson('/api/v1/auth/reset-password', $payload)->assertStatus(422);
});

it('never tells the player which HTTP status went wrong', function () {
    // Error copy reaches the app's dialogs verbatim.
    $body = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])
        ->json('message');

    expect($body)->not->toContain('422')
        ->and($body)->not->toContain('Unprocessable');
});

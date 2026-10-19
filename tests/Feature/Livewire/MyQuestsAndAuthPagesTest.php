<?php

use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiValidationException;
use App\Models\User;
use App\Services\Api\Resources\AuthResource;
use App\Services\Api\Resources\QuestApiResource;
use App\Services\Api\Resources\UserApiResource;
use Livewire\Livewire;

beforeEach(fn () => mockFullApiClient());

// --- My Quests ---

function myQuests()
{
    return Livewire::actingAs(User::factory()->create())
        ->test('pages::my-quests.played-quests');
}

it('opens on the favourites tab', function () {
    myQuests()->assertSet('tab', 'favourites');
});

it('loads only the data the open tab needs', function () {
    // Each tab hits a different endpoint; loading all three on every switch
    // would triple the requests on a screen players flick between.
    myQuests()
        ->assertSet('createdQuestsData', [])
        ->assertSet('participationsData', []);
});

it('loads the created quests when that tab is opened', function () {
    $component = myQuests()->set('tab', 'created');

    expect($component->get('createdQuestsData'))->toHaveCount(1);
    expect($component->get('createdQuestsData')[0]['title'])->toBe('Copenhagen History Hunt');
});

it('loads participations for the playing and history tabs', function (string $tab) {
    myQuests()->set('tab', $tab)->assertSet('createdQuestsData', []);
})->with(['playing', 'history']);

it('forgets the page position when the tab changes', function () {
    // Carrying a cursor across tabs would page one list by another's offset.
    myQuests()
        ->set('cursor', 'some-cursor')
        ->set('tab', 'created')
        ->assertSet('cursor', '');
});

it('reloads the list after archiving a quest', function () {
    $quests = Mockery::mock(QuestApiResource::class);
    $quests->shouldReceive('destroy')->once()->with(1)->andReturn(['data' => []]);
    $quests->shouldReceive('list', 'show', 'nearby', 'rate')->andReturn(['data' => []]);
    swapApiResource('quests', $quests);

    myQuests()->set('tab', 'created')->call('archiveQuest', 1);
});

it('survives a favourites request the backend refuses', function () {
    // A quest bookmarked then made private used to 403 the whole screen.
    $user = Mockery::mock(UserApiResource::class);
    $user->shouldReceive('favourites')->andThrow(new ApiException(403, 'Forbidden'));
    $user->shouldReceive('quests')->andReturn(['data' => [], 'meta' => ['next_cursor' => null]]);
    $user->shouldReceive('sessions')->andReturn(['data' => []]);
    swapApiResource('user', $user);

    myQuests()->assertSet('favouriteQuestsData', []);
});

it('renders the created quests screen', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::my-quests.created-quests')
        ->assertSee('Copenhagen History Hunt');
});

// --- Forgot password ---

it('requires an email address', function () {
    Livewire::test('pages::auth.forgot-password')
        ->call('sendCode')
        ->assertHasErrors('email');
});

it('rejects something that is not an email address', function () {
    Livewire::test('pages::auth.forgot-password')
        ->set('email', 'not-an-email')
        ->call('sendCode')
        ->assertHasErrors('email');
});

it('asks for the code once it has been sent', function () {
    $auth = Mockery::mock(AuthResource::class);
    $auth->shouldReceive('forgotPassword')->once()->with('kasper@example.com')->andReturn(['data' => []]);
    $auth->shouldReceive('me')->andReturn(['data' => ['id' => 1, 'name' => 'Test', 'locale' => 'en']]);
    swapApiResource('auth', $auth);

    Livewire::test('pages::auth.forgot-password')
        ->set('email', 'kasper@example.com')
        ->call('sendCode')
        ->assertSet('step', 'code');
});

it('shows the backend\'s own complaint against the field it belongs to', function () {
    $auth = Mockery::mock(AuthResource::class);
    $auth->shouldReceive('forgotPassword')->andThrow(
        new ApiValidationException('Validation failed.', ['email' => ['We do not know that address.']])
    );
    $auth->shouldReceive('me')->andReturn(['data' => ['id' => 1, 'name' => 'Test', 'locale' => 'en']]);
    swapApiResource('auth', $auth);

    Livewire::test('pages::auth.forgot-password')
        ->set('email', 'nobody@example.com')
        ->call('sendCode')
        ->assertHasErrors('email')
        ->assertSet('step', 'email');
});

// --- Reset with the emailed code ---

function resetScreenAtCode(AuthResource $auth)
{
    $auth->shouldReceive('forgotPassword')->andReturn(['message' => 'sent']);
    $auth->shouldReceive('me')->andReturn(['data' => ['id' => 1, 'name' => 'Test', 'locale' => 'en']]);
    swapApiResource('auth', $auth);

    return Livewire::test('pages::auth.forgot-password')
        ->set('email', 'kasper@example.com')
        ->call('sendCode')
        ->assertSet('step', 'code');
}

it('wants all six digits before it asks for the new password', function (string $code) {
    resetScreenAtCode(Mockery::mock(AuthResource::class))
        ->set('code', $code)
        ->call('confirmCode')
        ->assertHasErrors('code')
        ->assertSee(__('auth.reset_code_wrong'))
        ->assertSet('step', 'code');
})->with(['', '12345', 'ABC123']);

it('moves on to the new password with six digits', function () {
    resetScreenAtCode(Mockery::mock(AuthResource::class))
        ->set('code', '123456')
        ->call('confirmCode')
        ->assertHasNoErrors()
        ->assertSet('step', 'password')
        ->assertSee(__('auth.new_password_title'));
});

it('checks the new password before sending it', function () {
    $auth = Mockery::mock(AuthResource::class);
    $auth->shouldNotReceive('resetPasswordWithCode');

    resetScreenAtCode($auth)
        ->set('code', '123456')
        ->call('confirmCode')
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('savePassword')
        ->assertHasErrors('password');
});

it('saves the new password and sends the player to log in with it', function () {
    $auth = Mockery::mock(AuthResource::class);
    $auth->shouldReceive('resetPasswordWithCode')->once()
        ->with('kasper@example.com', '123456', 'a-new-secret', 'a-new-secret')
        ->andReturn(['message' => 'ok']);

    resetScreenAtCode($auth)
        ->set('code', '123456')
        ->call('confirmCode')
        ->set('password', 'a-new-secret')
        ->set('password_confirmation', 'a-new-secret')
        ->call('savePassword')
        ->assertHasNoErrors()
        ->assertRedirect('/login?email=kasper%40example.com');

    expect(session('status'))->toBe(__('auth.password_reset_done'));
});

it('goes back to the code in its own words when the backend refuses it', function () {
    $auth = Mockery::mock(AuthResource::class);
    $auth->shouldReceive('resetPasswordWithCode')->andThrow(
        new ApiValidationException('That code is wrong or has expired.', ['code' => ['That code is wrong or has expired.']])
    );

    resetScreenAtCode($auth)
        ->set('code', '123456')
        ->call('confirmCode')
        ->set('password', 'a-new-secret')
        ->set('password_confirmation', 'a-new-secret')
        ->call('savePassword')
        ->assertSet('step', 'code')
        ->assertSet('code', '')
        ->assertSet('password', '')
        ->assertHasErrors('code')
        ->assertSee(__('auth.reset_code_wrong'));
});

it('says to wait instead of passing on the backend\'s rate limit', function () {
    $auth = Mockery::mock(AuthResource::class);
    $auth->shouldReceive('resetPasswordWithCode')->andThrow(new ApiException(429, 'Too Many Attempts.'));

    resetScreenAtCode($auth)
        ->set('code', '123456')
        ->call('confirmCode')
        ->set('password', 'a-new-secret')
        ->set('password_confirmation', 'a-new-secret')
        ->call('savePassword')
        ->assertHasErrors('password')
        ->assertSee(__('auth.reset_too_many'))
        ->assertDontSee('Too Many Attempts.')
        ->assertNotDispatched('api-error');
});

it('sends a new code from the code step', function () {
    $auth = Mockery::mock(AuthResource::class);
    $auth->shouldReceive('forgotPassword')->twice()->with('kasper@example.com')->andReturn(['message' => 'sent']);
    $auth->shouldReceive('me')->andReturn(['data' => ['id' => 1, 'name' => 'Test', 'locale' => 'en']]);
    swapApiResource('auth', $auth);

    Livewire::test('pages::auth.forgot-password')
        ->set('email', 'kasper@example.com')
        ->call('sendCode')
        ->set('code', '12')
        ->call('sendCode')
        ->assertSet('step', 'code')
        ->assertSet('code', '');
});

it('lets the player fix the address from the code step', function () {
    resetScreenAtCode(Mockery::mock(AuthResource::class))
        ->call('changeEmail')
        ->assertSet('step', 'email')
        ->assertSet('email', 'kasper@example.com');
});

it('opens the login screen with the reset address and the confirmation', function () {
    session()->flash('status', __('auth.password_reset_done'));

    Livewire::withQueryParams(['email' => 'kasper@example.com'])
        ->test('pages::auth.login')
        ->assertSet('email', 'kasper@example.com')
        ->assertSee(__('auth.password_reset_done'));
});

/**
 * The link used to sit under the password field, which only appears once an
 * email is typed, so a player who had forgotten their password never saw it.
 */
it('offers the forgotten password reset on a fresh login screen', function () {
    Livewire::test('pages::auth.login')
        ->assertSet('email', '')
        ->assertSee(__('auth.forgot_password'))
        ->assertSeeHtml('href="/forgot-password"');
});

it('carries the typed email over to the reset screen', function () {
    Livewire::test('pages::auth.login')
        ->set('email', 'kasper+quest@example.com')
        ->assertSeeHtml('href="/forgot-password?email=kasper%2Bquest%40example.com"');
});

it('starts the reset screen with the email from the login screen', function () {
    Livewire::withQueryParams(['email' => 'kasper@example.com'])
        ->test('pages::auth.forgot-password')
        ->assertSet('email', 'kasper@example.com');
});

it('ignores an email that is not a plain string', function () {
    Livewire::withQueryParams(['email' => ['kasper@example.com']])
        ->test('pages::auth.forgot-password')
        ->assertSet('email', '');
});

it('words the reset without web terms', function (string $locale) {
    app()->setLocale($locale);

    $keys = [
        'forgot_password', 'forgot_password_title', 'forgot_password_subtitle', 'send_reset_code', 'reset_code_sent',
        'reset_code_wrong', 'reset_code_next', 'reset_too_many', 'new_password_title', 'new_password_subtitle',
        'new_password', 'save_new_password', 'password_reset_done',
    ];

    foreach ($keys as $key) {
        expect(mb_strtolower(__("auth.{$key}")))
            ->not->toMatch('/page|link|url|browser|site|web/');
    }
})->with(['en', 'da']);

// --- Welcome ---

it('renders the welcome screen for a visitor', function () {
    Livewire::test('pages::welcome.index')->assertOk();
});

it('offers both ways in from the welcome screen', function () {
    Livewire::test('pages::welcome.index')
        ->assertSee(__('general.login'))
        ->assertSee(__('general.join_quest'));
});

/**
 * The app opens on the welcome screen at every cold start. Until it sent
 * signed-in players onward, every restart looked like being signed out —
 * the token was fine, but the player was staring at the marketing pitch
 * with a Log In button.
 */
it('sends a signed-in player from the welcome screen into the app', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::welcome.index')
        ->assertRedirect('/discover/list');
});

it('keeps the welcome screen for guests', function () {
    Livewire::test('pages::welcome.index')->assertNoRedirect();
});

it('sends a signed-in player away from the login form', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::auth.login')
        ->assertRedirect('/discover/list');
});

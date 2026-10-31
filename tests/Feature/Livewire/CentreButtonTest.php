<?php

use App\Models\User;
use App\Services\Api\ApiCache;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\GameplayApiResource;
use App\Services\Api\Resources\SessionApiResource;
use App\Services\Api\Resources\UserApiResource;
use App\Services\TokenStorage;
use Livewire\Livewire;

/**
 * The middle button of the bottom bar asks to join a quest, or to continue the
 * one the player is already in.
 */
beforeEach(function () {
    mockFullApiClient();
    TokenStorage::set('test-token');
});

function playerSessions(array $participations): void
{
    $user = Mockery::mock(UserApiResource::class);
    $user->shouldReceive('sessions')->andReturn(['data' => $participations]);

    swapApiResource('user', $user);
}

function participation(string $status, array $overrides = []): array
{
    return array_merge([
        'current_checkpoint_index' => 1,
        'quest_completed_at' => null,
        'session' => [
            'session_code' => 'ABC123',
            'status' => $status,
            'quest' => ['title' => 'Copenhagen History Hunt', 'checkpoints_count' => 4],
        ],
    ], $overrides);
}

it('offers to join a quest when the player is in none', function () {
    playerSessions([]);

    Livewire::test('centre-button')
        ->assertSet('mode', 'join')
        ->assertSet('href', '/join')
        ->assertSee('Join quest')
        ->assertDontSee('Continue');
});

it('offers to join a quest when the only sessions are finished or over', function () {
    playerSessions([
        participation('active', ['quest_completed_at' => '2026-10-01T10:00:00Z']),
        participation('completed'),
    ]);

    Livewire::test('centre-button')->assertSet('mode', 'join');
});

it('continues the quest in play, with its progress and just the word Quest', function () {
    playerSessions([participation('completed'), participation('active')]);

    Livewire::test('centre-button')
        ->assertSet('mode', 'continue')
        ->assertSet('href', route('session.play', 'ABC123', false))
        ->assertSet('progress', 0.25)
        ->assertSee('Quest')
        ->assertDontSee('Continue')
        ->assertDontSee('Copenhagen History Hunt')
        ->assertDontSee('Join quest');
});

it('continues to the lobby while the session is still waiting', function () {
    playerSessions([participation('waiting')]);

    Livewire::test('centre-button')
        ->assertSet('mode', 'continue')
        ->assertSet('href', route('session.lobby', 'ABC123', false));
});

it('reads a paginated list of participations', function () {
    playerSessions(['data' => [participation('active')], 'next_cursor' => null]);

    Livewire::test('centre-button')->assertSet('mode', 'continue');
});

it('shows a plain ring when the quest size is unknown', function () {
    playerSessions([participation('active', ['session' => [
        'session_code' => 'ABC123',
        'status' => 'active',
        'quest' => ['title' => 'Copenhagen History Hunt'],
    ]])]);

    Livewire::test('centre-button')
        ->assertSet('mode', 'continue')
        ->assertSet('progress', null);
});

it('says Join quest and Quest in Danish', function () {
    app()->setLocale('da');
    playerSessions([]);

    Livewire::test('centre-button')->assertSee('Deltag i quest');

    playerSessions([participation('active')]);

    Livewire::test('centre-button')->assertSee('Quest')->assertDontSee('Fortsæt');
});

it('stays on join when the player is signed out', function () {
    TokenStorage::signOut();

    Livewire::test('centre-button')->assertSet('mode', 'join');
});

it('forgets the cached session list when the player joins or answers', function (string $action) {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('post')->andReturn(['data' => []]);

    ApiCache::remember('user:sessions', fn () => ['data' => []]);

    match ($action) {
        'join' => (new SessionApiResource($client))->join('ABC123', 'Kasper'),
        'answer' => (new GameplayApiResource($client))->answer('ABC123', 1, 2, 3),
    };

    $fresh = ApiCache::remember('user:sessions', fn () => ['data' => [participation('active')]]);

    expect($fresh['data'])->toHaveCount(1);
})->with(['join', 'answer']);

it('draws only a big round logo, raised above the bar, with the mode text as its label', function () {
    playerSessions([]);

    Livewire::test('centre-button')
        ->assertSeeHtml('data-centre-mode="join"')
        ->assertSeeHtml('rounded-full bg-amber-400 shadow-[0_0_0_9px_rgba(252,232,189,0.6)')
        ->assertSeeHtml('text-[26px] font-[800] text-forest-600')
        ->assertDontSeeHtml('bg-forest-600 shadow')
        ->assertSeeHtml('aria-label="Join quest"')
        ->assertSeeHtml('h-[84px] w-[84px]')
        ->assertSeeHtml('-mt-9')
        ->assertDontSeeHtml('truncate')
        ->assertDontSeeHtml('<polyline');

    playerSessions([participation('active')]);

    Livewire::test('centre-button')
        ->assertSeeHtml('aria-label="Quest"')
        ->assertSeeHtml('bg-cream')
        ->assertDontSeeHtml('truncate');
});

it('says Start Quest in amber on a quest screen', function () {
    playerSessions([]);

    Livewire::test('centre-button', ['onQuestScreen' => true])
        ->assertSet('mode', 'start')
        ->assertSee('Start Quest')
        ->assertDontSee('Join quest')
        ->assertSeeHtml('data-centre-mode="start"')
        ->assertSeeHtml('aria-label="Start Quest"')
        ->assertSeeHtml('rounded-full bg-amber-400')
        ->assertSeeHtml('bg-forest-600 text-amber-400');
});

it('says Start Quest in Danish on a quest screen', function () {
    app()->setLocale('da');
    playerSessions([]);

    Livewire::test('centre-button', ['onQuestScreen' => true])->assertSee('Start Quest')->assertDontSee('Deltag i quest');
});

it('keeps Continue on a quest screen when the player is in a session', function () {
    playerSessions([participation('active')]);

    Livewire::test('centre-button', ['onQuestScreen' => true])->assertSet('mode', 'continue');
});

it('asks the quest screen to start when tapped', function () {
    playerSessions([]);

    Livewire::test('centre-button', ['onQuestScreen' => true])->call('startQuest')->assertDispatched('centre-start-quest');
});

it('starts the quest with the chosen play mode when the centre button asks', function () {
    $sent = null;
    $sessions = Mockery::mock(SessionApiResource::class);
    $sessions->shouldReceive('create')->andReturnUsing(function ($questId, $mode) use (&$sent) {
        $sent = $mode;

        return ['data' => ['session_code' => 'XYZ789']];
    });
    $sessions->shouldReceive('join')->andReturn(['data' => ['id' => 5]]);
    swapApiResource('sessions', $sessions);

    Livewire::test('pages::discover.quest-detail', ['quest' => 7])
        ->set('playMode', 'competitive_individual')
        ->dispatch('centre-start-quest')
        ->assertRedirect('/session/XYZ789');

    expect($sent)->toBe('competitive_individual');
});

it('shows the amber start button on the quest page and Join quest on Discover, through the layout', function () {
    playerSessions([]);

    $this->actingAs(User::factory()->make(['id' => 1]));

    $this->get('/quests/7')->assertOk()->assertSee('data-centre-mode="start"', false);
    $this->get('/discover/list')->assertOk()->assertSee('data-centre-mode="join"', false);
});

it('draws the progress arc from 12 o\'clock, clockwise, as the share of the quest done', function (int $done, int $total, ?string $dash) {
    playerSessions([participation('active', [
        'current_checkpoint_index' => $done,
        'session' => ['session_code' => 'ABC123', 'status' => 'active', 'quest' => ['checkpoints_count' => $total]],
    ])]);

    $test = Livewire::test('centre-button')
        ->assertSeeHtml('-rotate-90')
        ->assertSeeHtml('text-cream-border')
        ->assertSeeHtml('text-[26px] font-[800] text-forest-600');

    $dash === null
        ? $test->assertDontSeeHtml('stroke-dasharray')
        : $test->assertSeeHtml('stroke-dasharray="'.$dash.' 100"');
})->with([
    'none of five' => [0, 5, null],
    'one of five' => [1, 5, '20'],
    'three of five' => [3, 5, '60'],
    'all five' => [5, 5, '100'],
]);

<?php

use App\Models\User;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\AuthResource;
use App\Services\Api\Resources\CategoryApiResource;
use App\Services\Api\Resources\GameplayApiResource;
use App\Services\Api\Resources\QuestApiResource;
use App\Services\Api\Resources\SessionApiResource;
use App\Services\Api\Resources\UserApiResource;

function mockFullApiClient(): void
{
    $questListItem = [
        'id' => 1,
        'title' => 'Copenhagen History Hunt',
        'description' => 'Explore the historical heart of Copenhagen!',
        'cover_image_url' => null,
        'category' => ['id' => 3, 'name' => 'History', 'icon' => 'castle', 'color' => '#F59E0B'],
        'difficulty' => 'medium',
        'visibility' => 'public',
        'status' => 'published',
        'estimated_duration_minutes' => 45,
        'average_rating' => '5.0',
        'sessions_count' => 2,
        'user' => ['id' => 2, 'name' => 'Bent Hansen'],
        'created_at' => '2026-03-31T07:47:07.000000Z',
    ];

    $questDetail = array_merge($questListItem, [
        'starting_checkpoint' => ['id' => 1, 'title' => 'Nyhavn', 'latitude' => '55.67980000', 'longitude' => '12.59070000'],
        'checkpoint_count' => 3,
        'scoring_points_per_correct' => 100,
        'scoring_speed_bonus_enabled' => true,
        'scoring_wrong_attempt_penalty_enabled' => false,
        'scoring_quest_completion_time_bonus_enabled' => true,
        'wrong_answer_behaviour' => 'retry_free',
        'wrong_answer_penalty_points' => null,
        'wrong_answer_lockout_seconds' => null,
        'ratings_count' => 5,
    ]);

    $mockQuests = Mockery::mock(QuestApiResource::class);
    $mockQuests->shouldReceive('list')->andReturn([
        'data' => [$questListItem],
        'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
        'meta' => ['path' => 'https://questify-admin.test/api/v1/quests', 'per_page' => 15, 'next_cursor' => null, 'prev_cursor' => null],
    ]);
    $mockQuests->shouldReceive('show')->andReturn(['data' => $questDetail]);
    $mockQuests->shouldReceive('nearby')->andReturn(['data' => []]);

    $mockCategories = Mockery::mock(CategoryApiResource::class);
    $mockCategories->shouldReceive('list')->andReturn([
        'data' => [
            ['id' => 1, 'name' => 'General Knowledge', 'slug' => 'general-knowledge', 'icon' => 'brain', 'color' => '#6366F1', 'sort_order' => 0],
            ['id' => 3, 'name' => 'History', 'slug' => 'history', 'icon' => 'castle', 'color' => '#F59E0B', 'sort_order' => 2],
        ],
    ]);

    $mockUser = Mockery::mock(UserApiResource::class);
    $mockUser->shouldReceive('quests')->andReturn(['data' => [$questListItem], 'meta' => ['next_cursor' => null]]);
    $mockUser->shouldReceive('sessions')->andReturn(['data' => []]);
    $mockUser->shouldReceive('favourites')->andReturn(['data' => [], 'meta' => ['next_cursor' => null]]);

    $mockSessions = Mockery::mock(SessionApiResource::class);
    $mockSessions->shouldReceive('show')->andReturn([
        'data' => ['id' => 1, 'session_code' => 'ABC123', 'status' => 'waiting', 'play_mode' => 'competitive_individual', 'quest' => ['id' => 1, 'title' => 'Copenhagen History Hunt'], 'host' => ['id' => 2, 'name' => 'Bent Hansen'], 'participants' => [], 'participants_count' => 0, 'started_at' => null, 'completed_at' => null],
    ]);
    $mockSessions->shouldReceive('dashboard')->andReturn([
        'data' => ['session' => ['id' => 1, 'session_code' => 'ABC123', 'status' => 'active', 'participants_count' => 0], 'participants' => []],
    ]);

    $mockGameplay = Mockery::mock(GameplayApiResource::class);
    $mockGameplay->shouldReceive('leaderboard')->andReturn(['data' => []]);

    $mockAuth = Mockery::mock(AuthResource::class);
    $mockAuth->shouldReceive('me')->andReturn(['data' => ['id' => 1, 'name' => 'Test', 'email' => 'test@example.com', 'avatar_url' => null, 'locale' => 'en']]);

    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('quests')->andReturn($mockQuests);
    $mockClient->shouldReceive('categories')->andReturn($mockCategories);
    $mockClient->shouldReceive('user')->andReturn($mockUser);
    $mockClient->shouldReceive('sessions')->andReturn($mockSessions);
    $mockClient->shouldReceive('gameplay')->andReturn($mockGameplay);
    $mockClient->shouldReceive('auth')->andReturn($mockAuth);

    app()->instance(QuestifyApiClient::class, $mockClient);
}

beforeEach(fn () => mockFullApiClient());

// --- Public Pages ---

it('renders welcome page', function () {
    $this->get('/')->assertOk();
});

it('renders login page', function () {
    $this->get('/login')->assertOk();
});

it('renders register page', function () {
    $this->get('/register')->assertOk();
});

it('renders quest list page with quests', function () {
    $this->get('/discover/list')
        ->assertOk()
        ->assertSee('Copenhagen History Hunt');
});

it('renders quest list with category filters', function () {
    $this->get('/discover/list')
        ->assertOk()
        ->assertSee('History')
        ->assertSee('General Knowledge');
});

it('renders quest detail page', function () {
    $this->get('/quests/1')
        ->assertOk()
        ->assertSee('Copenhagen History Hunt');
});

it('renders quest map page', function () {
    $this->get('/discover/map')->assertOk();
});

// --- Guest Redirects ---

it('redirects guest from create page', function () {
    $this->get('/create')->assertRedirect('/login');
});

it('redirects guest from profile page', function () {
    $this->get('/profile')->assertRedirect('/login');
});

it('redirects guest from my-quests page', function () {
    $this->get('/my-quests')->assertRedirect('/login');
});

it('redirects guest from created-quests page', function () {
    $this->get('/my-quests/created')->assertRedirect('/login');
});

// --- Authenticated Pages ---

it('renders profile page', function () {
    $this->actingAs(User::factory()->create())->get('/profile')->assertOk();
});

it('renders my played quests page', function () {
    $this->actingAs(User::factory()->create())->get('/my-quests')->assertOk();
});

it('renders my created quests page', function () {
    $this->actingAs(User::factory()->create())->get('/my-quests/created')->assertOk();
});

it('renders create quest page', function () {
    $this->actingAs(User::factory()->create())->get('/create')->assertOk();
});

// Session pages require deeper Blade template updates and are tested via the API tests

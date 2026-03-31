<?php

use App\Models\User;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\CategoryApiResource;
use App\Services\Api\Resources\GameplayApiResource;
use App\Services\Api\Resources\QuestApiResource;
use App\Services\Api\Resources\SessionApiResource;
use App\Services\Api\Resources\UserApiResource;

function mockApiClient(): void
{
    $mockQuests = Mockery::mock(QuestApiResource::class);
    $mockQuests->shouldReceive('list')->andReturn([
        'data' => [
            ['id' => 1, 'title' => 'Test Quest', 'description' => 'A test', 'difficulty' => 'medium', 'cover_image_url' => null, 'estimated_duration_minutes' => 60, 'average_rating' => 4.5, 'sessions_count' => 3, 'category' => ['id' => 1, 'name' => 'History'], 'user' => ['id' => 1, 'name' => 'John'], 'created_at' => now()->toISOString()],
        ],
        'meta' => ['next_cursor' => null, 'prev_cursor' => null],
    ]);
    $mockQuests->shouldReceive('show')->andReturn([
        'data' => ['id' => 1, 'title' => 'Test Quest', 'description' => 'A test', 'difficulty' => 'medium', 'visibility' => 'public', 'status' => 'published', 'cover_image_url' => null, 'estimated_duration_minutes' => 60, 'average_rating' => 4.5, 'sessions_count' => 3, 'category' => ['id' => 1, 'name' => 'History', 'slug' => 'history', 'icon' => 'castle', 'sort_order' => 1], 'user' => ['id' => 1, 'name' => 'John'], 'checkpoints' => [], 'ratings_count' => 5, 'created_at' => now()->toISOString()],
    ]);

    $mockCategories = Mockery::mock(CategoryApiResource::class);
    $mockCategories->shouldReceive('list')->andReturn([
        'data' => [
            ['id' => 1, 'name' => 'History', 'slug' => 'history', 'icon' => 'castle', 'sort_order' => 1],
            ['id' => 2, 'name' => 'Nature', 'slug' => 'nature', 'icon' => 'tree', 'sort_order' => 2],
        ],
    ]);

    $mockUser = Mockery::mock(UserApiResource::class);
    $mockUser->shouldReceive('quests')->andReturn(['data' => [], 'meta' => ['next_cursor' => null]]);
    $mockUser->shouldReceive('sessions')->andReturn(['data' => []]);

    $mockSessions = Mockery::mock(SessionApiResource::class);
    $mockSessions->shouldReceive('show')->andReturn(['data' => ['id' => 1, 'session_code' => 'ABC123', 'status' => 'waiting', 'quest' => ['id' => 1, 'title' => 'Test'], 'host' => ['id' => 1, 'name' => 'Host'], 'participants' => [], 'participants_count' => 0]]);

    $mockGameplay = Mockery::mock(GameplayApiResource::class);
    $mockGameplay->shouldReceive('leaderboard')->andReturn(['data' => []]);

    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('quests')->andReturn($mockQuests);
    $mockClient->shouldReceive('categories')->andReturn($mockCategories);
    $mockClient->shouldReceive('user')->andReturn($mockUser);
    $mockClient->shouldReceive('sessions')->andReturn($mockSessions);
    $mockClient->shouldReceive('gameplay')->andReturn($mockGameplay);

    app()->instance(QuestifyApiClient::class, $mockClient);
}

beforeEach(fn () => mockApiClient());

// --- Public Pages ---

it('renders welcome page for guests', function () {
    $this->get('/')->assertOk();
});

it('renders login page for guests', function () {
    $this->get('/login')->assertOk();
});

it('renders register page for guests', function () {
    $this->get('/register')->assertOk();
});

it('renders quest list page', function () {
    $this->get('/discover/list')
        ->assertOk()
        ->assertSee('Test Quest');
});

it('renders quest list with category filter options', function () {
    $this->get('/discover/list')
        ->assertOk()
        ->assertSee('History')
        ->assertSee('Nature');
});

it('renders quest detail page', function () {
    $this->get('/quests/1')
        ->assertOk()
        ->assertSee('Test Quest');
});

it('renders quest map page', function () {
    $this->get('/discover/map')->assertOk();
});

// --- Protected Pages (redirect guests to login) ---

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

it('renders profile page for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertOk();
});

it('renders my played quests page for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/my-quests')->assertOk();
});

it('renders my created quests page for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/my-quests/created')->assertOk();
});

it('renders create quest page for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/create')->assertOk();
});

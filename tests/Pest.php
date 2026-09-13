<?php

use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\AuthResource;
use App\Services\Api\Resources\CategoryApiResource;
use App\Services\Api\Resources\GameplayApiResource;
use App\Services\Api\Resources\QuestApiResource;
use App\Services\Api\Resources\SessionApiResource;
use App\Services\Api\Resources\UserApiResource;
use Database\Seeders\ActivityTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->seed(ActivityTypeSeeder::class);
        cache()->forget('activity_type_map');

        Http::fake([
            '*/api/v1/info' => Http::response([
                'data' => [
                    'auth_methods' => [
                        'email' => true,
                        'phone' => true,
                        'google' => true,
                        'facebook' => true,
                        'apple' => true,
                        'microsoft' => true,
                    ],
                ],
            ], 200),
        ]);
    })
    ->in('Feature', 'Unit', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Default /info payload used to satisfy AppInfoService in Livewire tests that
 * mock QuestifyApiClient. Keeps all auth methods enabled so UI branches render.
 *
 * @return array<string, mixed>
 */
function appInfoStub(): array
{
    return [
        'data' => [
            'auth_methods' => [
                'email' => true,
                'phone' => true,
                'google' => true,
                'facebook' => true,
                'apple' => true,
                'microsoft' => true,
            ],
        ],
    ];
}

/**
 * Bind a fully-mocked QuestifyApiClient into the container so any page can
 * render without the real backend. Shared by Livewire page tests and the
 * browser UI smoke suite.
 */
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
        // Matches QuestResource: the API has never returned a 'user' key for a
        // quest, only 'creator'. The old fixture invented one, so every test
        // that touched the author passed while the app read null on device.
        'creator' => ['type' => 'user', 'id' => 2, 'name' => 'Bent Hansen'],
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
    // Quest 7 is an unpublished draft, so the wizard's edit flow has something
    // it is allowed to open; every other id is the published quest.
    $mockQuests->shouldReceive('show')->andReturnUsing(fn (int $id): array => $id === 7
        ? ['data' => array_merge($questDetail, [
            'id' => 7,
            'title' => 'Half-written Walk',
            'status' => 'draft',
            'checkpoints' => [[
                'id' => 1, 'title' => 'Nyhavn', 'description' => '',
                'latitude' => '55.67980000', 'longitude' => '12.59070000',
                'questions' => [[
                    'id' => 3, 'question_text' => 'Which year?', 'question_type' => 'multiple_choice', 'points' => 10,
                    'answers' => [
                        ['id' => 1, 'answer_text' => '1673', 'is_correct' => true],
                        ['id' => 2, 'answer_text' => '1750', 'is_correct' => false],
                    ],
                ]],
            ]],
        ])]
        : ['data' => $questDetail]);
    // The nearby endpoint adds the starting checkpoint and distances — the map
    // filters out any quest without starting_checkpoint.latitude, so the list
    // shape alone would render zero pins.
    $mockQuests->shouldReceive('nearby')->andReturn(['data' => [array_merge($questListItem, [
        'starting_checkpoint' => ['id' => 1, 'title' => 'Nyhavn', 'latitude' => '55.67980000', 'longitude' => '12.59070000'],
        'checkpoint_count' => 5,
        'visibility' => 'public',
        'distance_to_start_km' => 1.05,
        'distance_to_farthest_km' => 2.3,
    ])]]);
    $mockQuests->shouldReceive('rate')->andReturn(['data' => []]);

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
    $mockSessions->shouldReceive('create')->andReturn(['data' => ['id' => 2, 'session_code' => 'XYZ789']]);
    $mockSessions->shouldReceive('start')->andReturn(['data' => ['status' => 'active']]);
    // 'XYZ789' is an active in-play session (participant 55 belongs to user 1, the
    // first factory user of a test); any other code is a waiting lobby.
    $mockSessions->shouldReceive('show')->andReturnUsing(fn (string $code): array => $code === 'XYZ789'
        ? ['data' => [
            'id' => 2,
            'session_code' => 'XYZ789',
            'status' => 'active',
            'play_mode' => 'competitive_individual',
            'quest' => ['id' => 1, 'title' => 'Copenhagen History Hunt', 'checkpoint_arrival_radius_meters' => 50],
            'host' => ['id' => 2, 'name' => 'Bent Hansen'],
            'participants' => [['id' => 55, 'user_id' => 1, 'display_name' => 'Kasper Test']],
            'participants_count' => 1,
            'checkpoints' => [
                ['id' => 1, 'title' => 'Nyhavn', 'description' => 'The colourful harbour', 'latitude' => '55.67980000', 'longitude' => '12.59070000'],
                ['id' => 2, 'title' => 'Amalienborg', 'description' => 'The royal palace', 'latitude' => '55.68410000', 'longitude' => '12.59300000'],
            ],
            'started_at' => '2026-09-08T10:00:00.000000Z',
            'completed_at' => null,
        ]]
        : ['data' => ['id' => 1, 'session_code' => $code, 'status' => 'waiting', 'play_mode' => 'competitive_individual', 'quest' => ['id' => 1, 'title' => 'Copenhagen History Hunt'], 'host' => ['id' => 2, 'name' => 'Bent Hansen'], 'participants' => [], 'participants_count' => 0, 'started_at' => null, 'completed_at' => null]]);
    $mockSessions->shouldReceive('dashboard')->andReturn([
        'data' => ['session' => ['id' => 1, 'session_code' => 'ABC123', 'status' => 'active', 'participants_count' => 0], 'participants' => []],
    ]);
    $mockSessions->shouldReceive('join')->andReturn([
        'data' => ['id' => 55, 'participant_id' => 55, 'display_name' => 'Kasper Test'],
    ]);

    $mockGameplay = Mockery::mock(GameplayApiResource::class);
    $mockGameplay->shouldReceive('leaderboard')->andReturn([
        'data' => [['id' => 55, 'display_name' => 'Kasper Test', 'total_score' => 100]],
    ]);
    $mockGameplay->shouldReceive('arrived')->andReturn([
        'data' => [
            'id' => 1,
            'title' => 'Nyhavn',
            'questions' => [
                [
                    'id' => 10,
                    'question_type' => 'multiple_choice',
                    'question_text' => 'In which year was Nyhavn completed?',
                    'points' => 100,
                    'answers' => [
                        ['id' => 1, 'answer_text' => '1673'],
                        ['id' => 2, 'answer_text' => '1750'],
                    ],
                ],
            ],
        ],
    ]);
    $mockGameplay->shouldReceive('answer')->andReturn([
        'data' => ['correct' => true, 'score_earned' => 100, 'next' => 'quest_complete'],
    ]);

    $mockAuth = Mockery::mock(AuthResource::class);
    $mockAuth->shouldReceive('me')->andReturn(['data' => ['id' => 1, 'name' => 'Test', 'email' => 'test@example.com', 'avatar_url' => null, 'locale' => 'en']]);
    $mockAuth->shouldReceive('register')->andReturn([
        'data' => [
            'user' => ['id' => 9, 'name' => 'Anna Jensen', 'email' => 'anna@example.com', 'avatar_url' => null, 'locale' => 'en'],
            'token' => 'test-token',
        ],
    ]);
    $mockAuth->shouldReceive('login')->andReturn([
        'data' => [
            'user' => ['id' => 1, 'name' => 'Test', 'email' => 'test@example.com', 'avatar_url' => null, 'locale' => 'en'],
            'token' => 'test-token',
        ],
    ]);

    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('quests')->andReturn($mockQuests);
    $mockClient->shouldReceive('categories')->andReturn($mockCategories);
    $mockClient->shouldReceive('user')->andReturn($mockUser);
    $mockClient->shouldReceive('sessions')->andReturn($mockSessions);
    $mockClient->shouldReceive('gameplay')->andReturn($mockGameplay);
    $mockClient->shouldReceive('auth')->andReturn($mockAuth);
    $mockClient->shouldReceive('get')->with('/info')->andReturn(appInfoStub());

    app()->instance(QuestifyApiClient::class, $mockClient);
}

/**
 * Replace one resource on the mocked API client, keeping the shared fixtures
 * for everything else. Lets a test script a single endpoint without rebuilding
 * the whole client.
 */
function swapApiResource(string $accessor, object $resource): void
{
    $existing = app(QuestifyApiClient::class);

    $client = Mockery::mock(QuestifyApiClient::class);

    foreach (['quests', 'categories', 'user', 'sessions', 'gameplay', 'auth'] as $name) {
        $client->shouldReceive($name)->andReturn(
            $name === $accessor ? $resource : $existing->{$name}()
        );
    }

    $client->shouldReceive('get')->with('/info')->andReturn(appInfoStub());

    app()->instance(QuestifyApiClient::class, $client);
}

function swapSessions(object $resource): void
{
    swapApiResource('sessions', $resource);
}

function swapGameplayResource(object $resource): void
{
    swapApiResource('gameplay', $resource);
}

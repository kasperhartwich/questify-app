<?php

use App\Models\Category;
use App\Models\User;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\CategoryApiResource;
use App\Services\Api\Resources\QuestApiResource;
use Livewire\Livewire;

function mockQuestWizardApiClient(?array $storeResponse = null): void
{
    $mockCategories = Mockery::mock(CategoryApiResource::class);
    $mockCategories->shouldReceive('list')->andReturn([
        'data' => [
            ['id' => 1, 'name' => 'History', 'slug' => 'history', 'icon' => 'castle', 'color' => '#F59E0B', 'sort_order' => 0],
            ['id' => 2, 'name' => 'Science', 'slug' => 'science', 'icon' => 'flask', 'color' => '#6366F1', 'sort_order' => 1],
        ],
    ]);

    $mockQuests = Mockery::mock(QuestApiResource::class);
    $mockQuests->shouldReceive('store')->andReturn($storeResponse ?? [
        'data' => [
            'id' => 42,
            'title' => 'My Test Quest',
            'status' => 'draft',
        ],
    ]);
    $mockQuests->shouldReceive('publish')->andReturn([
        'data' => ['id' => 42, 'status' => 'pending_review'],
    ]);

    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('categories')->andReturn($mockCategories);
    $mockClient->shouldReceive('quests')->andReturn($mockQuests);

    app()->instance(QuestifyApiClient::class, $mockClient);
}

it('renders the quest wizard page for authenticated users', function () {
    mockQuestWizardApiClient();

    $this->actingAs(User::factory()->create())
        ->get('/create')
        ->assertOk();
});

it('redirects guests away from the quest wizard', function () {
    $this->get('/create')->assertRedirect('/login');
});

it('loads categories from the API on mount', function () {
    mockQuestWizardApiClient();

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard');

    $component->assertSet('categories', [1 => 'History', 2 => 'Science']);
});

it('starts on step 1', function () {
    mockQuestWizardApiClient();

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard');

    $component->assertSet('step', 1);
});

it('validates step 1 before advancing', function () {
    mockQuestWizardApiClient();
    Category::factory()->create(['id' => 1]);

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard');

    $component->call('nextStep')
        ->assertHasErrors(['title', 'difficulty']);
});

it('advances from step 1 to step 2 with valid data', function () {
    mockQuestWizardApiClient();
    Category::factory()->create(['id' => 1]);

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('title', 'Copenhagen Walk')
        ->set('categoryId', 1)
        ->set('difficulty', 'medium')
        ->call('nextStep');

    $component->assertSet('step', 2);
});

it('adds and removes checkpoints', function () {
    mockQuestWizardApiClient();

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard');

    // Starts with 1 checkpoint from mount()
    $component->assertCount('checkpoints', 1);

    $component->call('addCheckpoint')
        ->assertCount('checkpoints', 2);

    $component->call('removeCheckpoint', 1)
        ->assertCount('checkpoints', 1);
});

it('does not remove the last checkpoint', function () {
    mockQuestWizardApiClient();

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard');

    $component->call('removeCheckpoint', 0)
        ->assertCount('checkpoints', 1);
});

it('adds questions to a checkpoint', function () {
    mockQuestWizardApiClient();

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard');

    $component->call('addQuestion', 0);

    expect($component->get('questions.0'))->toHaveCount(1);
    expect($component->get('questions.0.0.type'))->toBe('multiple_choice');
    expect($component->get('questions.0.0.answers'))->toHaveCount(2);
});

it('saves a quest via the API and redirects', function () {
    mockQuestWizardApiClient();
    Category::factory()->create(['id' => 1]);

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('title', 'Copenhagen Walk')
        ->set('description', 'A walking tour')
        ->set('categoryId', 1)
        ->set('difficulty', 'easy')
        ->set('visibility', 'public')
        ->set('checkpoints', [
            ['title' => 'Nyhavn', 'description' => '', 'latitude' => 55.6796, 'longitude' => 12.5907],
        ])
        ->set('questions', [
            [
                ['body' => 'What year was it built?', 'type' => 'multiple_choice', 'hint' => '', 'points' => 10, 'answers' => [
                    ['body' => '1673', 'is_correct' => true],
                    ['body' => '1750', 'is_correct' => false],
                ]],
            ],
        ]);

    $component->call('saveAsDraft')
        ->assertRedirect('/quests/42');
});

it('publishes a quest via the API', function () {
    mockQuestWizardApiClient();
    Category::factory()->create(['id' => 1]);

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 5)
        ->set('title', 'Copenhagen Walk')
        ->set('description', 'A walking tour')
        ->set('categoryId', 1)
        ->set('difficulty', 'easy')
        ->set('checkpoints', [
            ['title' => 'Nyhavn', 'description' => '', 'latitude' => 55.6796, 'longitude' => 12.5907],
        ])
        ->set('questions', [
            [
                ['body' => 'What year?', 'type' => 'multiple_choice', 'hint' => '', 'points' => 10, 'answers' => [
                    ['body' => '1673', 'is_correct' => true],
                    ['body' => '1750', 'is_correct' => false],
                ]],
            ],
        ]);

    $component->call('publish')
        ->assertRedirect('/quests/42');
});

it('sends the visibility property instead of hardcoded public', function () {
    $capturedData = null;

    $mockCategories = Mockery::mock(CategoryApiResource::class);
    $mockCategories->shouldReceive('list')->andReturn(['data' => [['id' => 1, 'name' => 'History', 'slug' => 'history', 'icon' => 'castle', 'color' => '#F59E0B', 'sort_order' => 0]]]);

    $mockQuests = Mockery::mock(QuestApiResource::class);
    $mockQuests->shouldReceive('store')
        ->once()
        ->withArgs(function ($data, $coverImagePath) use (&$capturedData) {
            $capturedData = $data;

            return true;
        })
        ->andReturn(['data' => ['id' => 42, 'title' => 'Test', 'status' => 'draft']]);

    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('categories')->andReturn($mockCategories);
    $mockClient->shouldReceive('quests')->andReturn($mockQuests);
    app()->instance(QuestifyApiClient::class, $mockClient);

    Category::factory()->create(['id' => 1]);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('title', 'Private Quest')
        ->set('categoryId', 1)
        ->set('difficulty', 'easy')
        ->set('visibility', 'private')
        ->set('checkpoints', [['title' => 'Stop 1', 'description' => '', 'latitude' => 55.0, 'longitude' => 12.0]])
        ->set('questions', [[['body' => 'Q?', 'type' => 'open_text', 'hint' => '', 'points' => 5, 'answers' => []]]])
        ->call('saveAsDraft');

    expect($capturedData['visibility'])->toBe('private');
});

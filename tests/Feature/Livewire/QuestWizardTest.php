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
    $mockClient->shouldReceive('get')->with('/info')->andReturn(appInfoStub());

    app()->instance(QuestifyApiClient::class, $mockClient);
}

it('renders the quest wizard page for authenticated users', function () {
    mockQuestWizardApiClient();

    $this->actingAs(User::factory()->create())
        ->get('/create')
        ->assertOk();
});

it('redirects guests away from the quest wizard', function () {
    $this->get('/create')->assertRedirect('/');
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

it('does not block step 1 on a missing title', function () {
    mockQuestWizardApiClient();
    Category::factory()->create(['id' => 1]);

    // Authors sketch the route first and write the copy on the review screen,
    // where the title and description become required.
    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard');

    $component->call('nextStep')
        ->assertHasNoErrors();
});

it('validates category and difficulty on the details step', function () {
    mockQuestWizardApiClient();
    Category::factory()->create(['id' => 1]);

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 5)
        ->call('nextStep');

    $component->assertHasErrors(['categoryId', 'difficulty']);
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

    // Starts empty: each map tap appends exactly one checkpoint
    $component->assertCount('checkpoints', 0);

    $component->call('addCheckpoint')
        ->assertCount('checkpoints', 1);

    $component->call('addCheckpoint')
        ->assertCount('checkpoints', 2);

    $component->call('removeCheckpoint', 1)
        ->assertCount('checkpoints', 1);
});

it('does not remove the last checkpoint', function () {
    mockQuestWizardApiClient();

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard');

    $component->call('addCheckpoint')
        ->call('removeCheckpoint', 0)
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
        ->assertRedirect('/quests/42?from=created');
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
        ->assertRedirect('/quests/42?from=created');
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
        ->set('description', 'A short description')
        ->set('checkpoints', [['title' => 'Stop 1', 'description' => '', 'latitude' => 55.0, 'longitude' => 12.0]])
        ->set('questions', [[['body' => 'Q?', 'type' => 'open_text', 'hint' => '', 'points' => 5, 'answers' => []]]])
        ->call('saveAsDraft');

    expect($capturedData['visibility'])->toBe('private');
});

it('disables the step 2 CTA until the quest has a route', function () {
    mockQuestWizardApiClient();

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 2);

    // No checkpoints yet: the CTA is disabled and says why.
    $component->assertSee(__('quests.checkpoints_min'));

    $component->call('addCheckpoint')->call('addCheckpoint');

    $component->assertDontSee(__('quests.checkpoints_min'));
});

it('resumes a draft after navigating away from the wizard', function () {
    mockQuestWizardApiClient();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::create.quest-wizard')
        ->set('title', 'Half-finished Walk')
        ->set('step', 2)
        ->call('addCheckpoint');

    // A fresh component instance is what you get after visiting Profile and
    // coming back — it must pick the draft up again.
    Livewire::actingAs($user)
        ->test('pages::create.quest-wizard')
        ->assertSet('title', 'Half-finished Walk')
        ->assertSet('step', 2)
        ->assertCount('checkpoints', 1);
});

it('starts clean after the draft is discarded', function () {
    mockQuestWizardApiClient();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::create.quest-wizard')
        ->set('title', 'Abandoned')
        ->call('clearDraft');

    Livewire::actingAs($user)
        ->test('pages::create.quest-wizard')
        ->assertSet('title', '')
        ->assertSet('step', 1);
});

it('offers no back link on the first wizard step', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->assertSet('step', 1)
        // The old back arrow pointed at "/", which renders the logged-out
        // welcome screen and reads as being signed out.
        ->assertDontSeeHtml('href="/"');
});

it('names the following step on each wizard CTA', function (int $step, string $expected) {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', $step)
        // Step 4's button said "Review & Publish" while two screens still
        // followed it.
        ->assertSee($expected);
})->with([
    'info → checkpoints' => [1, 'Next: Add Checkpoints'],
    'checkpoints → questions' => [2, 'Next: Add Questions'],
    'settings → details' => [4, 'Next: Details'],
    'details → review' => [5, 'Next: Review'],
]);

it('shows a tappable cover image drop zone', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 5)
        // The bare file input gave no hint that a cover image could be added.
        ->assertSee(__('general.add_cover_image'))
        ->assertSeeHtml('type="file"');
});

it('lets the author fix the name and description on the review step', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 6)
        ->set('title', 'Typo Qeust')
        ->set('categoryId', 1)
        ->set('difficulty', 'medium')
        // Editable, so the last screen is not a dead end for a typo.
        ->assertSeeHtml('wire:model.blur="title"')
        ->assertSeeHtml('wire:model.blur="description"')
        ->set('title', 'Fixed Quest')
        ->assertSet('title', 'Fixed Quest');
});

it('summarises language, category and difficulty before publishing', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 6)
        ->set('categoryId', 1)
        ->set('difficulty', 'hard')
        ->assertSee(__('general.language'))
        ->assertSee('History')
        ->assertSee(__('general.hard'));
});

it('allows leaving step 1 without a name or description', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('step', 2);
});

it('requires a name and description before publishing', function () {
    mockQuestWizardApiClient();
    Category::factory()->create(['id' => 1]);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 6)
        ->set('title', '')
        ->set('description', '')
        ->call('publish')
        ->assertHasErrors(['title', 'description'])
        ->assertNoRedirect();
});

it('blocks step 3 when a choice answer has no text', function () {
    mockQuestWizardApiClient();

    // Without this the backend rejected the save with a raw field path:
    // "checkpoints.0.questions.0.answers.0.answer_text field is required".
    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 3)
        ->set('checkpoints', [['title' => 'Stop', 'description' => '', 'latitude' => 55.0, 'longitude' => 12.0]])
        ->set('questions', [[[
            'body' => 'Question?', 'type' => 'multiple_choice', 'hint' => '', 'points' => 10,
            'answers' => [['body' => 'Right', 'is_correct' => true], ['body' => '', 'is_correct' => false]],
        ]]])
        ->call('nextStep')
        ->assertDispatched('validation-notice', message: __('quests.answers_need_text'))
        ->assertSet('step', 3);
});

it('blocks step 3 when no answer is marked correct', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 3)
        ->set('checkpoints', [['title' => 'Stop', 'description' => '', 'latitude' => 55.0, 'longitude' => 12.0]])
        ->set('questions', [[[
            'body' => 'Question?', 'type' => 'multiple_choice', 'hint' => '', 'points' => 10,
            'answers' => [['body' => 'A', 'is_correct' => false], ['body' => 'B', 'is_correct' => false]],
        ]]])
        ->call('nextStep')
        ->assertDispatched('validation-notice', message: __('quests.answers_need_one_correct'))
        ->assertSet('step', 3);
});

it('lets a complete question through', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 3)
        ->set('checkpoints', [['title' => 'Stop', 'description' => '', 'latitude' => 55.0, 'longitude' => 12.0]])
        ->set('questions', [[[
            'body' => 'Question?', 'type' => 'multiple_choice', 'hint' => '', 'points' => 10,
            'answers' => [['body' => 'A', 'is_correct' => true], ['body' => 'B', 'is_correct' => false]],
        ]]])
        ->call('nextStep')
        ->assertSet('step', 4);
});

it('discards the quest and starts over', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 6)
        ->set('title', 'Abandoned Quest')
        ->set('checkpoints', [['title' => 'Stop', 'description' => '', 'latitude' => 55.0, 'longitude' => 12.0]])
        ->call('discardQuest')
        ->assertSet('step', 1)
        ->assertSet('title', '')
        ->assertCount('checkpoints', 0);
});

it('keeps a saved backend draft when the wizard is cleared', function () {
    mockQuestWizardApiClient();

    // The draft belongs in My Quests — clearing the wizard must not delete it.
    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('draftQuestId', 42)
        ->set('title', 'Half-written')
        ->call('discardQuest')
        ->assertSet('step', 1)
        ->assertSet('title', '')
        ->assertSet('draftQuestId', null);
});

it('loads an existing draft into the wizard for editing', function () {
    mockFullApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard', ['quest' => 7])
        ->assertSet('draftQuestId', 7)
        ->assertSet('title', 'Half-written Walk')
        ->assertCount('checkpoints', 1)
        ->assertSet('step', 1);
});

it('refuses to edit a published quest', function () {
    mockFullApiClient();

    // Quest 1 in the fixture is published — it is live for players.
    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard', ['quest' => 1])
        ->assertRedirect('/quests/1')
        // Nothing from the published quest may leak into the wizard.
        ->assertSet('draftQuestId', null)
        ->assertSet('title', '');
});

it('switches cleanly between two quests', function () {
    mockFullApiClient();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::create.quest-wizard')
        ->set('title', 'Something half-written')
        ->set('draftQuestId', 99);

    // Opening a different quest must not inherit the previous draft's copy.
    Livewire::actingAs($user)
        ->test('pages::create.quest-wizard', ['quest' => 7])
        ->assertSet('draftQuestId', 7)
        ->assertSet('title', 'Half-written Walk');
});

it('lets the author suggest their own category', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 5)
        ->call('chooseCustomCategory')
        ->set('suggestedCategory', 'Street Art')
        ->set('difficulty', 'easy')
        ->call('nextStep')
        // A suggestion is enough to move on — an admin approves it later.
        ->assertHasNoErrors()
        ->assertSet('step', 6)
        ->assertSet('categoryId', '');
});

it('clears a typed category when one of ours is picked', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('suggestedCategory', 'Street Art')
        ->call('chooseCategory', 1)
        ->assertSet('suggestedCategory', '')
        ->assertSet('categoryId', 1);
});

it('still requires a category of some kind', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 5)
        ->set('difficulty', 'easy')
        ->call('nextStep')
        ->assertHasErrors(['categoryId']);
});

it('lets the author move between checkpoints on the questions step', function () {
    mockQuestWizardApiClient();

    // Only the last stop used to be reachable, which made a multi-stop quest
    // impossible to finish editing.
    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 3)
        ->set('checkpoints', [
            ['title' => 'Nyhavn', 'description' => '', 'latitude' => 55.0, 'longitude' => 12.0],
            ['title' => 'Amalienborg', 'description' => '', 'latitude' => 55.1, 'longitude' => 12.1],
        ]);

    $component->assertSee('Nyhavn')->assertSee('Amalienborg')
        ->assertSet('activeCheckpointIndex', 0);

    $component->set('activeCheckpointIndex', 1)
        ->assertSet('activeCheckpointIndex', 1);
});

it('sends no blank answers for a text-answer question', function () {
    $captured = null;

    $mockCategories = Mockery::mock(CategoryApiResource::class);
    $mockCategories->shouldReceive('list')->andReturn(['data' => [['id' => 1, 'name' => 'History', 'slug' => 'history', 'icon' => 'castle', 'color' => '#F59E0B', 'sort_order' => 0]]]);

    $mockQuests = Mockery::mock(QuestApiResource::class);
    $mockQuests->shouldReceive('store')->once()
        ->withArgs(function ($data) use (&$captured) {
            $captured = $data;

            return true;
        })
        ->andReturn(['data' => ['id' => 7, 'status' => 'draft']]);

    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('categories')->andReturn($mockCategories);
    $mockClient->shouldReceive('quests')->andReturn($mockQuests);
    $mockClient->shouldReceive('get')->with('/info')->andReturn(appInfoStub());
    app()->instance(QuestifyApiClient::class, $mockClient);

    // Switching a question to "Text answer" leaves the two blank multiple-choice
    // rows behind. Sending them made the API reject the save with
    // "checkpoints.0.questions.0.answers.0.answer_text field is required".
    // What must survive is the one answer the author typed: the backend grades
    // free text by comparing it against the answer flagged correct, so a text
    // question saved with no answers marks every player wrong.
    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('title', 'Text answer quest')
        ->set('description', 'A quest with a free-text question')
        ->set('categoryId', 1)
        ->set('difficulty', 'easy')
        ->set('checkpoints', [['title' => 'Stop', 'description' => '', 'latitude' => 55.0, 'longitude' => 12.0]])
        ->set('questions', [[[
            'body' => 'What is the name carved above the door?',
            'type' => 'open_text',
            'hint' => 'Look up',
            'points' => 10,
            'answers' => [['body' => 'Anno 1743', 'is_correct' => true], ['body' => '', 'is_correct' => false]],
        ]]])
        ->call('saveAsDraft');

    $answers = $captured['checkpoints'][0]['questions'][0]['answers'] ?? null;

    expect($answers)->toBe([['answer_text' => 'Anno 1743', 'is_correct' => true]]);
});

it('offers every play mode by default and lets them be toggled', function () {
    mockQuestWizardApiClient();

    // A quest declares which ways it can be played; the host picks one when
    // starting a session, so this is a multi-select, not a radio.
    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->assertSet('playModes', ['solo', 'competitive_individual', 'competitive_teams'])
        ->call('togglePlayMode', 'solo')
        ->assertSet('playModes', ['competitive_individual', 'competitive_teams'])
        ->call('togglePlayMode', 'solo')
        ->assertSet('playModes', ['competitive_individual', 'competitive_teams', 'solo']);
});

it('requires at least one play mode', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 4)
        ->set('playModes', [])
        ->call('nextStep')
        ->assertHasErrors(['playModes'])
        ->assertSet('step', 4);
});

/**
 * Both answer types need a visible way to say what counts as correct. The text
 * type had none at all: the screen offered only a hint field, so authors had no
 * place to type the answer and the backend graded every attempt wrong.
 */
function wizardAtQuestions(string $type, array $answers)
{
    mockQuestWizardApiClient();

    return Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('step', 3)
        ->set('checkpoints', [['title' => 'Stop', 'description' => '', 'latitude' => 55.0, 'longitude' => 12.0]])
        ->set('questions', [[[
            'body' => 'Question?',
            'type' => $type,
            'hint' => '',
            'points' => 10,
            'answers' => $answers,
        ]]]);
}

it('offers a field for the expected answer on a text question', function () {
    wizardAtQuestions('open_text', [['body' => 'Anno 1743', 'is_correct' => true]])
        ->assertSee(__('quests.correct_answer'))
        ->assertSeeHtml('questions.0.0.answers.0.body');
});

it('offers the lettered choice rows on a multiple choice question', function () {
    wizardAtQuestions('multiple_choice', [
        ['body' => 'First', 'is_correct' => true],
        ['body' => 'Second', 'is_correct' => false],
    ])
        ->assertSeeHtml('questions.0.0.answers.0.body')
        ->assertSeeHtml('questions.0.0.answers.1.body')
        ->assertDontSee(__('quests.correct_answer'));
});

it('keeps the hint field on both answer types', function (string $type, array $answers) {
    wizardAtQuestions($type, $answers)->assertSeeHtml('questions.0.0.hint');
})->with([
    'text' => ['open_text', [['body' => 'Anno 1743', 'is_correct' => true]]],
    'choice' => ['multiple_choice', [['body' => 'A', 'is_correct' => true], ['body' => 'B', 'is_correct' => false]]],
]);

it('refuses to save a text question with no expected answer', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('title', 'Text quest')
        ->set('description', 'Desc')
        ->set('categoryId', 1)
        ->set('difficulty', 'easy')
        ->set('checkpoints', [['title' => 'Stop', 'description' => '', 'latitude' => 55.0, 'longitude' => 12.0]])
        ->set('questions', [[[
            'body' => 'What is carved above the door?',
            'type' => 'open_text',
            'hint' => '',
            'points' => 10,
            'answers' => [['body' => '  ', 'is_correct' => true]],
        ]]])
        ->set('step', 3)
        ->call('nextStep')
        ->assertDispatched('validation-notice');
});

/**
 * Landing on the new quest left the wizard in history, so the back arrow
 * dropped the author straight back into Create. After saving, back belongs in
 * My Quests → Created, where the quest now lives.
 */
it('sends the author to their created quests when leaving a quest they just saved', function () {
    mockQuestWizardApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('title', 'A saved quest')
        ->set('description', 'Description')
        ->set('categoryId', 1)
        ->set('difficulty', 'easy')
        ->set('checkpoints', [['title' => 'Stop', 'description' => '', 'latitude' => 55.0, 'longitude' => 12.0]])
        ->set('questions', [[[
            'body' => 'Question?',
            'type' => 'open_text',
            'hint' => '',
            'points' => 10,
            'answers' => [['body' => 'Answer', 'is_correct' => true]],
        ]]])
        ->call('saveAsDraft')
        ->assertRedirect('/quests/42?from=created');
});

/**
 * Questions are keyed by checkpoint index, so moving a stop has to carry its
 * questions along. Reordering the checkpoint array alone would silently
 * reattach every question to whichever stop landed on that index.
 */
function wizardWithThreeCheckpoints()
{
    mockQuestWizardApiClient();

    return Livewire::actingAs(User::factory()->create())
        ->test('pages::create.quest-wizard')
        ->set('checkpoints', [
            ['title' => 'First', 'description' => '', 'latitude' => 55.1, 'longitude' => 12.1],
            ['title' => 'Second', 'description' => '', 'latitude' => 55.2, 'longitude' => 12.2],
            ['title' => 'Third', 'description' => '', 'latitude' => 55.3, 'longitude' => 12.3],
        ])
        ->set('questions', [
            [['body' => 'Q for First', 'type' => 'open_text', 'hint' => '', 'points' => 10, 'answers' => [['body' => 'a', 'is_correct' => true]]]],
            [['body' => 'Q for Second', 'type' => 'open_text', 'hint' => '', 'points' => 10, 'answers' => [['body' => 'b', 'is_correct' => true]]]],
            [['body' => 'Q for Third', 'type' => 'open_text', 'hint' => '', 'points' => 10, 'answers' => [['body' => 'c', 'is_correct' => true]]]],
        ]);
}

it('moves a checkpoint down the list', function () {
    $component = wizardWithThreeCheckpoints()->call('moveCheckpoint', 0, 2);

    expect(array_column($component->get('checkpoints'), 'title'))
        ->toBe(['Second', 'Third', 'First']);
});

it('moves a checkpoint up the list', function () {
    $component = wizardWithThreeCheckpoints()->call('moveCheckpoint', 2, 0);

    expect(array_column($component->get('checkpoints'), 'title'))
        ->toBe(['Third', 'First', 'Second']);
});

it('carries each checkpoint\'s questions with it when the order changes', function () {
    $component = wizardWithThreeCheckpoints()->call('moveCheckpoint', 0, 2);

    $bodies = collect($component->get('questions'))->map(fn ($qs) => $qs[0]['body'])->all();

    expect($bodies)->toBe(['Q for Second', 'Q for Third', 'Q for First']);
});

it('ignores a move that goes nowhere or off the ends', function (int $from, int $to) {
    $component = wizardWithThreeCheckpoints()->call('moveCheckpoint', $from, $to);

    expect(array_column($component->get('checkpoints'), 'title'))
        ->toBe(['First', 'Second', 'Third']);
})->with([
    'same position' => [1, 1],
    'below the list' => [0, 9],
    'above the list' => [1, -1],
    'source does not exist' => [7, 0],
]);

it('keeps the active checkpoint pointing at the stop the author was editing', function () {
    $component = wizardWithThreeCheckpoints()
        ->set('activeCheckpointIndex', 0)
        ->call('moveCheckpoint', 0, 2);

    expect($component->get('activeCheckpointIndex'))->toBe(2);
});

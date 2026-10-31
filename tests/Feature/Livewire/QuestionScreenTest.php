<?php

use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiValidationException;
use App\Models\User;
use App\Services\Api\Resources\GameplayApiResource;
use Livewire\Livewire;

/**
 * Answering is where a quest is won or lost, and none of it was covered: the
 * proximity bounce, the double-submission guard, the four wrong-answer
 * behaviours from spec 5.15, and where the player lands afterwards.
 */
beforeEach(function () {
    mockFullApiClient();

    // The navigation screen records the last fix; /arrived is rejected without it.
    session()->put('questify_participant_id', 55);
    session()->put('questify_player_lat', 55.67980);
    session()->put('questify_player_lng', 12.59070);
});

/**
 * Swap in a gameplay resource whose answer() returns exactly what this test
 * needs. arrived() keeps the shared fixture unless overridden.
 *
 * @param  array<string, mixed>|null  $answer
 */
function fakeGameplay(?array $answer = null, mixed $arrived = null): void
{
    $gameplay = Mockery::mock(GameplayApiResource::class);

    $gameplay->shouldReceive('leaderboard')->andReturn([
        'data' => [['id' => 55, 'display_name' => 'Kasper Test', 'total_score' => 100]],
    ]);

    $gameplay->shouldReceive('arrived')->andReturn($arrived ?? ['data' => [
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
            [
                'id' => 11,
                'question_type' => 'open_text',
                'question_text' => 'What is carved above the door?',
                'points' => 50,
                'answers' => [],
            ],
        ],
    ]]);

    $gameplay->shouldReceive('answer')->andReturn($answer ?? [
        'data' => ['correct' => true, 'score_earned' => 100, 'next' => 'question'],
    ]);

    swapGameplayResource($gameplay);
}

function questionScreen()
{
    return Livewire::actingAs(User::factory()->create())
        ->test('pages::session.question-screen', ['code' => 'XYZ789', 'checkpoint' => 1]);
}

// --- Loading ---

it('loads the checkpoint questions on arrival', function () {
    fakeGameplay();

    questionScreen()
        ->assertSet('totalQuestions', 2)
        ->assertSee('In which year was Nyhavn completed?');
});

it('sends the player back to navigation when the server rejects the arrival', function () {
    // The server re-checks proximity, so a player who is not actually there
    // must not reach the questions.
    $gameplay = Mockery::mock(GameplayApiResource::class);
    $gameplay->shouldReceive('arrived')->andThrow(new ApiException(422, 'Too far away'));
    $gameplay->shouldReceive('leaderboard')->andReturn(['data' => []]);
    swapGameplayResource($gameplay);

    Livewire::test('pages::session.question-screen', ['code' => 'XYZ789', 'checkpoint' => 1])
        ->assertRedirect('/session/XYZ789/play');
});

// --- Submitting ---

it('does nothing when no answer has been picked', function () {
    fakeGameplay();

    questionScreen()
        ->call('submitAnswer')
        ->assertSet('showFeedback', false);
});

it('awards points for a correct answer', function () {
    fakeGameplay();

    questionScreen()
        ->set('selectedAnswerId', 1)
        ->call('submitAnswer')
        ->assertSet('lastAnswerCorrect', true)
        ->assertSet('lastPointsEarned', 100)
        ->assertSet('showFeedback', true);
});

it('names the speed bonus inside the points earned', function () {
    fakeGameplay(['data' => ['correct' => true, 'score_earned' => 105, 'speed_bonus' => 5, 'next' => 'question']]);

    questionScreen()
        ->set('selectedAnswerId', 1)
        ->call('submitAnswer')
        ->assertSet('lastSpeedBonus', 5)
        ->assertSee('+105')
        ->assertSee(__('sessions.speed_bonus_included', ['points' => 5]));
});

it('shows the base points the server sent on the question', function () {
    fakeGameplay();

    questionScreen()->assertSee('100 '.__('quests.points'));
});

it('refuses a second submission while feedback is on screen', function () {
    fakeGameplay();

    $component = questionScreen()
        ->set('selectedAnswerId', 1)
        ->call('submitAnswer')
        ->set('lastPointsEarned', 0)
        ->call('submitAnswer');

    // The guard returned early, so the score the second call would have
    // written was never applied.
    expect($component->get('lastPointsEarned'))->toBe(0);
});

it('moves a correctly answered question out of the queue', function () {
    fakeGameplay();

    questionScreen()
        ->set('selectedAnswerId', 1)
        ->call('submitAnswer')
        ->assertSet('answeredQuestionIds', [10]);
});

// --- Wrong-answer behaviours (spec 5.15) ---

it('shows an icon, not an emoji, on a correct answer', function () {
    fakeGameplay();

    questionScreen()
        ->set('selectedAnswerId', 1)
        ->call('submitAnswer')
        ->assertSeeHtml('data-testid="answer-correct-icon"')
        ->assertDontSee('✅');
});

it('shows an icon, not an emoji, on a wrong answer', function () {
    fakeGameplay(['data' => ['correct' => false, 'behaviour' => 'retry_free']]);

    questionScreen()
        ->set('selectedAnswerId', 2)
        ->call('submitAnswer')
        ->assertSeeHtml('data-testid="answer-wrong-icon"')
        ->assertDontSee('❌');
});

it('lets the player retry for free', function () {
    fakeGameplay(['data' => ['correct' => false, 'behaviour' => 'retry_free']]);

    questionScreen()
        ->set('selectedAnswerId', 2)
        ->call('submitAnswer')
        ->assertSet('lastAnswerCorrect', false)
        ->assertSet('wrongBehaviour', 'retry_free')
        ->assertSet('penaltyPoints', null);
});

it('names the penalty when a wrong answer costs points', function () {
    fakeGameplay(['data' => ['correct' => false, 'behaviour' => 'retry_penalty', 'penalty' => 25]]);

    questionScreen()
        ->set('selectedAnswerId', 2)
        ->call('submitAnswer')
        ->assertSet('penaltyPoints', 25);
});

it('counts down a lockout', function () {
    fakeGameplay(['data' => [
        'correct' => false,
        'behaviour' => 'lockout',
        'locked_until' => now()->addSeconds(30)->toIso8601String(),
    ]]);

    $component = questionScreen()->set('selectedAnswerId', 2)->call('submitAnswer');

    expect($component->get('lockoutRemaining'))->toBeGreaterThan(25)
        ->and($component->get('lockoutRemaining'))->toBeLessThanOrEqual(30);
});

it('counts down from the server retry_after, not the device clock', function () {
    fakeGameplay(['data' => [
        'correct' => false,
        'behaviour' => 'lockout',
        'retry_after' => 30,
        'locked_until' => now()->subMinute()->toIso8601String(),
    ]]);

    questionScreen()->set('selectedAnswerId', 2)->call('submitAnswer')
        ->assertSet('lockoutRemaining', 30)
        ->assertSet('lockoutTotal', 30);
});

it('treats a lockout that has already elapsed as over', function () {
    fakeGameplay(['data' => [
        'correct' => false,
        'behaviour' => 'lockout',
        'locked_until' => now()->subMinute()->toIso8601String(),
    ]]);

    questionScreen()->set('selectedAnswerId', 2)->call('submitAnswer')
        ->assertSet('lockoutRemaining', 0);
});

it('shows the countdown ring and disables the answers while locked out', function () {
    fakeGameplay(['data' => [
        'correct' => false,
        'behaviour' => 'lockout',
        'locked_until' => now()->addSeconds(30)->toIso8601String(),
    ]]);

    questionScreen()
        ->set('selectedAnswerId', 2)
        ->call('submitAnswer')
        ->assertSeeHtml('data-testid="lockout-ring"')
        ->assertSee(__('sessions.wait_to_answer'))
        ->assertDontSee(__('sessions.try_again'));
});

it('resumes a lockout the server is still running when the player comes back', function () {
    fakeGameplay(null, ['meta' => ['answer_locked_until' => now()->addSeconds(20)->toIso8601String(), 'retry_after' => 20], 'data' => [
        'id' => 1,
        'title' => 'Nyhavn',
        'questions' => [[
            'id' => 10,
            'question_type' => 'multiple_choice',
            'question_text' => 'In which year was Nyhavn completed?',
            'points' => 100,
            'answers' => [['id' => 1, 'answer_text' => '1673'], ['id' => 2, 'answer_text' => '1750']],
        ]],
    ]]);

    $component = questionScreen()
        ->assertSet('lockoutRemaining', 20)
        ->assertSeeHtml('data-testid="lockout-ring"');

    // Picking an answer and submitting does nothing while locked.
    $component->set('selectedAnswerId', 1)->call('submitAnswer')->assertSet('showFeedback', false);
});

it('starts the countdown when the server refuses an answer as locked', function () {
    $gameplay = Mockery::mock(GameplayApiResource::class);
    $gameplay->shouldReceive('leaderboard')->andReturn(['data' => []]);
    $gameplay->shouldReceive('arrived')->andReturn(['data' => [
        'id' => 1,
        'title' => 'Nyhavn',
        'questions' => [[
            'id' => 10,
            'question_type' => 'multiple_choice',
            'question_text' => 'In which year was Nyhavn completed?',
            'points' => 100,
            'answers' => [['id' => 1, 'answer_text' => '1673'], ['id' => 2, 'answer_text' => '1750']],
        ]],
    ]]);
    $gameplay->shouldReceive('answer')->andThrow(new ApiException(423, 'Wait', ['code' => 'answer_locked', 'retry_after' => 12]));
    swapGameplayResource($gameplay);

    questionScreen()
        ->set('selectedAnswerId', 1)
        ->call('submitAnswer')
        ->assertSet('lockoutRemaining', 12)
        ->assertSet('showFeedback', false)
        ->assertSeeHtml('data-testid="lockout-ring"');
});

it('lets the player answer again once the lockout is over', function () {
    fakeGameplay(['data' => [
        'correct' => false,
        'behaviour' => 'lockout',
        'locked_until' => now()->addSeconds(30)->toIso8601String(),
    ]]);

    questionScreen()
        ->set('selectedAnswerId', 2)
        ->call('submitAnswer')
        ->call('tryAgain')
        ->assertSet('lockoutRemaining', 0)
        ->assertSet('showFeedback', false)
        ->assertDontSeeHtml('data-testid="lockout-ring"');
});

it('reveals the hint after three strikes', function () {
    fakeGameplay(['data' => [
        'correct' => false,
        'behaviour' => 'three_strikes_hint',
        'hint' => 'Look above the door',
    ]]);

    questionScreen()
        ->set('selectedAnswerId', 2)
        ->call('submitAnswer')
        ->assertSet('revealedHint', 'Look above the door');
});

it('keeps the revealed hint visible while the player retries', function () {
    fakeGameplay(['data' => [
        'correct' => false,
        'behaviour' => 'three_strikes_hint',
        'hint' => 'Look above the door',
    ]]);

    questionScreen()
        ->set('selectedAnswerId', 2)
        ->call('submitAnswer')
        ->call('tryAgain')
        ->assertSet('revealedHint', 'Look above the door')
        ->assertSet('showFeedback', false)
        ->assertSet('selectedAnswerId', null);
});

// --- Free text ---

it('submits a typed answer rather than an answer id', function () {
    fakeGameplay();

    questionScreen()
        // Answer the multiple-choice question first so the text one is current.
        ->set('answeredQuestionIds', [10])
        ->set('openEndedAnswer', '  Anno 1743 ')
        ->call('submitAnswer')
        ->assertSet('lastAnswerCorrect', true);
});

// --- Where the player goes next ---

it('sends the player to the results when the quest is finished', function () {
    fakeGameplay(['data' => ['correct' => true, 'score_earned' => 100, 'next' => 'quest_complete']]);

    questionScreen()
        ->set('selectedAnswerId', 1)
        ->call('submitAnswer')
        ->assertSet('questComplete', true)
        ->call('nextQuestion')
        ->assertRedirect('/session/XYZ789/complete');
});

it('sends the player back to navigation and advances the stop when a checkpoint is done', function () {
    fakeGameplay(['data' => ['correct' => true, 'score_earned' => 100, 'next' => 'checkpoint_complete']]);

    session()->put('questify_checkpoint_index.XYZ789', 0);

    questionScreen()
        ->set('selectedAnswerId', 1)
        ->call('submitAnswer')
        ->assertSet('checkpointComplete', true)
        ->call('nextQuestion')
        ->assertRedirect('/session/XYZ789/play');

    expect(session('questify_checkpoint_index.XYZ789'))->toBe(1);
});

it('clears the previous answer before the next question', function () {
    fakeGameplay();

    questionScreen()
        ->set('selectedAnswerId', 1)
        ->call('submitAnswer')
        ->call('nextQuestion')
        ->assertSet('selectedAnswerId', null)
        ->assertSet('openEndedAnswer', '')
        ->assertSet('showFeedback', false);
});

it('sends a player without a participant back to the play screen without calling the API', function () {
    session()->forget('questify_participant_id');
    $gameplay = Mockery::mock(GameplayApiResource::class);
    $gameplay->shouldNotReceive('arrived');
    swapGameplayResource($gameplay);

    Livewire::test('pages::session.question-screen', ['code' => 'XYZ789', 'checkpoint' => 1])
        ->assertRedirect('/session/XYZ789/play');
});

it('tells the player in plain words when the API no longer knows the participant', function () {
    fakeGameplay();
    $component = questionScreen();

    swapGameplayResource(tap(Mockery::mock(GameplayApiResource::class), function ($gameplay) {
        $gameplay->shouldReceive('answer')->andThrow(
            new ApiValidationException('Invalid.', ['participant_id' => ['The selected participant id is invalid.']]),
        );
    }));

    $component->set('selectedAnswerId', 1)
        ->call('submitAnswer')
        ->assertDispatched('api-error', message: __('errors.participant_unknown'))
        ->assertHasNoErrors();

    expect(session('questify_participant_id'))->toBeNull();
});

it('finds a signed-in player again when the participant id was lost', function () {
    session()->forget('questify_participant_id');
    fakeGameplay();

    questionScreen()->assertNoRedirect();

    expect(session('questify_participant_id'))->toBe(55);
});

// --- The question screen design (QU-50) ---

it('labels the answers A and B and the question type in a pill', function () {
    fakeGameplay();

    questionScreen()
        ->assertSeeHtml('data-testid="question-type-pill"')
        ->assertSee(__('sessions.type_multiple_choice'))
        ->assertSeeHtmlInOrder(['data-testid="answer-badge"', 'A', 'data-testid="answer-badge"', 'B']);
});

it('shows check and cross rows for a true or false question', function () {
    fakeGameplay(arrived: ['data' => [
        'id' => 1,
        'title' => 'Nyhavn',
        'questions' => [[
            'id' => 12,
            'question_type' => 'true_false',
            'question_text' => 'Bees make honey.',
            'points' => 100,
            'answers' => [['id' => 5, 'answer_text' => 'True'], ['id' => 6, 'answer_text' => 'False']],
        ]],
    ]]);

    questionScreen()
        ->assertSee(__('sessions.type_true_false'))
        ->assertSee('True')
        ->assertSee('False')
        ->assertDontSeeHtml('>A<')
        ->assertDontSeeHtml('>\n                                    A');
});

it('shows Not quite and +0 points after a free wrong answer', function () {
    fakeGameplay(['data' => ['correct' => false, 'behaviour' => 'retry_free']]);

    questionScreen()
        ->set('selectedAnswerId', 2)
        ->call('submitAnswer')
        ->assertSeeHtml('data-testid="answer-result-wrong"')
        ->assertSee(__('sessions.not_quite'))
        ->assertSee('+0 '.__('quests.points'));
});

it('shows the penalty instead of +0 points when a wrong answer costs points', function () {
    fakeGameplay(['data' => ['correct' => false, 'behaviour' => 'retry_penalty', 'penalty' => 25]]);

    questionScreen()
        ->set('selectedAnswerId', 2)
        ->call('submitAnswer')
        ->assertSee(__('sessions.wrong_penalty', ['points' => 25]))
        ->assertDontSee('+0 '.__('quests.points'));
});

it('shows the correct result block with the points earned', function () {
    fakeGameplay();

    questionScreen()
        ->set('selectedAnswerId', 1)
        ->call('submitAnswer')
        ->assertSeeHtml('data-testid="answer-result-correct"')
        ->assertSee('+100 '.__('quests.points'));
});

it('picks the true or false icon from the answer text, not its position', function () {
    fakeGameplay(arrived: ['data' => [
        'id' => 1,
        'title' => 'Nyhavn',
        'questions' => [[
            'id' => 12,
            'question_type' => 'true_false',
            'question_text' => 'Bees make honey.',
            'points' => 100,
            'answers' => [['id' => 6, 'answer_text' => 'False'], ['id' => 5, 'answer_text' => 'True']],
        ]],
    ]]);

    $html = questionScreen()->html();

    // The check mark path belongs to the True row, whichever order the rows arrive in.
    preg_match_all('/<button.*?<\/button>/s', $html, $rows);
    $falseRow = collect($rows[0])->first(fn ($row) => str_contains($row, 'False'));
    $trueRow = collect($rows[0])->first(fn ($row) => str_contains($row, 'True'));

    expect($trueRow)->toContain('M5 12.5l4.5 4.5L19 7.5')
        ->and($falseRow)->toContain('M7 7l10 10M17 7L7 17');
});

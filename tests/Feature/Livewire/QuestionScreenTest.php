<?php

use App\Exceptions\Api\ApiException;
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

    questionScreen()->assertRedirect('/session/XYZ789/play');
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

it('treats a lockout that has already elapsed as over', function () {
    fakeGameplay(['data' => [
        'correct' => false,
        'behaviour' => 'lockout',
        'locked_until' => now()->subMinute()->toIso8601String(),
    ]]);

    questionScreen()->set('selectedAnswerId', 2)->call('submitAnswer')
        ->assertSet('lockoutRemaining', 0);
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

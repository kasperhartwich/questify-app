<?php

use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('logs activity for mapped routes on success', function () {
    // user.profile.update is in ROUTE_TYPE_MAP and NOT in SKIP_ROUTES
    $this->actingAs($this->user)->putJson('/api/v1/user/profile', [
        'name' => 'Updated Name',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $this->user->id,
    ]);
});

it('skips logging for routes in SKIP_ROUTES', function () {
    // quests.store is in SKIP_ROUTES — controller handles logging itself
    $quest = Quest::factory()->create([
        'creator_id' => $this->user->id,
        'status' => QuestStatus::Draft,
    ]);

    // Count logs before
    $countBefore = $this->user->activityLogs()->count();

    $this->actingAs($this->user)->postJson('/api/v1/quests', [
        'title' => 'Test Quest',
        'description' => 'A test quest',
        'category_id' => $quest->category_id,
        'difficulty' => 'easy',
        'visibility' => 'public',
        'checkpoints' => [
            [
                'title' => 'Start',
                'latitude' => 55.6761,
                'longitude' => 12.5683,
                'sort_order' => 0,
                'questions' => [
                    [
                        'body' => 'Test?',
                        'type' => 'multiple_choice',
                        'answers' => [
                            ['body' => 'Yes', 'is_correct' => true],
                            ['body' => 'No', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    // The middleware should have skipped; controller may or may not log (that's tested elsewhere)
    // We verify the middleware did NOT double-log by checking only one log was created (from controller)
    $logCount = $this->user->activityLogs()->where('activity_type_id', function ($q) {
        $q->select('id')->from('activity_types')->where('key', 'quest_created');
    })->count();

    expect($logCount)->toBeLessThanOrEqual(1);
});

it('does not log for unauthenticated requests', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'nonexistent@test.com',
        'password' => 'wrong',
    ]);

    $this->assertDatabaseCount('activity_logs', 0);
});

it('does not log for failed responses', function () {
    // Attempt login with wrong password — returns 401/422
    $this->actingAs($this->user)->putJson('/api/v1/user/profile', [
        'locale' => 'invalid',
    ]);

    // The profile update should fail validation (422), so no log
    $this->assertDatabaseMissing('activity_logs', [
        'user_id' => $this->user->id,
    ]);
});

it('does not log for unmapped routes', function () {
    // GET /api/v1/categories is not in ROUTE_TYPE_MAP
    $this->actingAs($this->user)->getJson('/api/v1/categories');

    $this->assertDatabaseMissing('activity_logs', [
        'user_id' => $this->user->id,
    ]);
});

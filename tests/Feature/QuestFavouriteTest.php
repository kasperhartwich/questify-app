<?php

use App\Enums\QuestStatus;
use App\Enums\QuestVisibility;
use App\Models\Quest;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('toggles a quest as favourite', function () {
    $quest = Quest::factory()->create();

    $response = $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/favourite");

    $response->assertOk()
        ->assertJsonPath('data.is_favourited', true);

    $this->assertDatabaseHas('quest_favourites', [
        'user_id' => $this->user->id,
        'quest_id' => $quest->id,
    ]);
});

it('removes a quest from favourites on second toggle', function () {
    $quest = Quest::factory()->create();
    $this->user->favouriteQuests()->attach($quest);

    $response = $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/favourite");

    $response->assertOk()
        ->assertJsonPath('data.is_favourited', false);

    $this->assertDatabaseMissing('quest_favourites', [
        'user_id' => $this->user->id,
        'quest_id' => $quest->id,
    ]);
});

it('lists favourite quests', function () {
    $quests = Quest::factory()->count(3)->create([
        'status' => QuestStatus::Published,
        'visibility' => QuestVisibility::Public,
    ]);
    $this->user->favouriteQuests()->attach($quests->pluck('id'));

    Quest::factory()->create();

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/favourites');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('requires authentication to toggle favourite', function () {
    $quest = Quest::factory()->create();

    $response = $this->postJson("/api/v1/quests/{$quest->id}/favourite");

    $response->assertStatus(401);
});

it('requires authentication to list favourites', function () {
    $response = $this->getJson('/api/v1/user/favourites');

    $response->assertStatus(401);
});

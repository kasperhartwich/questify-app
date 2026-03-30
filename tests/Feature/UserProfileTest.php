<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('updates profile name', function () {
    $response = $this->actingAs($this->user)->putJson('/api/v1/user/profile', [
        'name' => 'New Name',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New Name');
});

it('updates locale', function () {
    $response = $this->actingAs($this->user)->putJson('/api/v1/user/profile', [
        'locale' => 'da',
    ]);

    $response->assertOk();
    expect($this->user->fresh()->locale)->toBe('da');
});

it('rejects invalid locale', function () {
    $response = $this->actingAs($this->user)->putJson('/api/v1/user/profile', [
        'locale' => 'fr',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('locale');
});

it('deletes account (GDPR)', function () {
    $this->user->createToken('test');

    $response = $this->actingAs($this->user)->deleteJson('/api/v1/user');

    $response->assertOk();
    $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $this->user->id,
        'tokenable_type' => User::class,
    ]);
});

it('requires authentication for profile operations', function () {
    $this->putJson('/api/v1/user/profile', ['name' => 'Test'])->assertStatus(401);
    $this->deleteJson('/api/v1/user')->assertStatus(401);
});

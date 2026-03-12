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

it('updates profile email', function () {
    $response = $this->actingAs($this->user)->putJson('/api/v1/user/profile', [
        'email' => 'newemail@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.email', 'newemail@example.com');
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

it('rejects duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->actingAs($this->user)->putJson('/api/v1/user/profile', [
        'email' => 'taken@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('allows updating to own email', function () {
    $response = $this->actingAs($this->user)->putJson('/api/v1/user/profile', [
        'email' => $this->user->email,
    ]);

    $response->assertOk();
});

it('deletes account (GDPR)', function () {
    // Create a token first
    $this->user->createToken('test');

    $response = $this->actingAs($this->user)->deleteJson('/api/v1/user/profile');

    $response->assertOk();
    $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $this->user->id,
        'tokenable_type' => User::class,
    ]);
});

it('requires authentication for profile operations', function () {
    $this->putJson('/api/v1/user/profile', ['name' => 'Test'])->assertStatus(401);
    $this->deleteJson('/api/v1/user/profile')->assertStatus(401);
});

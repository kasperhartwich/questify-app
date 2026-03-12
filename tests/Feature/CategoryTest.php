<?php

use App\Models\Category;

it('lists categories', function () {
    Category::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug'],
            ],
        ]);
});

it('returns categories ordered by name', function () {
    Category::factory()->create(['name' => 'Zebra']);
    Category::factory()->create(['name' => 'Apple']);
    Category::factory()->create(['name' => 'Mango']);

    $response = $this->getJson('/api/v1/categories');

    $names = collect($response->json('data'))->pluck('name')->toArray();
    expect($names)->toBe(['Apple', 'Mango', 'Zebra']);
});

it('returns empty array when no categories exist', function () {
    $response = $this->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

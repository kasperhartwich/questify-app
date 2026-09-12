<?php

use App\Services\Api\QuestifyApiClient;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * Livewire's temporary upload path has no extension. Sending basename() of it
 * made the API reject the file with "The avatar failed to upload", because the
 * backend validates it with the `image` rule.
 */
it('uploads the avatar under its original filename', function () {
    Http::fake(['*' => Http::response(['data' => ['id' => 1, 'name' => 'K']], 200)]);

    $file = UploadedFile::fake()->image('holiday-selfie.jpg');

    app(QuestifyApiClient::class)->user()->updateProfile(
        ['name' => 'Kasper', 'locale' => 'en'],
        $file->getRealPath(),
        $file->getClientOriginalName(),
    );

    Http::assertSent(function ($request) {
        $names = collect($request->data())->where('name', 'avatar')->pluck('filename');

        return $names->contains('holiday-selfie.jpg');
    });
});

it('uploads the quest cover under its original filename', function () {
    Http::fake(['*' => Http::response(['data' => ['id' => 1]], 200)]);

    $file = UploadedFile::fake()->image('cover.png');

    app(QuestifyApiClient::class)->quests()->store(
        ['title' => 'Test'],
        $file->getRealPath(),
        $file->getClientOriginalName(),
    );

    Http::assertSent(function ($request) {
        $names = collect($request->data())->where('name', 'cover_image')->pluck('filename');

        return $names->isEmpty() || $names->contains('cover.png');
    });
});

<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Every static internal href in a Blade view must resolve to a real route.
 * A dead link ships silently — /settings in the create wizard 404'd for
 * months before anyone noticed.
 *
 * @return array<int, string>
 */
function staticInternalLinks(): array
{
    $links = [];

    foreach (File::allFiles(resource_path('views')) as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        preg_match_all('/href="(\/[^"{}\s]*)"/', $file->getContents(), $matches);

        foreach ($matches[1] as $href) {
            $links[] = strtok($href, '?#');
        }
    }

    return array_values(array_unique($links));
}

it('points every internal link at a route that exists', function () {
    $missing = [];

    foreach (staticInternalLinks() as $path) {
        $request = Request::create($path, 'GET');

        $matched = collect(Route::getRoutes()->getRoutes())
            ->contains(fn ($route) => in_array('GET', $route->methods(), true)
                && $route->matches($request, includingMethod: false));

        if (! $matched) {
            $missing[] = $path;
        }
    }

    expect($missing)->toBe([], 'Dead internal links: '.implode(', ', $missing));
});

it('redirects the friendly discover and create URLs', function (string $from, string $to) {
    $this->actingAs(User::factory()->create())
        ->get($from)
        ->assertRedirect($to);
})->with([
    ['/discover', '/discover/list'],
    ['/quests/create', '/create'],
]);

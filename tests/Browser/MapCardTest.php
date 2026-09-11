<?php

use App\Models\User;

beforeEach(fn () => mockFullApiClient());

it('renders the quest card even when distance fields are missing', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/discover/map');

    // A pin without the distance keys — exactly what a nearby response missing
    // them produces. One throwing expression must not blank the whole card.
    $out = $page->script(<<<'JS'
        (() => {
            const roots = [...document.querySelectorAll('[x-data]')];
            for (const el of roots) {
                const d = window.Alpine?.$data(el);
                if (d && 'pins' in d) {
                    d.selectedPin = {
                        id: 7,
                        title: 'Krogebjergparken Trail',
                        difficulty: 'easy',
                        checkpoint_count: 5,
                    };
                    return 'set';
                }
            }
            return 'no map root';
        })()
    JS);
    expect($out)->toBe('set');

    $page->assertSee('Krogebjergparken Trail')
        ->assertSee('5 stops');
});

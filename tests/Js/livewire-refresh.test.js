import { test } from 'node:test';
import assert from 'node:assert/strict';
import { refreshAllComponents } from '../../resources/js/livewire-refresh.js';

test('refreshes every component through its $wire proxy', () => {
    let refreshed = 0;
    const component = { $wire: { $refresh: () => { refreshed++; } } };

    refreshAllComponents({ all: () => [component, component] });

    assert.equal(refreshed, 2);
});

test('a component without $wire is skipped, Livewire missing is a no-op', () => {
    refreshAllComponents({ all: () => [{}] });
    refreshAllComponents(undefined);
});

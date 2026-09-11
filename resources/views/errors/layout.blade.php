{{--
    Shared error screen. Deliberately says nothing about HTTP status codes —
    players get a plain explanation, a way back, and the tab bar stays put so
    the app never feels like a dead end.
--}}
@component('layouts.app')
    <div class="flex min-h-[70vh] flex-col items-center justify-center px-6 text-center">
        <div class="mb-5 flex h-[72px] w-[72px] items-center justify-center rounded-[22px] bg-amber-400/20">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16.5" x2="12.01" y2="16.5"/>
            </svg>
        </div>

        <h1 class="mb-2 font-heading text-[22px] font-[800] leading-tight text-bark">{{ $title }}</h1>
        <p class="mb-7 max-w-[300px] text-[13px] leading-relaxed text-muted">{{ $body }}</p>

        <button
            onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '/discover/list'; }"
            class="mb-2.5 w-full max-w-[280px] rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark"
        >
            {{ __('general.back') }}
        </button>

        <a href="/discover/list" class="text-[13px] font-semibold text-forest-400" wire:navigate>{{ __('general.explore_quests') }}</a>
    </div>
@endcomponent

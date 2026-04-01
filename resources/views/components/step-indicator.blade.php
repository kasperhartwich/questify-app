@props(['current' => 1, 'total' => 4, 'backAction' => null, 'backUrl' => null])

<div class="flex items-center gap-2.5 pb-4 pt-1">
    @if ($backAction)
        <button wire:click="{{ $backAction }}" class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-[11px] bg-cream-dark">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
    @elseif ($backUrl)
        <a href="{{ $backUrl }}" class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-[11px] bg-cream-dark" wire:navigate>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
    @endif
    <div class="flex flex-1 gap-1">
        @for ($i = 1; $i <= $total; $i++)
            <div class="h-[3px] flex-1 rounded-[2px] {{ $i <= $current ? 'bg-forest-600' : 'bg-cream-border' }}"></div>
        @endfor
    </div>
    <span class="shrink-0 text-[11px] text-muted">{{ __('general.step_of', ['current' => $current, 'total' => $total]) }}</span>
</div>

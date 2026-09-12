@props(['expandable' => false])

{{-- Shared map chrome. Both the quest map and the wizard need "centre on me"
     and, where the map is a panel rather than the page, expand/collapse. --}}
<div class="absolute right-3 top-3 z-[500] flex flex-col gap-2">
    <button
        type="button"
        @click="locateUser()"
        class="flex h-9 w-9 items-center justify-center rounded-[11px] bg-white shadow-[0_2px_8px_rgba(0,0,0,0.15)]"
        aria-label="{{ __('general.my_location') }}"
    >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/><circle cx="12" cy="12" r="8"/></svg>
    </button>

    @if ($expandable)
        <button
            type="button"
            @click="toggleMapSize()"
            class="flex h-9 w-9 items-center justify-center rounded-[11px] bg-white shadow-[0_2px_8px_rgba(0,0,0,0.15)]"
            :aria-label="mapExpanded ? '{{ __('general.collapse_map') }}' : '{{ __('general.expand_map') }}'"
        >
            <svg x-show="!mapExpanded" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
            <svg x-show="mapExpanded" x-cloak width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14h6v6"/><path d="M20 10h-6V4"/><path d="M14 10l7-7"/><path d="M3 21l7-7"/></svg>
        </button>
    @endif
</div>

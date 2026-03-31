@props([
    'quest' => null,
    'variant' => 'discover',
    'progress' => null,
    'score' => null,
    'rank' => null,
    'status' => null,
    'ctaLabel' => null,
    'ctaUrl' => null,
])

@php
    $headerColor = '#0B3D2E';

    $difficultyClass = match($quest->difficulty ?? '') {
        'hard' => 'bg-[#FCDDD7] text-[#C03A20]',
        'medium' => 'bg-[#D4EDE4] text-forest-600',
        default => 'bg-amber-100 text-amber-700',
    };
@endphp

<a href="{{ $ctaUrl ?? '/quests/' . ($quest->id ?? '') }}" class="block overflow-hidden rounded-[14px] bg-white shadow-[0_2px_10px_rgba(0,0,0,0.06)] {{ $status === 'completed' ? 'opacity-70' : ($status === 'upcoming' ? 'opacity-60' : '') }}" wire:navigate>
    {{-- Header --}}
    @if (!empty($quest->cover_image_url))
        <img src="{{ $quest->cover_image_url }}" alt="{{ $quest->title }}" class="h-40 w-full object-cover" />
    @else
        <div class="relative overflow-hidden px-3.5 pb-2.5 pt-3" style="background: {{ $headerColor }}">
            <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <h3 class="font-heading text-sm font-bold leading-snug text-white">{{ $quest->title }}</h3>
                    @if ($quest->user?->name ?? null)
                        <p class="mt-1 text-[9px] text-white/55">by {{ $quest->user->name }}{{ !empty($quest->distance) ? ' · ' . $quest->distance : '' }}</p>
                    @endif
                </div>
                @if (!empty($quest->average_rating))
                    <span class="ml-2 shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-bold text-amber-700">⭐ {{ $quest->average_rating }}</span>
                @endif
            </div>
        </div>
    @endif

    {{-- Body --}}
    <div class="px-3.5 pb-3 pt-2.5">
        @if (!empty($quest->cover_image_url))
            <h3 class="font-heading text-base font-bold leading-snug text-bark">{{ $quest->title }}</h3>
        @endif

        {{-- Meta row --}}
        <div class="mt-2 flex flex-wrap items-center gap-2.5 text-[11px] text-muted">
            @if (!empty($quest->estimated_duration_minutes))
                <span class="flex items-center gap-1">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    {{ $quest->estimated_duration_minutes }} min
                </span>
            @endif
            @if (!empty($quest->checkpoints_count))
                <span class="flex items-center gap-1">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                    {{ $quest->checkpoints_count }} {{ __('general.stops') }}
                </span>
            @endif
            @if (!empty($quest->sessions_count))
                <span class="flex items-center gap-1">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                    {{ $quest->sessions_count }} {{ __('general.plays') }}
                </span>
            @endif
            @if ($quest->difficulty ?? null)
                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold {{ $difficultyClass }}">{{ ucfirst($quest->difficulty) }}</span>
            @endif
        </div>

        {{-- Score / Rank --}}
        @if ($score || $rank)
            <div class="mt-2 flex items-center gap-3 text-[11px] text-muted">
                @if ($score)
                    <span>{{ __('general.score') }}: <strong class="text-bark">{{ number_format($score) }} pts</strong></span>
                @endif
                @if ($rank)
                    <span>{{ $rank }}</span>
                @endif
            </div>
        @endif

        {{-- CTA button --}}
        @if ($ctaLabel)
            <div class="mt-3">
                <span class="block w-full rounded-xl bg-forest-600 px-4 py-3 text-center font-heading text-sm font-bold text-white">{{ $ctaLabel }}</span>
            </div>
        @endif
    </div>
</a>

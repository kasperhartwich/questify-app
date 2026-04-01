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

<a href="{{ $ctaUrl ?? '/quests/' . ($quest->id ?? '') }}" class="block overflow-hidden rounded-[16px] bg-white shadow-sm mb-[12px] {{ $status === 'completed' ? 'opacity-70' : ($status === 'upcoming' ? 'opacity-60' : '') }}" wire:navigate>
    {{-- Header --}}
    @if (!empty($quest->cover_image_url))
        <img src="{{ $quest->cover_image_url }}" alt="{{ $quest->title }}" class="h-40 w-full object-cover" />
    @else
        <div class="relative overflow-hidden px-[16px] pb-[12px] pt-[14px]" style="background: {{ $headerColor }}">
            <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <h3 class="font-heading text-[15px] font-bold leading-snug text-white">{{ $quest->title }}</h3>
                    @if ($quest->user?->name ?? null)
                        <p class="mt-1 text-[12px] text-white/55">{{ $quest->user->name }}{{ !empty($quest->distance) ? ' · ' . $quest->distance : '' }}</p>
                    @endif
                </div>
                @if (!empty($quest->average_rating))
                    <span class="ml-2 shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-600">⭐ {{ $quest->average_rating }}</span>
                @endif
            </div>
            {{-- Progress bar --}}
            @if ($progress !== null)
                <div class="mt-2.5 h-[3px] w-full rounded-full bg-white/[0.18]">
                    <div class="h-full rounded-full bg-amber-400" style="width: {{ $progress }}%"></div>
                </div>
            @endif
        </div>
    @endif

    {{-- Body --}}
    <div class="px-[16px] pb-[14px] pt-[12px]">
        @if (!empty($quest->cover_image_url))
            <h3 class="font-heading text-base font-bold leading-snug text-bark">{{ $quest->title }}</h3>
        @endif

        {{-- Meta row --}}
        <div class="flex flex-wrap items-center gap-3 text-[12px] text-muted">
            @if (!empty($quest->estimated_duration_minutes))
                <span class="flex items-center gap-1">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    {{ $quest->estimated_duration_minutes }} {{ __('general.minutes') }}
                </span>
            @endif
            @if (!empty($quest->checkpoints_count))
                <span class="flex items-center gap-1">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                    {{ $quest->checkpoints_count }} {{ __('general.stops') }}
                </span>
            @endif
            @if ($quest->difficulty ?? null)
                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $difficultyClass }}">{{ ucfirst($quest->difficulty) }}</span>
            @endif
        </div>

        {{-- Score / Rank --}}
        @if ($score || $rank)
            <div class="mt-2 flex items-center gap-3 text-[12px] text-muted">
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
                <span class="block w-full rounded-[12px] bg-forest-600 py-[11px] text-center text-[14px] font-bold text-white">{{ $ctaLabel }}</span>
            </div>
        @endif
    </div>
</a>

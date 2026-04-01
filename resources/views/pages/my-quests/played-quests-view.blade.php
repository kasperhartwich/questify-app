<div class="flex flex-col">
    {{-- Title --}}
    <div class="px-[20px] py-[6px]">
        <h1 class="font-heading text-[24px] font-[800] text-bark">{{ __('general.my_quests') }}</h1>
    </div>

    {{-- Tabs --}}
    <div class="mt-[12px] flex border-b-2 border-cream-border px-[20px]">
        <button wire:click="$set('tab', 'playing')" class="-mb-[2px] flex-1 border-b-2 py-[12px] text-center text-[13px] font-semibold {{ $tab === 'playing' ? 'border-b-forest-600 text-forest-600' : 'border-b-transparent text-muted' }}">
            {{ __('general.playing') }}
        </button>
        <button wire:click="$set('tab', 'created')" class="-mb-[2px] flex-1 border-b-2 py-[12px] text-center text-[13px] font-semibold {{ $tab === 'created' ? 'border-b-forest-600 text-forest-600' : 'border-b-transparent text-muted' }}">
            {{ __('general.created') }}
        </button>
        <button wire:click="$set('tab', 'history')" class="-mb-[2px] flex-1 border-b-2 py-[12px] text-center text-[13px] font-semibold {{ $tab === 'history' ? 'border-b-forest-600 text-forest-600' : 'border-b-transparent text-muted' }}">
            {{ __('general.history') }}
        </button>
    </div>

    {{-- Playing Tab --}}
    @if ($tab === 'playing')
        <div class="space-y-3 p-[20px]">
            @php $hasActive = false; @endphp
            @foreach ($participations as $participation)
                @php
                    $session = $participation->quest_session ?? $participation->questSession ?? null;
                    $quest = $session?->quest ?? null;
                    $isFinished = ($participation->finished_at ?? null) !== null;
                @endphp
                @if ($quest && !$isFinished)
                    @php
                        $hasActive = true;
                        $checkpointsCount = $quest->checkpoints_count ?? $session->checkpoints_count ?? 7;
                        $currentIndex = $participation->current_checkpoint_index ?? 0;
                        $progressPercent = $checkpointsCount > 0 ? round($currentIndex / $checkpointsCount * 100) : 0;
                        $joinCode = $session->join_code ?? '';
                    @endphp
                    <div class="overflow-hidden rounded-[14px] bg-white shadow-sm">
                        {{-- Card top: forest-600 with quest info --}}
                        <div class="relative overflow-hidden bg-forest-600 px-4 py-3.5">
                            <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
                            <div class="flex items-start justify-between">
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-heading text-[14px] font-bold leading-tight text-white">{{ $quest->title ?? '' }}</h3>
                                    <p class="mt-1 text-[11px] text-white/55">{{ __('general.in_progress') }} · {{ __('general.stop_x_of_y', ['current' => $currentIndex, 'total' => $checkpointsCount]) }}</p>
                                </div>
                                <span class="ml-2 shrink-0 rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-bold text-amber-700">{{ __('general.active') }}</span>
                            </div>
                            {{-- Progress bar --}}
                            <div class="mt-3">
                                <div class="h-[3px] overflow-hidden rounded-full bg-white/[0.18]">
                                    <div class="h-full rounded-full bg-amber-400" style="width: {{ $progressPercent }}%"></div>
                                </div>
                                <p class="mt-1.5 text-[10px] text-white/50">{{ $currentIndex }} / {{ $checkpointsCount }} {{ __('general.checkpoints_reached') }}</p>
                            </div>
                        </div>
                        {{-- Card bottom: score + continue --}}
                        <div class="flex items-center justify-between px-4 py-3">
                            <span class="text-[12px] text-muted">{{ __('general.score') }}: <strong class="text-bark">{{ number_format($participation->score ?? 0) }} pts</strong></span>
                            <a href="/session/{{ $joinCode }}/play" class="rounded-[10px] bg-forest-600 px-4 py-2 text-[12px] font-bold text-white" wire:navigate>{{ __('general.continue') }} &rarr;</a>
                        </div>
                    </div>
                @endif
            @endforeach
            @unless ($hasActive)
                {{-- Empty state --}}
                <div class="flex flex-col items-center px-6 py-16">
                    {{-- Illustration SVG --}}
                    <div class="mb-5">
                        <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="120" height="120" rx="60" fill="#F0E8D6"/>
                            <path d="M20 75 Q35 55 50 65 Q65 75 80 55 Q95 35 105 50" stroke="#E5DDD0" stroke-width="3" fill="none" stroke-linecap="round"/>
                            <path d="M15 85 Q40 65 60 75 Q80 85 100 65" stroke="#E5DDD0" stroke-width="2" fill="none" stroke-linecap="round"/>
                            <circle cx="60" cy="52" r="22" fill="#0B3D2E"/>
                            <text x="60" y="60" text-anchor="middle" font-family="Exo 2, sans-serif" font-size="22" font-weight="800" fill="white">Q</text>
                            <circle cx="35" cy="42" r="4" fill="#F5A623" opacity="0.8"/>
                            <circle cx="85" cy="38" r="3" fill="#F5A623" opacity="0.6"/>
                            <circle cx="78" cy="72" r="3.5" fill="#F5A623" opacity="0.7"/>
                        </svg>
                    </div>
                    <h2 class="font-heading text-[20px] font-[800] text-bark">{{ __('general.no_quests_yet') }}</h2>
                    <p class="mt-2 whitespace-pre-line text-center text-[14px] leading-[1.6] text-muted">{{ __('general.no_quests_yet_playing_desc') }}</p>
                    <a href="/discover" class="mt-6 w-full rounded-[12px] bg-amber-400 py-3.5 text-center text-[14px] font-bold text-bark" wire:navigate>{{ __('general.explore_quests') }} &rarr;</a>
                    <p class="mt-3 text-[13px] text-muted">{{ __('general.or_create_a_quest') }} <a href="/quests/create" class="font-semibold text-forest-600" wire:navigate>{{ __('general.create_a_quest') }}</a></p>
                </div>
            @endunless
        </div>

    {{-- Created Tab --}}
    @elseif ($tab === 'created')
        <div class="space-y-3 p-[20px]">
            @forelse ($createdQuests as $quest)
                <a href="/quests/{{ $quest->id }}" class="block overflow-hidden rounded-[14px] bg-white shadow-sm" wire:navigate wire:key="created-{{ $quest->id }}">
                    <div class="relative overflow-hidden bg-forest-600 px-4 py-3.5">
                        <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-heading text-[14px] font-bold leading-tight text-white">{{ $quest->title }}</h3>
                                <p class="mt-1 text-[11px] text-white/55">{{ ucfirst(str_replace('_', ' ', $quest->status ?? 'draft')) }}</p>
                            </div>
                            @php
                                $statusClass = match($quest->status ?? '') {
                                    'published' => 'bg-[#D4EDE4] text-forest-600',
                                    'pending_review' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-cream-dark text-muted',
                                };
                            @endphp
                            <span class="ml-2 shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $quest->status ?? 'draft')) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3">
                        <div class="flex items-center gap-3 text-[11px] text-muted">
                            @if ($quest->sessions_count ?? null)
                                <span>{{ $quest->sessions_count }} {{ __('general.plays') }}</span>
                            @endif
                        </div>
                        @if (($quest->status ?? '') === 'draft')
                            <span class="text-[12px] font-semibold text-forest-400">{{ __('general.edit') }}</span>
                        @endif
                    </div>
                </a>
            @empty
                {{-- Empty state --}}
                <div class="flex flex-col items-center px-6 py-16">
                    <div class="mb-5">
                        <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="120" height="120" rx="60" fill="#F0E8D6"/>
                            <path d="M20 75 Q35 55 50 65 Q65 75 80 55 Q95 35 105 50" stroke="#E5DDD0" stroke-width="3" fill="none" stroke-linecap="round"/>
                            <path d="M15 85 Q40 65 60 75 Q80 85 100 65" stroke="#E5DDD0" stroke-width="2" fill="none" stroke-linecap="round"/>
                            <circle cx="60" cy="52" r="22" fill="#0B3D2E"/>
                            <text x="60" y="60" text-anchor="middle" font-family="Exo 2, sans-serif" font-size="22" font-weight="800" fill="white">Q</text>
                            <circle cx="35" cy="42" r="4" fill="#F5A623" opacity="0.8"/>
                            <circle cx="85" cy="38" r="3" fill="#F5A623" opacity="0.6"/>
                            <circle cx="78" cy="72" r="3.5" fill="#F5A623" opacity="0.7"/>
                        </svg>
                    </div>
                    <h2 class="font-heading text-[20px] font-[800] text-bark">{{ __('general.no_created_quests_yet') }}</h2>
                    <p class="mt-2 whitespace-pre-line text-center text-[14px] leading-[1.6] text-muted">{{ __('general.no_created_quests_desc') }}</p>
                    <a href="/quests/create" class="mt-6 w-full rounded-[12px] bg-amber-400 py-3.5 text-center text-[14px] font-bold text-bark" wire:navigate>{{ __('general.create_quest') }} &rarr;</a>
                    <p class="mt-3 text-[13px] text-muted">{{ __('general.or_create_a_quest') }} <a href="/discover" class="font-semibold text-forest-600" wire:navigate>{{ __('general.explore_quests') }}</a></p>
                </div>
            @endforelse

            @if (!empty($nextCursor))
                <button wire:click="$set('cursor', '{{ $nextCursor }}')" class="mt-2 w-full rounded-[12px] bg-forest-600 px-4 py-3 text-[13px] font-bold text-white">
                    {{ __('general.load_more') }}
                </button>
            @endif
        </div>

    {{-- History Tab --}}
    @elseif ($tab === 'history')
        <div class="space-y-3 p-[20px]">
            @php $hasHistory = false; @endphp
            @foreach ($participations as $participation)
                @php
                    $session = $participation->quest_session ?? $participation->questSession ?? null;
                    $quest = $session?->quest ?? null;
                    $isFinished = ($participation->finished_at ?? null) !== null;
                @endphp
                @if ($quest && $isFinished)
                    @php $hasHistory = true; @endphp
                    <div class="overflow-hidden rounded-[14px] bg-white opacity-70 shadow-sm">
                        <div class="relative overflow-hidden bg-forest-500 px-4 py-3.5">
                            <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-heading text-[14px] font-bold leading-tight text-white">{{ $quest->title ?? '' }}</h3>
                                    <p class="mt-1 text-[11px] text-white/55">{{ __('general.completed') }} · {{ $participation->finished_at ? \Carbon\Carbon::parse($participation->finished_at)->diffForHumans() : '' }}</p>
                                </div>
                                <span class="ml-2 flex shrink-0 items-center gap-1 rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-bold text-white/90">
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ __('general.done') }}
                                </span>
                            </div>
                        </div>
                        <div class="px-4 py-3">
                            <div class="flex items-center gap-3 text-[12px] text-muted">
                                <span>{{ __('general.final_score') }}: <strong class="text-bark">{{ number_format($participation->score ?? 0) }} pts</strong></span>
                                @if ($participation->rank ?? null)
                                    <span>#{{ $participation->rank }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
            @unless ($hasHistory)
                {{-- Empty state --}}
                <div class="flex flex-col items-center px-6 py-16">
                    <div class="mb-5">
                        <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="120" height="120" rx="60" fill="#F0E8D6"/>
                            <path d="M20 75 Q35 55 50 65 Q65 75 80 55 Q95 35 105 50" stroke="#E5DDD0" stroke-width="3" fill="none" stroke-linecap="round"/>
                            <path d="M15 85 Q40 65 60 75 Q80 85 100 65" stroke="#E5DDD0" stroke-width="2" fill="none" stroke-linecap="round"/>
                            <circle cx="60" cy="52" r="22" fill="#0B3D2E"/>
                            <text x="60" y="60" text-anchor="middle" font-family="Exo 2, sans-serif" font-size="22" font-weight="800" fill="white">Q</text>
                            <circle cx="35" cy="42" r="4" fill="#F5A623" opacity="0.8"/>
                            <circle cx="85" cy="38" r="3" fill="#F5A623" opacity="0.6"/>
                            <circle cx="78" cy="72" r="3.5" fill="#F5A623" opacity="0.7"/>
                        </svg>
                    </div>
                    <h2 class="font-heading text-[20px] font-[800] text-bark">{{ __('general.no_history_yet') }}</h2>
                    <p class="mt-2 whitespace-pre-line text-center text-[14px] leading-[1.6] text-muted">{{ __('general.no_history_yet_desc') }}</p>
                    <a href="/discover" class="mt-6 w-full rounded-[12px] bg-amber-400 py-3.5 text-center text-[14px] font-bold text-bark" wire:navigate>{{ __('general.explore_quests') }} &rarr;</a>
                </div>
            @endunless
        </div>
    @endif
</div>

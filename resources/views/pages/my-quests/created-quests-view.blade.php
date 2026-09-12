<div class="flex h-full flex-col bg-cream">
    {{-- Title --}}
    <div class="shrink-0 px-[20px] py-[6px]">
        <h1 class="font-heading text-[24px] font-[800] text-bark">{{ __('general.my_quests') }}</h1>
    </div>

    {{-- Tabs --}}
    <div class="mt-[12px] flex shrink-0 border-b-2 border-cream-border px-[20px]">
        <a href="/my-quests" class="-mb-[2px] flex-1 border-b-2 border-b-transparent py-[12px] text-center text-[13px] font-semibold text-muted" wire:navigate>
            {{ __('general.playing') }}
        </a>
        <a href="/my-quests/created" class="-mb-[2px] flex-1 border-b-2 border-b-forest-600 py-[12px] text-center text-[13px] font-semibold text-forest-600">
            {{ __('general.created') }}
        </a>
        <a href="/my-quests" class="-mb-[2px] flex-1 border-b-2 border-b-transparent py-[12px] text-center text-[13px] font-semibold text-muted" wire:navigate>
            {{ __('general.history') }}
        </a>
    </div>

    {{-- Content (scrollable) --}}
    <div class="flex-1 overflow-y-auto">
        <div class="space-y-3 p-[20px]">
            @forelse ($quests as $quest)
                <a href="/quests/{{ $quest->id }}" class="block overflow-hidden rounded-[14px] bg-white shadow-sm" wire:navigate wire:key="created-{{ $quest->id }}">
                    <div class="relative overflow-hidden bg-forest-600 px-4 py-3.5">
                        <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-heading text-[14px] font-bold leading-tight text-white">{{ $quest->title }}</h3>
                                {{-- The badge on the right already says the status; show the
                                     quest's own copy here instead. --}}
                                @if (filled($quest->description ?? null))
                                    <p class="mt-1 text-[11px] leading-relaxed text-white/55">{{ Str::limit($quest->description, 90) }}</p>
                                @endif
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
                    <div class="px-4 py-3">
                        {{-- Same meta line as the discover cards. --}}
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[11px] text-muted">
                            @if ($quest->estimated_duration_minutes ?? null)
                                <span class="flex items-center gap-1">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    {{ $quest->estimated_duration_minutes }} {{ __('general.minutes') }}
                                </span>
                            @endif
                            @if ($quest->checkpoint_count ?? null)
                                <span>{{ $quest->checkpoint_count }} {{ __('general.stops') }}</span>
                            @endif
                            @if ($quest->difficulty ?? null)
                                @php
                                    $difficultyClass = match($quest->difficulty) {
                                        'easy' => 'bg-[#D4EDE4] text-forest-600',
                                        'hard' => 'bg-red-50 text-coral',
                                        default => 'bg-amber-100 text-amber-700',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $difficultyClass }}">{{ __('general.'.$quest->difficulty) }}</span>
                            @endif
                            @if ($quest->category->name ?? null)
                                <span class="rounded-full bg-cream-dark px-2 py-0.5 text-[10px] font-bold text-muted">{{ $quest->category->name }}</span>
                            @endif
                            @if ($quest->sessions_count ?? null)
                                <span>{{ $quest->sessions_count }} {{ __('general.plays') }}</span>
                            @endif
                        </div>
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
    </div>
</div>

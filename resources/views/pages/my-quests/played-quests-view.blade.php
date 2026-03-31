<div class="flex flex-col">
    {{-- Title --}}
    <div class="px-4 pb-0 pt-1.5">
        <h1 class="font-heading text-xl font-extrabold text-bark">{{ __('general.my_quests') }}</h1>
    </div>

    {{-- Tabs --}}
    <div class="mt-2.5 flex border-b-2 border-cream-border px-3.5">
        <button wire:click="$set('tab', 'playing')" class="-mb-[2px] flex-1 border-b-2 py-2.5 text-center text-[11px] font-semibold {{ $tab === 'playing' ? 'border-forest-600 text-forest-600' : 'border-transparent text-muted' }}">
            {{ __('general.playing') }}
        </button>
        <button wire:click="$set('tab', 'created')" class="-mb-[2px] flex-1 border-b-2 py-2.5 text-center text-[11px] font-semibold {{ $tab === 'created' ? 'border-forest-600 text-forest-600' : 'border-transparent text-muted' }}">
            {{ __('general.created') }}
        </button>
        <button wire:click="$set('tab', 'history')" class="-mb-[2px] flex-1 border-b-2 py-2.5 text-center text-[11px] font-semibold {{ $tab === 'history' ? 'border-forest-600 text-forest-600' : 'border-transparent text-muted' }}">
            {{ __('general.history') }}
        </button>
    </div>

    {{-- Playing Tab --}}
    @if ($tab === 'playing')
        <div class="space-y-2.5 p-3.5">
            @php $hasActive = false; @endphp
            @foreach ($participations as $participation)
                @php
                    $session = $participation->quest_session ?? $participation->questSession ?? null;
                    $quest = $session->quest ?? null;
                    if (!$quest) continue;
                    $sessionStatus = $session->status ?? '';
                    $isFinished = ($participation->finished_at ?? null) !== null;
                    if ($isFinished) continue;
                    $hasActive = true;
                    $checkpointsCount = $quest->checkpoints_count ?? $session->checkpoints_count ?? 7;
                    $currentIndex = $participation->current_checkpoint_index ?? 0;
                    $progressPercent = $checkpointsCount > 0 ? round($currentIndex / $checkpointsCount * 100) : 0;
                    $joinCode = $session->join_code ?? '';
                @endphp

                <div class="overflow-hidden rounded-[14px] bg-white shadow-sm">
                    {{-- Header --}}
                    <div class="relative overflow-hidden bg-forest-600 px-3.5 py-3">
                        <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
                        <div class="flex items-start justify-between">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-heading text-xs font-bold leading-tight text-white">{{ $quest->title ?? '' }}</h3>
                                <p class="mt-0.5 text-[9px] text-white/55">In progress · Stop {{ $currentIndex }} of {{ $checkpointsCount }}</p>
                            </div>
                            <span class="ml-2 shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-bold text-amber-700">{{ __('general.active') }}</span>
                        </div>
                        <div class="mt-2.5">
                            <div class="h-[3px] overflow-hidden rounded-full bg-white/20">
                                <div class="h-full rounded-full bg-amber-400" style="width: {{ $progressPercent }}%"></div>
                            </div>
                            <p class="mt-1 text-[9px] text-white/50">{{ $currentIndex }} / {{ $checkpointsCount }} {{ __('general.checkpoints_reached') }}</p>
                        </div>
                    </div>
                    {{-- Body --}}
                    <div class="flex items-center justify-between px-3.5 py-2.5">
                        <span class="text-[11px] text-muted">{{ __('general.score') }}: <strong class="text-bark">{{ number_format($participation->score ?? 0) }} pts</strong></span>
                        <a href="/session/{{ $joinCode }}/play" class="rounded-[9px] bg-forest-600 px-3.5 py-1.5 text-[11px] font-bold text-white" wire:navigate>{{ __('general.continue') }} &rarr;</a>
                    </div>
                </div>
            @endforeach
            @unless ($hasActive)
                <div class="py-12 text-center text-muted">
                    <p>{{ __('general.no_active_quests') }}</p>
                </div>
            @endunless
        </div>

    {{-- Created Tab --}}
    @elseif ($tab === 'created')
        <div class="space-y-2.5 p-3.5">
            @forelse ($createdQuests as $quest)
                <a href="/quests/{{ $quest->id }}" class="block overflow-hidden rounded-[14px] bg-white shadow-sm" wire:navigate wire:key="created-{{ $quest->id }}">
                    <div class="relative overflow-hidden bg-forest-600 px-3.5 py-3">
                        <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-heading text-xs font-bold leading-tight text-white">{{ $quest->title }}</h3>
                                <p class="mt-0.5 text-[9px] text-white/55">{{ ucfirst(str_replace('_', ' ', $quest->status ?? 'draft')) }}</p>
                            </div>
                            @php
                                $statusClass = match($quest->status ?? '') {
                                    'published' => 'bg-[#D4EDE4] text-forest-600',
                                    'pending_review' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-cream-dark text-muted',
                                };
                            @endphp
                            <span class="ml-2 shrink-0 rounded-full px-2 py-0.5 text-[9px] font-bold {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $quest->status ?? 'draft')) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-3.5 py-2.5">
                        <div class="flex items-center gap-3 text-[10px] text-muted">
                            @if ($quest->sessions_count ?? null)
                                <span>{{ $quest->sessions_count }} {{ __('general.plays') }}</span>
                            @endif
                        </div>
                        @if (($quest->status ?? '') === 'draft')
                            <span class="text-[11px] font-semibold text-forest-400">{{ __('general.edit') }}</span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="py-12 text-center text-muted">
                    <p>{{ __('general.no_created_quests') }}</p>
                </div>
            @endforelse

            @if (!empty($nextCursor))
                <button wire:click="$set('cursor', '{{ $nextCursor }}')" class="mt-2 w-full rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-bold text-white">
                    {{ __('general.load_more') }}
                </button>
            @endif
        </div>

    {{-- History Tab --}}
    @elseif ($tab === 'history')
        <div class="space-y-2.5 p-3.5">
            @php $hasHistory = false; @endphp
            @foreach ($participations as $participation)
                @php
                    $session = $participation->quest_session ?? $participation->questSession ?? null;
                    $quest = $session->quest ?? null;
                    if (!$quest) continue;
                    $isFinished = ($participation->finished_at ?? null) !== null;
                    if (!$isFinished) continue;
                    $hasHistory = true;
                @endphp

                <div class="overflow-hidden rounded-[14px] bg-white opacity-70 shadow-sm">
                    <div class="relative overflow-hidden bg-[#165C45] px-3.5 py-3">
                        <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-heading text-xs font-bold leading-tight text-white">{{ $quest->title ?? '' }}</h3>
                                <p class="mt-0.5 text-[9px] text-white/55">Completed</p>
                            </div>
                            <span class="ml-2 shrink-0 rounded-full bg-white/20 px-2 py-0.5 text-[9px] font-bold text-white/90">Done</span>
                        </div>
                    </div>
                    <div class="px-3.5 py-2.5">
                        <div class="flex items-center gap-3 text-[10px] text-muted">
                            <span>Final score: <strong class="text-bark">{{ number_format($participation->score ?? 0) }} pts</strong></span>
                        </div>
                    </div>
                </div>
            @endforeach
            @unless ($hasHistory)
                <div class="py-12 text-center text-muted">
                    <p>{{ __('general.no_sessions_found') }}</p>
                </div>
            @endunless
        </div>
    @endif
</div>

<div class="flex flex-col">
    {{-- Title --}}
    <div class="px-4 pb-0 pt-1.5">
        <h1 class="font-heading text-xl font-extrabold text-bark">{{ __('general.my_quests') }}</h1>
    </div>

    {{-- Tabs --}}
    <div class="mt-2.5 flex border-b-2 border-cream-border px-3.5">
        <button wire:click="$set('tab', 'playing')" class="-mb-[2px] flex-1 border-b-2 py-2.5 text-center text-[11px] font-semibold {{ $tab === 'playing' ? 'border-forest-600 text-forest-600' : 'border-transparent text-muted' }}">
            {{ __('general.playing') ?? 'Playing' }}
        </button>
        <button wire:click="$set('tab', 'created')" class="-mb-[2px] flex-1 border-b-2 py-2.5 text-center text-[11px] font-semibold {{ $tab === 'created' ? 'border-forest-600 text-forest-600' : 'border-transparent text-muted' }}">
            {{ __('general.created') ?? 'Created' }}
        </button>
        <button wire:click="$set('tab', 'history')" class="-mb-[2px] flex-1 border-b-2 py-2.5 text-center text-[11px] font-semibold {{ $tab === 'history' ? 'border-forest-600 text-forest-600' : 'border-transparent text-muted' }}">
            {{ __('general.history') ?? 'History' }}
        </button>
    </div>

    {{-- Playing Tab --}}
    @if ($tab === 'playing')
        <div class="space-y-2.5 p-3.5">
            @forelse ($participations as $participation)
                @php
                    $session = $participation->questSession ?? null;
                    $quest = $session?->quest ?? null;
                    $isActive = ($session?->status?->value ?? $session?->status ?? '') === 'active';
                    $isCompleted = ($participation->finished_at ?? null) !== null;
                @endphp
                @if ($quest && ($isActive || !$isCompleted))
                    <x-quest-card
                        :quest="$quest"
                        variant="playing"
                        :status="$isActive ? 'active' : 'upcoming'"
                        :score="$participation->score ?? null"
                        :progress="$isActive ? [
                            'percent' => ($session->checkpoints_count ?? 7) > 0 ? round(($participation->current_checkpoint_index ?? 0) / ($session->checkpoints_count ?? 7) * 100) : 0,
                            'label' => ($participation->current_checkpoint_index ?? 0) . ' / ' . ($quest->checkpoints_count ?? '?') . ' ' . (__('general.checkpoints_reached') ?? 'checkpoints reached'),
                        ] : null"
                        :cta-url="$isActive ? '/session/' . ($session->join_code ?? '') . '/play' : null"
                    />
                @endif
            @empty
                <div class="py-12 text-center text-muted">
                    <p>{{ __('general.no_active_quests') ?? 'No active quests' }}</p>
                </div>
            @endforelse
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
                                <p class="mt-0.5 text-[9px] text-white/55">{{ ucfirst($quest->status ?? 'draft') }}</p>
                            </div>
                            @php
                                $statusClass = match($quest->status ?? '') {
                                    'published' => 'bg-[#D4EDE4] text-forest-600',
                                    'pending_review' => 'bg-amber-100 text-amber-700',
                                    'archived' => 'bg-cream-dark text-muted',
                                    default => 'bg-cream-dark text-muted',
                                };
                            @endphp
                            <span class="ml-2 shrink-0 rounded-full px-2 py-0.5 text-[9px] font-bold {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $quest->status ?? 'draft')) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-3.5 py-2.5">
                        <div class="flex items-center gap-3 text-[10px] text-muted">
                            @if ($quest->sessions_count ?? null)
                                <span>{{ $quest->sessions_count }} {{ __('general.plays') ?? 'plays' }}</span>
                            @endif
                            @if ($quest->average_rating ?? null)
                                <span>{{ $quest->average_rating }}</span>
                            @endif
                        </div>
                        @if (($quest->status ?? '') === 'draft')
                            <a href="/create?edit={{ $quest->id }}" class="text-[11px] font-semibold text-forest-400" wire:navigate>{{ __('general.edit') ?? 'Edit' }}</a>
                        @endif
                    </div>
                </a>
            @empty
                <div class="py-12 text-center text-muted">
                    <p>{{ __('general.no_created_quests') ?? 'No created quests yet' }}</p>
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
            @forelse ($participations as $participation)
                @php
                    $session = $participation->questSession ?? null;
                    $quest = $session?->quest ?? null;
                    $isCompleted = ($participation->finished_at ?? null) !== null;
                @endphp
                @if ($quest && $isCompleted)
                    <x-quest-card
                        :quest="$quest"
                        variant="history"
                        status="completed"
                        :score="$participation->score ?? null"
                        :cta-url="'/quests/' . ($quest->id ?? '')"
                    />
                @endif
            @empty
                <div class="py-12 text-center text-muted">
                    <p>{{ __('general.no_sessions_found') ?? 'No completed quests yet' }}</p>
                </div>
            @endforelse
        </div>
    @endif
</div>

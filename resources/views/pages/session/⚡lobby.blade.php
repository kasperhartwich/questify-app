<?php

use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Session Lobby')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    public array $session = [];

    public string $code = '';

    public array $participants = [];

    public bool $isHost = false;

    public string $shareUrl = '';

    public string $joinUrl = '';

    public function mount(string $code): void
    {
        $this->code = $code;
        $this->loadSession();
    }

    public function loadSession(): void
    {
        $response = $this->tryApiCall(fn () => $this->api->sessions()->show($this->code));
        $this->session = $response['data'] ?? [];
        $this->isHost = Auth::id() === ($this->session['host']['id'] ?? null);
        $this->joinUrl = url('/join/' . $this->code . '/name');
        $this->shareUrl = $this->joinUrl;
        $this->participants = $this->session['participants'] ?? [];
    }

    #[On('echo-presence:session.{code},ParticipantJoined')]
    public function onParticipantJoined(): void
    {
        $this->loadSession();
    }

    #[On('echo-presence:session.{code},SessionStarted')]
    public function onSessionStarted(): void
    {
        $this->redirect('/session/' . $this->code . '/play');
    }

    #[On('confirm-start-session')]
    public function startSession(): void
    {
        if (! $this->isHost) {
            return;
        }

        $this->tryApiCall(fn () => $this->api->sessions()->start($this->code));

        $this->redirect('/session/' . $this->code . '/host');
    }

    public function shareSession(): void
    {
        $this->dispatch('share-session', url: $this->shareUrl, code: $this->code);
    }
};
?>

<div class="flex min-h-screen flex-col">
    {{-- Forest Header --}}
    <div class="relative overflow-hidden bg-forest-600 px-5 pb-5 pt-6 text-white">
        <div class="pointer-events-none absolute right-[-24px] top-[-24px] h-[120px] w-[120px] rounded-full border-[22px] border-[rgba(245,166,35,0.1)]"></div>

        <p class="text-[11px] font-bold uppercase tracking-widest text-amber-400">{{ __('sessions.lobby') }}</p>
        <h1 class="mt-1.5 font-heading text-[19px] font-bold leading-[1.2] text-white">{{ $session['quest']['title'] ?? __('sessions.session') }}</h1>

        {{-- Badges --}}
        <div class="mt-3 flex flex-wrap gap-2">
            @if (!empty($session['play_mode']))
                <span class="rounded-full bg-amber-100 px-2.5 py-[3px] text-[11px] font-bold text-amber-600">{{ ucfirst(str_replace('_', ' ', $session['play_mode'])) }}</span>
            @endif
            @if (!empty($session['quest']['checkpoints_count']))
                <span class="rounded-full bg-white/15 px-2.5 py-[3px] text-[11px] font-bold text-white">{{ $session['quest']['checkpoints_count'] }} {{ __('general.stops') }}</span>
            @endif
        </div>

        {{-- Session Code Box --}}
        <div class="mt-4 rounded-[12px] bg-white/10 px-4 py-3">
            <p class="text-[10px] font-semibold text-white/50">{{ __('sessions.session_code') }}</p>
            <div class="mt-1 flex items-center justify-between">
                <p class="font-heading text-[24px] font-extrabold tracking-[4px] text-white">{{ $session['join_code'] ?? $code }}</p>
                <button
                    wire:click="shareSession"
                    class="rounded-[10px] bg-white/15 px-[14px] py-[8px] text-[13px] font-semibold text-white/70"
                    x-on:share-session.window="
                        if (navigator.share) {
                            navigator.share({ title: 'Join Quest', url: $event.detail.url });
                        } else {
                            navigator.clipboard.writeText($event.detail.url);
                        }
                    "
                >
                    {{ __('sessions.share') }}
                </button>
            </div>
        </div>

        {{-- QR Code --}}
        <div
            class="mt-3 flex justify-center"
            x-data
            x-init="
                if (window.QRCode) {
                    QRCode.toCanvas($refs.qr, @js($joinUrl), {
                        width: 160,
                        margin: 2,
                        color: { dark: '#0B3D2E', light: '#FFFFFF' },
                    });
                }
            "
        >
            <canvas x-ref="qr" class="rounded-xl"></canvas>
        </div>
    </div>

    {{-- Body --}}
    <div class="flex-1 bg-cream px-5 py-5">
        {{-- Players Section Header --}}
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-heading text-[15px] font-bold text-bark">{{ __('sessions.players') }}</h2>
            <div class="flex items-center gap-1.5">
                <div class="h-[8px] w-[8px] animate-pulse rounded-full bg-[#22C55E]"></div>
                <span class="text-[13px] font-semibold text-[#22C55E]">{{ count($participants) }} {{ __('sessions.joined') }}</span>
            </div>
        </div>

        {{-- Player Rows --}}
        <div>
            @php $avatarColors = ['#0B3D2E', '#7C4DFF', '#E85C3A', '#0EA5E9', '#F5A623', '#7C3AED']; @endphp

            {{-- Host --}}
            <div class="flex items-center gap-3 border-b border-cream-border py-3">
                <div class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-full bg-forest-600 text-[13px] font-bold text-white">
                    {{ strtoupper(substr($session['host']['name'] ?? 'Q', 0, 1)) }}
                </div>
                <p class="flex-1 text-[14px] font-semibold text-bark">{{ $session['host']['name'] ?? '' }}</p>
                <span class="rounded-full bg-[#D4EDE4] px-[8px] py-[2px] text-[10px] font-bold text-[#0A5A3A]">{{ __('sessions.quest_master') }}</span>
            </div>

            @foreach ($participants as $pIndex => $participant)
                <div class="flex items-center gap-3 border-b border-cream-border py-3 last:border-b-0" wire:key="participant-{{ $participant['id'] }}">
                    <div class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-full text-[13px] font-bold text-white" style="background: {{ $avatarColors[$pIndex % count($avatarColors)] }}">
                        {{ strtoupper(substr($participant['display_name'], 0, 1)) }}
                    </div>
                    <p class="text-[14px] font-semibold text-bark">{{ $participant['display_name'] }}</p>
                </div>
            @endforeach

            @if (empty($participants))
                <p class="py-6 text-center text-[13px] text-muted">{{ __('sessions.waiting_for_players') }}</p>
            @endif
        </div>

        {{-- Waiting Indicator (non-host) --}}
        @unless ($isHost)
            <div class="mt-6 flex flex-col items-center py-4">
                <div class="mb-2 flex items-center justify-center gap-1.5">
                    <div class="h-[8px] w-[8px] rounded-full bg-amber-400 opacity-40"></div>
                    <div class="h-[8px] w-[8px] rounded-full bg-amber-400 opacity-70"></div>
                    <div class="h-[8px] w-[8px] rounded-full bg-amber-400"></div>
                </div>
                <p class="text-[13px] text-muted">{{ __('sessions.waiting_for_host') }}</p>
            </div>
        @endunless
    </div>

    {{-- Host Start Button --}}
    @if ($isHost)
        <div class="border-t border-cream-border bg-cream px-5 pb-5 pt-4">
            <button
                wire:click="$dispatch('show-dialog', {
                    type: 'warning',
                    title: '{{ __('sessions.start_confirm_title') }}',
                    message: '{{ __('sessions.start_confirm_message') }}',
                    confirmLabel: '{{ __('sessions.start') }}',
                    cancelLabel: '{{ __('general.dismiss') }}',
                    confirmEvent: 'confirm-start-session'
                })"
                class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark hover:bg-amber-500"
                {{ count($participants) < 1 ? 'disabled' : '' }}
            >
                {{ __('sessions.start_quest') }}
            </button>
        </div>
    @endif
</div>

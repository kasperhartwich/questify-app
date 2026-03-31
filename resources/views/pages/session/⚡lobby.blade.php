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
        $this->shareUrl = url('/session/' . $this->code);
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

    public function getListeners(): array
    {
        return [
            "echo-presence:session.{$this->session->join_code},ParticipantJoined" => 'onParticipantJoined',
            "echo-presence:session.{$this->session->join_code},SessionStarted" => 'onSessionStarted',
        ];
    }
};
?>

<div class="flex flex-col">
    {{-- Forest Header --}}
    <div class="relative overflow-hidden bg-forest-600 px-4 py-5 text-white">
        <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[100px] w-[100px] rounded-full border-[18px] border-amber-400/10"></div>
        <p class="text-xs font-bold uppercase tracking-widest text-amber-400">{{ __('sessions.lobby') }}</p>
        <h1 class="mt-1 font-heading text-lg font-bold leading-tight">{{ $session->quest?->title ?? __('sessions.session') }}</h1>

        {{-- Session Code --}}
        <div class="mt-3 rounded-xl bg-white/10 px-4 py-2.5">
            <p class="text-[9px] font-semibold text-white/50">{{ __('sessions.join_code') }}</p>
            <div class="flex items-center justify-between">
                <p class="font-heading text-xl font-extrabold tracking-[3px]">{{ $session->join_code }}</p>
                <button
                    wire:click="shareSession"
                    class="rounded-lg bg-white/15 px-3 py-1.5 text-xs font-semibold text-white/70"
                    x-on:share-session.window="
                        if (navigator.share) {
                            navigator.share({ title: 'Join Quest', url: $event.detail.url });
                        } else {
                            navigator.clipboard.writeText($event.detail.url);
                        }
                    "
                >
                    {{ __('sessions.share_link') }}
                </button>
            </div>
        </div>
    </div>

    <div class="flex-1 space-y-4 p-4">
        {{-- Participants List --}}
        <div>
            <div class="mb-2 flex items-center justify-between px-1">
                <h2 class="font-heading text-sm font-bold text-bark dark:text-white">{{ __('sessions.participants') }}</h2>
                <div class="flex items-center gap-1.5">
                    <div class="h-1.5 w-1.5 animate-pulse rounded-full bg-green-500"></div>
                    <span class="text-xs font-semibold text-green-500">{{ count($participants) }} {{ __('sessions.joined') }}</span>
                </div>
            </div>

            <div class="space-y-1">
                {{-- Host --}}
                <div class="flex items-center gap-3 border-b border-cream-border py-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-forest-600 text-xs font-bold text-white">QM</div>
                    <p class="flex-1 text-sm font-semibold text-bark dark:text-white">{{ $session->host?->name }}</p>
                    <span class="rounded-full bg-forest-50 px-2 py-0.5 text-[9px] font-bold text-forest-500 dark:bg-forest-900/30 dark:text-forest-400">{{ __('sessions.host') }}</span>
                </div>

                @foreach ($participants as $participant)
                    <div class="flex items-center gap-3 border-b border-cream-border py-2.5" wire:key="participant-{{ $participant['id'] }}">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-400 text-xs font-bold text-bark">
                            {{ strtoupper(substr($participant['display_name'], 0, 1)) }}
                        </div>
                        <p class="text-sm font-semibold text-bark dark:text-white">{{ $participant['display_name'] }}</p>
                    </div>
                @endforeach

                @if (empty($participants))
                    <p class="py-4 text-center text-sm text-muted dark:text-gray-500">{{ __('sessions.waiting') }}</p>
                @endif
            </div>
        </div>

        {{-- Waiting indicator --}}
        @unless ($isHost)
            <div class="py-4 text-center">
                <div class="mb-1 flex items-center justify-center gap-1.5">
                    <div class="h-1.5 w-1.5 rounded-full bg-amber-400 opacity-40"></div>
                    <div class="h-1.5 w-1.5 rounded-full bg-amber-400 opacity-70"></div>
                    <div class="h-1.5 w-1.5 rounded-full bg-amber-400"></div>
                </div>
                <p class="text-xs text-muted">{{ __('sessions.waiting') }}</p>
            </div>
        @endunless
    </div>

    {{-- Host Start Button --}}
    @if ($isHost)
        <div class="border-t border-cream-border bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <button
                wire:click="startSession"
                wire:confirm="{{ __('sessions.start_confirm') }}"
                class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark hover:bg-amber-500"
                {{ count($participants) < 1 && !$isHost ? 'disabled' : '' }}
            >
                {{ __('sessions.start') }}
            </button>
        </div>
    @endif
</div>

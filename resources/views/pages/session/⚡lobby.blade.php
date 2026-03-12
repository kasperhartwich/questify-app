<?php

use App\Enums\SessionStatus;
use App\Events\SessionStarted;
use App\Models\QuestSession;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Session Lobby')]
class extends Component
{
    public QuestSession $session;

    public array $participants = [];

    public bool $isHost = false;

    public string $shareUrl = '';

    public function mount(string $code): void
    {
        $this->session = QuestSession::where('join_code', $code)
            ->with(['quest:id,title,cover_image_path', 'host:id,name'])
            ->firstOrFail();

        $this->isHost = Auth::id() === $this->session->host_id;
        $this->shareUrl = url('/session/' . $this->session->join_code);
        $this->loadParticipants();
    }

    public function loadParticipants(): void
    {
        $this->participants = $this->session->participants()
            ->with('user:id,name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'display_name' => $p->display_name,
                'user_name' => $p->user?->name,
            ])
            ->toArray();
    }

    #[On('echo-presence:session.{session.join_code},ParticipantJoined')]
    public function onParticipantJoined(): void
    {
        $this->loadParticipants();
    }

    #[On('echo-presence:session.{session.join_code},SessionStarted')]
    public function onSessionStarted(): void
    {
        $this->redirect('/session/' . $this->session->join_code . '/play');
    }

    public function startSession(): void
    {
        if (! $this->isHost) {
            return;
        }

        $this->session->update([
            'status' => SessionStatus::InProgress,
            'started_at' => now(),
        ]);

        broadcast(new SessionStarted($this->session->fresh()));

        $this->redirect('/session/' . $this->session->join_code . '/host');
    }

    public function shareSession(): void
    {
        $this->dispatch('share-session', url: $this->shareUrl, code: $this->session->join_code);
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
    {{-- Quest Header --}}
    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 px-4 py-6 text-center text-white">
        <h1 class="text-xl font-bold">{{ $session->quest?->title ?? __('sessions.session') }}</h1>
        <p class="mt-1 text-sm opacity-80">{{ __('sessions.waiting') }}</p>
    </div>

    <div class="flex-1 space-y-4 p-4">
        {{-- Join Code & QR --}}
        <div class="rounded-xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('sessions.join_code') }}</p>
            <p class="font-mono text-4xl font-bold tracking-widest text-indigo-600 dark:text-indigo-400">{{ $session->join_code }}</p>

            {{-- QR Code (using inline SVG placeholder, JS renders actual QR) --}}
            <div
                class="mx-auto mt-4 h-40 w-40"
                x-data="{
                    init() {
                        if (typeof QRCode !== 'undefined') {
                            new QRCode(this.$el, {
                                text: '{{ $shareUrl }}',
                                width: 160,
                                height: 160,
                            });
                        } else {
                            this.$el.innerHTML = '<div class=\'flex h-full items-center justify-center rounded-lg bg-gray-100 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400\'>QR Code</div>';
                        }
                    }
                }"
            ></div>

            {{-- Share Link --}}
            <button
                wire:click="shareSession"
                class="mt-3 inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400"
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

        {{-- Participants List --}}
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <h2 class="mb-3 flex items-center justify-between font-semibold text-gray-900 dark:text-white">
                {{ __('sessions.participants') }}
                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">{{ count($participants) }}</span>
            </h2>

            <div class="space-y-2">
                {{-- Host --}}
                <div class="flex items-center gap-3 rounded-lg bg-indigo-50 px-3 py-2 dark:bg-indigo-900/20">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">H</div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $session->host?->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('sessions.host') }}</p>
                    </div>
                </div>

                @foreach ($participants as $participant)
                    <div class="flex items-center gap-3 rounded-lg px-3 py-2" wire:key="participant-{{ $participant['id'] }}">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            {{ strtoupper(substr($participant['display_name'], 0, 1)) }}
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $participant['display_name'] }}</p>
                    </div>
                @endforeach

                @if (empty($participants))
                    <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('sessions.waiting') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Host Start Button --}}
    @if ($isHost)
        <div class="border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <button
                wire:click="startSession"
                wire:confirm="{{ __('sessions.start_confirm') }}"
                class="w-full rounded-lg bg-green-600 px-4 py-3 text-center font-semibold text-white hover:bg-green-700"
                {{ count($participants) < 1 && !$isHost ? 'disabled' : '' }}
            >
                {{ __('sessions.start') }}
            </button>
        </div>
    @endif
</div>

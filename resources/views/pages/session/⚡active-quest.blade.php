<?php

use App\Enums\SessionStatus;
use App\Models\Checkpoint;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Active Quest')]
class extends Component
{
    public QuestSession $session;

    public SessionParticipant $participant;

    public ?Checkpoint $currentCheckpoint = null;

    public int $currentCheckpointIndex = 0;

    public array $checkpoints = [];

    public array $leaderboard = [];

    public bool $showHint = false;

    public bool $showQuestions = false;

    public function mount(string $code): void
    {
        $this->session = QuestSession::where('join_code', $code)
            ->with(['quest.checkpoints.questions.answers'])
            ->firstOrFail();

        $this->participant = $this->session->participants()
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $this->checkpoints = $this->session->quest->checkpoints
            ->map(fn ($cp) => [
                'id' => $cp->id,
                'title' => $cp->title,
                'description' => $cp->description,
                'latitude' => $cp->latitude,
                'longitude' => $cp->longitude,
            ])
            ->toArray();

        $this->determineCurrentCheckpoint();
        $this->loadLeaderboard();
    }

    public function determineCurrentCheckpoint(): void
    {
        $completedCheckpointIds = $this->participant->checkpointProgress()
            ->where('is_correct', true)
            ->pluck('checkpoint_id')
            ->unique()
            ->toArray();

        $questCheckpoints = $this->session->quest->checkpoints;

        foreach ($questCheckpoints as $index => $checkpoint) {
            $checkpointQuestionCount = $checkpoint->questions->count();
            $completedQuestionCount = $this->participant->checkpointProgress()
                ->where('checkpoint_id', $checkpoint->id)
                ->where('is_correct', true)
                ->count();

            if ($completedQuestionCount < $checkpointQuestionCount) {
                $this->currentCheckpoint = $checkpoint;
                $this->currentCheckpointIndex = $index;

                return;
            }
        }

        $this->redirect('/session/' . $this->session->join_code . '/complete');
    }

    public function arriveAtCheckpoint(): void
    {
        $this->showQuestions = true;
    }

    public function showHint(): void
    {
        $this->showHint = true;
    }

    public function goToQuestions(): void
    {
        if (! $this->currentCheckpoint) {
            return;
        }

        $this->redirect('/session/' . $this->session->join_code . '/question/' . $this->currentCheckpoint->id);
    }

    public function loadLeaderboard(): void
    {
        $this->leaderboard = $this->session->participants()
            ->orderByDesc('score')
            ->limit(5)
            ->get()
            ->map(fn ($p, $i) => [
                'rank' => $i + 1,
                'display_name' => $p->display_name,
                'score' => $p->score,
                'is_me' => $p->id === $this->participant->id,
            ])
            ->toArray();
    }

    #[On('echo-presence:session.{session.join_code},LeaderboardUpdated')]
    public function onLeaderboardUpdated(): void
    {
        $this->loadLeaderboard();
    }

    #[On('echo-presence:session.{session.join_code},SessionEnded')]
    public function onSessionEnded(): void
    {
        $this->redirect('/session/' . $this->session->join_code . '/complete');
    }

    public function getListeners(): array
    {
        return [
            "echo-presence:session.{$this->session->join_code},LeaderboardUpdated" => 'onLeaderboardUpdated',
            "echo-presence:session.{$this->session->join_code},SessionEnded" => 'onSessionEnded',
        ];
    }
};
?>

<div class="flex flex-col">
    {{-- Map View --}}
    <div
        class="relative h-64 w-full bg-gray-200 dark:bg-gray-700"
        x-data="{
            map: null,
            init() {
                if (typeof google === 'undefined') return;
                const checkpoints = @js($checkpoints);
                const current = checkpoints[{{ $currentCheckpointIndex }}];
                if (!current || !current.latitude) return;

                this.map = new google.maps.Map(this.$el, {
                    center: { lat: parseFloat(current.latitude), lng: parseFloat(current.longitude) },
                    zoom: 15,
                    mapTypeControl: false,
                    streetViewControl: false,
                });

                checkpoints.forEach((cp, i) => {
                    if (!cp.latitude || !cp.longitude) return;
                    new google.maps.Marker({
                        position: { lat: parseFloat(cp.latitude), lng: parseFloat(cp.longitude) },
                        map: this.map,
                        label: String(i + 1),
                        opacity: i === {{ $currentCheckpointIndex }} ? 1.0 : 0.4,
                    });
                });

                if (navigator.geolocation) {
                    navigator.geolocation.watchPosition((pos) => {
                        const userPos = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                        if (this.userMarker) {
                            this.userMarker.setPosition(userPos);
                        } else {
                            this.userMarker = new google.maps.Marker({
                                position: userPos,
                                map: this.map,
                                icon: {
                                    path: google.maps.SymbolPath.CIRCLE,
                                    scale: 8,
                                    fillColor: '#4f46e5',
                                    fillOpacity: 1,
                                    strokeWeight: 2,
                                    strokeColor: '#ffffff',
                                },
                            });
                        }
                    });
                }
            }
        }"
    >
        <div class="flex h-full items-center justify-center text-gray-500 dark:text-gray-400">
            {{ __('general.loading') }}
        </div>
    </div>

    <div class="flex-1 space-y-3 p-4">
        {{-- Current Checkpoint Info --}}
        @if ($currentCheckpoint)
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        {{ __('quests.checkpoint') }} {{ $currentCheckpointIndex + 1 }}/{{ count($checkpoints) }}
                    </span>
                </div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $currentCheckpoint->title }}</h2>
                @if ($currentCheckpoint->description)
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $currentCheckpoint->description }}</p>
                @endif

                {{-- Hint Button --}}
                @if (!$showHint && $currentCheckpoint->questions->first()?->hint)
                    <button wire:click="showHint" class="mt-2 text-sm text-amber-600 dark:text-amber-400">💡 {{ __('sessions.show_hint') }}</button>
                @endif
                @if ($showHint && $currentCheckpoint->questions->first()?->hint)
                    <div class="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-900/20 dark:text-amber-400">
                        💡 {{ $currentCheckpoint->questions->first()->hint }}
                    </div>
                @endif

                <button wire:click="goToQuestions" class="mt-3 w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    {{ __('sessions.answer_questions') }}
                </button>
            </div>
        @endif

        {{-- Leaderboard Strip --}}
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('sessions.leaderboard') }}</h3>
            <div class="space-y-1">
                @foreach ($leaderboard as $entry)
                    <div class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm {{ $entry['is_me'] ? 'bg-indigo-50 font-semibold dark:bg-indigo-900/20' : '' }}">
                        <span class="flex items-center gap-2">
                            <span class="w-5 text-center text-xs font-bold {{ $entry['rank'] <= 3 ? 'text-amber-500' : 'text-gray-400' }}">{{ $entry['rank'] }}</span>
                            <span class="text-gray-900 dark:text-white">{{ $entry['display_name'] }}</span>
                            @if ($entry['is_me'])
                                <span class="text-xs text-indigo-600 dark:text-indigo-400">({{ __('sessions.you') }})</span>
                            @endif
                        </span>
                        <span class="font-mono text-xs text-gray-600 dark:text-gray-400">{{ number_format($entry['score']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

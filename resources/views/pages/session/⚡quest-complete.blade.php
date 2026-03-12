<?php

use App\Models\QuestRating;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Quest Complete')]
class extends Component
{
    public QuestSession $session;

    public ?SessionParticipant $participant = null;

    public array $leaderboard = [];

    public int $myScore = 0;

    public int $myRank = 0;

    public int $ratingValue = 0;

    public string $ratingReview = '';

    public bool $hasRated = false;

    public function mount(string $code): void
    {
        $this->session = QuestSession::where('join_code', $code)
            ->with('quest:id,title,creator_id')
            ->firstOrFail();

        $this->participant = $this->session->participants()
            ->where('user_id', Auth::id())
            ->first();

        if ($this->participant) {
            $this->myScore = $this->participant->score;
        }

        $this->loadLeaderboard();

        $this->hasRated = QuestRating::where('quest_id', $this->session->quest_id)
            ->where('user_id', Auth::id())
            ->exists();
    }

    public function loadLeaderboard(): void
    {
        $participants = $this->session->participants()
            ->orderByDesc('score')
            ->get();

        $this->leaderboard = $participants
            ->map(fn ($p, $i) => [
                'rank' => $i + 1,
                'display_name' => $p->display_name,
                'score' => $p->score,
                'is_me' => $p->user_id === Auth::id(),
                'finished_at' => $p->finished_at?->diffForHumans(),
            ])
            ->toArray();

        $this->myRank = collect($this->leaderboard)->firstWhere('is_me')['rank'] ?? 0;
    }

    public function rateQuest(): void
    {
        if ($this->ratingValue < 1 || $this->ratingValue > 5 || $this->hasRated) {
            return;
        }

        QuestRating::create([
            'quest_id' => $this->session->quest_id,
            'user_id' => Auth::id(),
            'rating' => $this->ratingValue,
            'review' => $this->ratingReview ?: null,
        ]);

        $this->hasRated = true;
    }

    public function shareResult(): void
    {
        $this->dispatch('share-result', [
            'title' => __('sessions.share_result_title'),
            'text' => __('sessions.share_result_text', [
                'quest' => $this->session->quest->title,
                'score' => $this->myScore,
                'rank' => $this->myRank,
            ]),
        ]);
    }
};
?>

<div class="flex flex-col">
    {{-- Celebration Header --}}
    <div class="bg-gradient-to-br from-amber-400 via-orange-500 to-pink-500 px-4 py-8 text-center text-white">
        <p class="text-5xl">🏆</p>
        <h1 class="mt-2 text-2xl font-bold">{{ __('sessions.quest_complete') }}</h1>
        <p class="mt-1 text-lg opacity-90">{{ $session->quest?->title }}</p>
    </div>

    <div class="flex-1 space-y-4 p-4">
        {{-- Score Card --}}
        @if ($participant)
            <div class="rounded-xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('sessions.your_score') }}</p>
                <p class="text-4xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($myScore) }}</p>
                @if ($myRank > 0)
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('sessions.rank_of', ['rank' => $myRank, 'total' => count($leaderboard)]) }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Leaderboard --}}
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <h2 class="mb-3 font-semibold text-gray-900 dark:text-white">{{ __('sessions.leaderboard') }}</h2>
            <div class="space-y-2">
                @foreach ($leaderboard as $entry)
                    <div class="flex items-center justify-between rounded-lg px-3 py-2 {{ $entry['is_me'] ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                        <span class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-bold
                                {{ match($entry['rank']) { 1 => 'bg-amber-100 text-amber-700', 2 => 'bg-gray-200 text-gray-600', 3 => 'bg-orange-100 text-orange-700', default => 'bg-gray-100 text-gray-500' } }}">
                                {{ $entry['rank'] }}
                            </span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $entry['display_name'] }}
                                @if ($entry['is_me'])
                                    <span class="text-xs text-indigo-600 dark:text-indigo-400">({{ __('sessions.you') }})</span>
                                @endif
                            </span>
                        </span>
                        <span class="font-mono text-sm font-semibold text-gray-700 dark:text-gray-300">{{ number_format($entry['score']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Rate Quest --}}
        @if (!$hasRated)
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                <h2 class="mb-3 font-semibold text-gray-900 dark:text-white">{{ __('sessions.rate_quest') }}</h2>
                <div class="mb-3 flex justify-center gap-2">
                    @for ($i = 1; $i <= 5; $i++)
                        <button
                            wire:click="$set('ratingValue', {{ $i }})"
                            class="text-3xl transition-transform {{ $ratingValue >= $i ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }} hover:scale-110"
                        >
                            ★
                        </button>
                    @endfor
                </div>
                <textarea
                    wire:model="ratingReview"
                    rows="2"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    placeholder="{{ __('sessions.review_placeholder') }}"
                ></textarea>
                <button
                    wire:click="rateQuest"
                    class="mt-2 w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                    {{ $ratingValue < 1 ? 'disabled' : '' }}
                >
                    {{ __('sessions.submit_rating') }}
                </button>
            </div>
        @else
            <div class="rounded-xl bg-green-50 p-4 text-center dark:bg-green-900/20">
                <p class="text-sm text-green-700 dark:text-green-400">{{ __('sessions.thanks_rating') }}</p>
            </div>
        @endif

        {{-- Share & Home --}}
        <div class="flex gap-3 pb-4">
            <button
                wire:click="shareResult"
                class="flex-1 rounded-lg border border-indigo-600 px-4 py-3 font-semibold text-indigo-600 dark:border-indigo-400 dark:text-indigo-400"
                x-on:share-result.window="
                    if (navigator.share) {
                        navigator.share({ title: $event.detail[0].title, text: $event.detail[0].text });
                    }
                "
            >
                {{ __('sessions.share_result') }}
            </button>
            <a href="/discover/list" class="flex-1 rounded-lg bg-indigo-600 px-4 py-3 text-center font-semibold text-white hover:bg-indigo-700" wire:navigate>
                {{ __('general.discover') }}
            </a>
        </div>
    </div>
</div>

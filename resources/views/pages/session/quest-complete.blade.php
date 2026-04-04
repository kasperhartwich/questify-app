<?php

use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Quest Complete')]
#[\Livewire\Attributes\Layout('layouts.app', ['bodyClass' => 'bg-forest-600'])]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    public string $code = '';

    public array $session = [];

    public array $leaderboard = [];

    public int $myScore = 0;

    public int $myRank = 0;

    public int $questId = 0;

    public string $questTitle = '';

    public int $ratingValue = 0;

    public string $ratingComment = '';

    public bool $hasRated = false;

    public function mount(string $code): void
    {
        $this->code = $code;
        $participantId = session('questify_participant_id', 0);

        $response = $this->tryApiCall(fn () => $this->api->sessions()->show($code));
        $this->session = $response['data'] ?? [];
        $this->questId = $this->session['quest']['id'] ?? 0;
        $this->questTitle = $this->session['quest']['title'] ?? '';

        $this->loadLeaderboard($participantId);
    }

    public function loadLeaderboard(int $participantId = 0): void
    {
        $response = $this->tryApiCall(fn () => $this->api->gameplay()->leaderboard($this->code));
        $isTeamMode = ($this->session['play_mode'] ?? '') === 'competitive_teams';

        if ($isTeamMode) {
            $myDisplayName = session('questify_display_name', '');

            $this->leaderboard = collect($response['data'] ?? [])
                ->map(fn ($team, $i) => [
                    'rank' => $i + 1,
                    'display_name' => $team['team_name'],
                    'score' => $team['score'],
                    'member_count' => $team['member_count'] ?? 1,
                    'members' => $team['members'] ?? [],
                    'is_me' => $team['team_name'] === $myDisplayName,
                ])
                ->toArray();
        } else {
            $this->leaderboard = collect($response['data'] ?? [])
                ->map(fn ($p, $i) => [
                    'rank' => $i + 1,
                    'display_name' => $p['display_name'],
                    'score' => $p['total_score'],
                    'is_me' => $p['id'] === $participantId,
                ])
                ->toArray();
        }

        $me = collect($this->leaderboard)->firstWhere('is_me');
        $this->myScore = $me['score'] ?? 0;
        $this->myRank = $me['rank'] ?? 0;
    }

    public function rateQuest(): void
    {
        if ($this->ratingValue < 1 || $this->ratingValue > 5 || $this->hasRated) {
            return;
        }

        $this->tryApiCall(fn () => $this->api->quests()->rate(
            $this->questId,
            $this->ratingValue,
            $this->ratingComment ?: null,
        ));

        $this->hasRated = true;
    }

    public function shareResult(): void
    {
        $this->dispatch('share-result', [
            'title' => __('sessions.share_result_title'),
            'text' => __('sessions.share_result_text', [
                'quest' => $this->questTitle,
                'score' => $this->myScore,
                'rank' => $this->myRank,
            ]),
        ]);
    }
};
?>

<div class="flex flex-col">
    {{-- Celebration Header --}}
    <div class="relative overflow-hidden bg-forest-600 px-4 py-8 text-center text-white">
        <div class="pointer-events-none absolute right-[-40px] top-[-40px] h-[160px] w-[160px] rounded-full border-[28px] border-amber-400/10"></div>
        <p class="text-5xl">🏆</p>
        <h1 class="mt-2 font-heading text-2xl font-extrabold">{{ __('sessions.quest_complete') }}</h1>
        <p class="mt-1 text-lg text-white/80">{{ $questTitle }}</p>
    </div>

    <div class="flex-1 space-y-4 p-4">
        {{-- Score Card --}}
        @if ($myScore > 0 || $myRank > 0)
            <div class="rounded-xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('sessions.your_score') }}</p>
                <p class="text-4xl font-bold text-forest-600 dark:text-forest-400">{{ number_format($myScore) }}</p>
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
                    <div>
                        <div class="flex items-center justify-between rounded-lg px-3 py-2 {{ $entry['is_me'] ? 'bg-forest-50 dark:bg-forest-900/20' : '' }}">
                            <span class="flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-bold
                                    {{ match($entry['rank']) { 1 => 'bg-amber-100 text-amber-700', 2 => 'bg-gray-200 text-gray-600', 3 => 'bg-orange-100 text-orange-700', default => 'bg-gray-100 text-gray-500' } }}">
                                    {{ $entry['rank'] }}
                                </span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $entry['display_name'] }}
                                    @if ($entry['is_me'])
                                        <span class="text-xs text-forest-600 dark:text-forest-400">({{ __('sessions.you') }})</span>
                                    @endif
                                    @if (!empty($entry['member_count']) && $entry['member_count'] > 1)
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $entry['member_count'] }} {{ __('sessions.members') }}</span>
                                    @endif
                                </span>
                            </span>
                            <span class="font-mono text-sm font-semibold text-gray-700 dark:text-gray-300">{{ number_format($entry['score']) }}</span>
                        </div>
                        @if (!empty($entry['members']))
                            <div class="ml-12 mt-1 space-y-0.5">
                                @foreach ($entry['members'] as $member)
                                    <div class="flex items-center justify-between px-2 py-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ $member['display_name'] }}</span>
                                        <span class="font-mono">{{ number_format($member['score']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
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
                    class="mt-2 w-full rounded-xl bg-amber-400 px-4 py-2.5 font-heading text-sm font-bold text-bark hover:bg-amber-500 disabled:opacity-50"
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
                class="flex-1 rounded-xl border-[1.5px] border-cream-border px-4 py-3.5 text-sm font-semibold text-bark dark:border-gray-600 dark:text-gray-300"
                x-on:share-result.window="
                    if (navigator.share) {
                        navigator.share({ title: $event.detail[0].title, text: $event.detail[0].text });
                    }
                "
            >
                {{ __('sessions.share_result') }}
            </button>
            <a href="/discover/list" class="flex-1 rounded-xl bg-amber-400 px-4 py-3.5 text-center font-heading text-sm font-bold text-bark hover:bg-amber-500" wire:navigate>
                {{ __('general.discover') }}
            </a>
        </div>
    </div>
</div>

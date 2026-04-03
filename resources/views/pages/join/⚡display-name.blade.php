<?php

use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Join a Quest')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    public string $code = '';

    public string $displayName = '';

    public ?object $session = null;

    public function mount(string $code): void
    {
        $this->code = strtoupper($code);

        $response = $this->tryApiCall(fn () => $this->api->sessions()->show($this->code));

        if ($response) {
            $this->session = $this->toObject($response['data']);
        }
    }

    public function join(): void
    {
        $this->validate([
            'displayName' => ['required', 'string', 'min:2', 'max:30'],
        ]);

        $userId = auth()->id();

        $response = $this->tryApiCall(fn () => $this->api->sessions()->join(
            $this->code,
            $this->displayName,
            $userId,
        ));

        if ($response) {
            session()->put('questify_participant_id', $response['data']['id'] ?? $response['data']['participant_id'] ?? null);
            session()->put('questify_display_name', $this->displayName);
            $this->redirect('/session/' . $this->code);
        }
    }
};
?>

<div class="flex min-h-screen flex-col bg-cream">
    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pb-3 pt-4">
        <a href="/join" class="flex h-[36px] w-[36px] items-center justify-center rounded-[11px] bg-cream-dark" wire:navigate>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
    </div>

    <div class="flex flex-1 flex-col px-4">
        {{-- Quest preview card --}}
        @if ($session)
            <div class="relative mb-5 overflow-hidden rounded-[14px] bg-forest-600 p-[14px_16px]">
                <div class="pointer-events-none absolute right-[-10px] top-[-10px] h-[60px] w-[60px] rounded-full border-[10px] border-amber-400/15"></div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-amber-400">{{ __('sessions.joining') }}</p>
                <h2 class="mt-1 font-heading text-[15px] font-bold text-white">{{ $session->quest?->title ?? '' }}</h2>
                <div class="mt-2 flex gap-1.5">
                    @if ($session->quest?->checkpoints_count ?? null)
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-bold text-amber-700">{{ $session->quest->checkpoints_count }} {{ __('general.stops') }}</span>
                    @endif
                </div>
            </div>
        @endif

        {{-- Heading --}}
        @php $isTeamMode = ($session->play_mode ?? '') === 'competitive_teams'; @endphp
        <h1 class="mb-1.5 font-heading text-[22px] font-extrabold leading-tight text-bark">{{ $isTeamMode ? __('sessions.what_team_name') : __('sessions.what_should_we_call_you') }}</h1>
        <p class="mb-5 text-[13px] text-muted">{{ $isTeamMode ? __('sessions.team_name_visible') : __('sessions.display_name_visible') }}</p>

        {{-- Display name input --}}
        <form wire:submit="join" class="flex flex-1 flex-col">
            <div class="relative mb-3">
                <input
                    type="text"
                    wire:model="displayName"
                    placeholder="{{ $isTeamMode ? __('sessions.your_team_name') : __('sessions.your_display_name') }}"
                    class="w-full rounded-xl border-2 border-cream-border bg-white px-3.5 py-3 pr-11 text-[14px] font-semibold text-bark focus:border-forest-600 focus:outline-none"
                    required
                />
                @if (strlen($displayName) >= 2)
                    <div class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                @endif
            </div>
            @error('displayName') <p class="mb-2 text-[10px] text-coral">{{ $message }}</p> @enderror
            <p class="mb-5 text-center text-[11px] text-muted">{{ __('sessions.no_account_just_session') }}</p>

            <div class="mt-auto pb-2">
                <button type="submit" class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark">
                    {{ __('sessions.enter_the_quest') }}
                </button>
            </div>
        </form>
    </div>
</div>

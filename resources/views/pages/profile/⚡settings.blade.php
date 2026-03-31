<?php

use App\Auth\QuestifyApiGuard;
use App\Enums\SocialProvider;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Title('Profile')]
class extends Component
{
    use HandlesApiErrors, WithApiClient, WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $locale = 'en';

    public $avatar = null;

    public bool $notifications_enabled = true;

    public bool $showSettings = false;

    /** @var array<string, mixed> */
    public array $stats = [];

    /** @var array<int, mixed> */
    public array $recentActivity = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->locale = $user->locale ?? 'en';

        $meResponse = $this->tryApiCall(fn () => $this->api->auth()->me());
        if ($meResponse) {
            $data = $meResponse['data'] ?? [];
            $this->stats = [
                'quests_played' => $data['quests_played_count'] ?? 0,
                'quests_created' => $data['quests_created_count'] ?? 0,
                'total_points' => $data['total_points'] ?? 0,
            ];
            $this->recentActivity = $data['recent_activity'] ?? [];
        }
    }

    public function updateProfile(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'in:en,da'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $avatarPath = null;
        if ($this->avatar) {
            $avatarPath = $this->avatar->getRealPath();
        }

        $this->tryApiCall(fn () => $this->api->user()->updateProfile([
            'name' => $validated['name'],
            'locale' => $validated['locale'],
        ], $avatarPath));

        // Update session user data
        $meResponse = $this->tryApiCall(fn () => $this->api->auth()->me());
        if ($meResponse) {
            session()->put('questify_user', $meResponse['data']);
        }

        app()->setLocale($validated['locale']);

        session()->flash('message', __('general.profile_updated'));
    }

    public function logout(): void
    {
        /** @var QuestifyApiGuard $guard */
        $guard = Auth::guard();
        $guard->logout();

        $this->redirect('/');
    }

    public function deleteAccount(): void
    {
        $this->tryApiCall(fn () => $this->api->user()->deleteAccount());

        /** @var QuestifyApiGuard $guard */
        $guard = Auth::guard();
        $guard->logout();

        $this->redirect('/');
    }

    /**
     * @return array<string, bool>
     */
    public function getLinkedAccountsProperty(): array
    {
        $accounts = [];
        foreach (SocialProvider::cases() as $provider) {
            $accounts[$provider->value] = false;
        }

        return $accounts;
    }
};
?>

<div class="flex flex-col">
    {{-- Profile Header --}}
    <div class="relative overflow-hidden bg-forest-600 px-4 pb-8 pt-4">
        <div class="pointer-events-none absolute right-[-30px] top-[-30px] h-[120px] w-[120px] rounded-full border-[20px] border-amber-400/10"></div>
        <div class="relative z-10 flex items-center gap-3">
            <div class="flex h-[52px] w-[52px] items-center justify-center overflow-hidden rounded-full border-[3px] border-white/20 bg-amber-400">
                @if (Auth::user()->avatarUrl)
                    <img src="{{ Auth::user()->avatarUrl }}" alt="" class="h-full w-full object-cover" />
                @else
                    <span class="font-heading text-xl font-extrabold text-bark">{{ substr($name, 0, 1) }}</span>
                @endif
            </div>
            <div>
                <h1 class="font-heading text-base font-bold text-white">{{ $name }}</h1>
                <p class="text-[10px] text-white/55">Quest Master · Member since {{ Auth::user()->createdAt ? \Carbon\Carbon::parse(Auth::user()->createdAt)->year : now()->year }}</p>
            </div>
        </div>
    </div>

    {{-- Stats Row (overlapping header) --}}
    <div class="-mt-4 relative z-10">
        <x-stat-row :stats="[
            ['value' => $stats['quests_played'] ?? 0, 'label' => __('general.quests_played') ?? 'Quests Played'],
            ['value' => $stats['quests_created'] ?? 0, 'label' => __('general.created') ?? 'Created'],
            ['value' => number_format($stats['total_points'] ?? 0), 'label' => __('general.total_points') ?? 'Total Points'],
        ]" />
    </div>

    {{-- Flash Messages --}}
    @if (session('message'))
        <div class="mx-3.5 mt-3 rounded-xl bg-forest-50 p-3 text-sm font-medium text-forest-600">
            {{ session('message') }}
        </div>
    @endif

    {{-- Recent Activity --}}
    <div class="px-4 pb-2 pt-3.5">
        <h2 class="font-heading text-[13px] font-bold text-bark">{{ __('general.recent_activity') ?? 'Recent Activity' }}</h2>
    </div>

    <div class="flex flex-col gap-2 px-3.5 pb-3">
        @forelse ($recentActivity as $activity)
            <div class="flex items-center gap-2.5 rounded-xl bg-white p-3">
                <div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-[10px] {{ ($activity['type'] ?? '') === 'completed' ? 'bg-[#D4EDE4]' : (($activity['type'] ?? '') === 'published' ? 'bg-amber-100' : 'bg-[#F3E8FF]') }}">
                    @if (($activity['type'] ?? '') === 'completed')
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                    @elseif (($activity['type'] ?? '') === 'published')
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#C8811A" stroke-width="2.5" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                    @else
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.5" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-semibold text-bark">{{ $activity['title'] ?? '' }}</p>
                    <p class="text-[9px] text-muted">{{ $activity['subtitle'] ?? '' }}</p>
                </div>
            </div>
        @empty
            {{-- Placeholder activities when API doesn't return them --}}
            <div class="flex items-center gap-2.5 rounded-xl bg-white p-3">
                <div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-[10px] bg-[#D4EDE4]">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-semibold text-muted">{{ __('general.no_recent_activity') ?? 'No recent activity yet' }}</p>
                    <p class="text-[9px] text-muted">{{ __('general.start_playing') ?? 'Start playing quests to see your activity here' }}</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Settings / Logout card --}}
    <div class="mx-3.5 mb-4 overflow-hidden rounded-[14px] bg-white">
        <button wire:click="$toggle('showSettings')" class="flex w-full items-center gap-2.5 border-b border-cream-border p-3 text-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
            <span class="flex-1 text-xs font-medium text-bark">{{ __('general.settings') ?? 'Settings' }}</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
        </button>
        <button wire:click="logout" class="flex w-full items-center gap-2.5 p-3 text-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E85C3A" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            <span class="flex-1 text-xs font-medium text-coral">{{ __('general.logout') }}</span>
        </button>
    </div>

    {{-- Settings panel (expandable) --}}
    @if ($showSettings)
        <div class="space-y-3 px-3.5 pb-6">
            <form wire:submit="updateProfile" class="space-y-3 rounded-[14px] bg-white p-4">
                {{-- Avatar Upload --}}
                <div>
                    <input type="file" wire:model="avatar" accept="image/*" class="text-sm text-muted" />
                    @error('avatar') <p class="mt-1 text-sm text-coral">{{ $message }}</p> @enderror
                </div>

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-[9px] font-bold uppercase tracking-wider text-muted">{{ __('general.name') }}</label>
                    <input id="name" type="text" wire:model="name" class="mt-1 w-full rounded-xl border-2 border-cream-border bg-white px-3.5 py-2.5 text-[13px] text-bark focus:border-forest-600 focus:outline-none" required />
                    @error('name') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                </div>

                {{-- Language Selector --}}
                <div>
                    <label for="locale" class="block text-[9px] font-bold uppercase tracking-wider text-muted">{{ __('general.language') }}</label>
                    <select id="locale" wire:model="locale" class="mt-1 w-full rounded-xl border-2 border-cream-border bg-white px-3.5 py-2.5 text-[13px] text-bark focus:border-forest-600 focus:outline-none">
                        <option value="en">{{ __('general.english') }}</option>
                        <option value="da">{{ __('general.danish') }}</option>
                    </select>
                </div>

                <button type="submit" class="w-full rounded-xl bg-amber-400 px-4 py-3 font-heading text-sm font-bold text-bark">
                    {{ __('general.save') }}
                </button>
            </form>

            {{-- Linked Accounts --}}
            <div class="rounded-[14px] bg-white p-4">
                <h3 class="mb-2 text-[9px] font-bold uppercase tracking-wider text-muted">{{ __('general.linked_accounts') }}</h3>
                <div class="space-y-2">
                    @foreach ($this->linkedAccounts as $provider => $isLinked)
                        <div class="flex items-center justify-between rounded-xl border-[1.5px] border-cream-border p-2.5">
                            <span class="text-xs font-medium text-bark">{{ ucfirst($provider) }}</span>
                            @if ($isLinked)
                                <span class="text-[10px] font-semibold text-forest-500">{{ __('general.linked') ?? 'Linked' }}</span>
                            @else
                                <a href="/auth/{{ $provider }}/redirect" class="text-[10px] font-semibold text-forest-600">Connect</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Delete Account --}}
            <button wire:click="deleteAccount" wire:confirm="{{ __('general.delete_account_confirm') }}" class="w-full rounded-[14px] bg-white p-3 text-center text-xs font-medium text-red-600">
                {{ __('general.delete_account') }}
            </button>
        </div>
    @endif
</div>

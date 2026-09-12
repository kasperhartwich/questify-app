<?php

use App\Auth\QuestifyApiGuard;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\RequestsLocation;
use App\Livewire\Concerns\WithApiClient;
use App\Services\AppInfoService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Native\Mobile\Facades\PushNotifications;

new
#[Title('Profile')]
#[\Livewire\Attributes\Layout('layouts.app', self::LAYOUT_PARAMS)]
class extends Component
{
    use HandlesApiErrors, RequestsLocation, WithApiClient, WithFileUploads;

    /**
     * Kept as a constant so the `#[Layout]` attribute contains no inline array —
     * Livewire's single-file-component detector rejects `]` between `new` and `class`.
     *
     * @var array<string, string|bool>
     */
    public const LAYOUT_PARAMS = ['bodyClass' => 'bg-forest-600', 'skipSafeAreaTop' => true];

    public string $name = '';

    public string $email = '';

    public string $locale = 'en';

    public $avatar = null;

    public bool $notifications_enabled = true;

    public bool $email_notifications_enabled = true;

    public bool $show_on_leaderboard = true;

    public bool $showSettings = false;

    /** 'granted' | 'denied' | 'not_determined' | 'permanently_denied' | 'unknown' */
    public string $locationPermission = 'unknown';

    public string $pushPermission = 'unknown';

    /** @var array<string, mixed> */
    public array $stats = [];

    /** @var array<int, mixed> */
    public array $recentActivity = [];

    public function mount(): void
    {
        // /profile?settings=1 opens the settings panel directly, so links that
        // point at a specific setting (e.g. language) land where they promise.
        $this->showSettings = request()->boolean('settings');

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
            $this->notifications_enabled = (bool) ($data['notifications_enabled'] ?? true);
            $this->email_notifications_enabled = (bool) ($data['email_notifications_enabled'] ?? true);
        }
    }

    /** Persist the notification preference toggles to the backend. */
    private function persistNotificationPreferences(): void
    {
        $this->tryApiCall(fn () => $this->api->user()->updateProfile([
            'notifications_enabled' => $this->notifications_enabled,
            'email_notifications_enabled' => $this->email_notifications_enabled,
        ]));
    }

    public function toggleEmailNotifications(): void
    {
        $this->email_notifications_enabled = ! $this->email_notifications_enabled;
        $this->persistNotificationPreferences();
    }

    /** Auto-save a newly picked avatar (the file input has no separate save button). */
    public function updatedAvatar(): void
    {
        if ($this->avatar) {
            $this->updateProfile();
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
        $avatarName = null;
        if ($this->avatar) {
            $avatarPath = $this->avatar->getRealPath();
            $avatarName = $this->avatar->getClientOriginalName();
        }

        $this->tryApiCall(fn () => $this->api->user()->updateProfile([
            'name' => $validated['name'],
            'locale' => $validated['locale'],
        ], $avatarPath, $avatarName));

        // Update session user data
        $meResponse = $this->tryApiCall(fn () => $this->api->auth()->me());
        if ($meResponse) {
            session()->put('questify_user', $meResponse['data']);
        }

        app()->setLocale($validated['locale']);

        session()->flash('message', __('general.profile_updated'));
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        $this->updateProfile();
    }

    public function logout(): void
    {
        /** @var QuestifyApiGuard $guard */
        $guard = Auth::guard();
        $guard->logout();

        $this->redirect('/');
    }

    #[On('confirm-delete-account')]
    public function deleteAccount(): void
    {
        $this->tryApiCall(fn () => $this->api->user()->deleteAccount());

        /** @var QuestifyApiGuard $guard */
        $guard = Auth::guard();
        $guard->logout();

        $this->redirect('/');
    }

    /**
     * Read the OS-level answers so the rows show what the device actually
     * allows, not just what the account prefers.
     */
    public function refreshPermissionStates(): void
    {
        try {
            $this->pushPermission = \Native\Mobile\Facades\PushNotifications::checkPermission() ?: 'unknown';
        } catch (\Throwable) {
            $this->pushPermission = 'unknown';
        }

        try {
            \Native\Mobile\Facades\Geolocation::checkPermissions()->get();
        } catch (\Throwable) {
            // Not on a device — leave it unknown.
        }
    }

    /** Ask for location access from the permissions row. */
    public function requestLocationPermission(): void
    {
        $this->requestLocation();
    }

    /** Ask for push access; enroll() is what surfaces the OS prompt. */
    public function requestPushPermission(): void
    {
        try {
            \Native\Mobile\Facades\PushNotifications::enroll();
        } catch (\Throwable) {
            // Not on a device.
        }
    }

    public function togglePushNotifications(): void
    {
        $this->notifications_enabled = ! $this->notifications_enabled;

        if ($this->notifications_enabled) {
            try {
                PushNotifications::enroll();
            } catch (\Throwable) {
                // Not running on native device
            }
        } else {
            $fcmToken = session('questify_fcm_token');
            if ($fcmToken) {
                $this->tryApiCall(fn () => $this->api->user()->deleteFcmToken($fcmToken));
                session()->forget('questify_fcm_token');
            }
        }

        $this->persistNotificationPreferences();
    }

    /**
     * @return array<string, bool>
     */
    public function getLinkedAccountsProperty(): array
    {
        $linked = Auth::user()->linkedProviders ?? [];

        $accounts = [];
        foreach (app(AppInfoService::class)->enabledSocialProviders() as $provider) {
            $accounts[$provider] = in_array($provider, $linked, true);
        }

        // A provider the backend no longer offers can still be linked — the user
        // must be able to see and disconnect it.
        foreach ($linked as $provider) {
            $accounts[$provider] ??= true;
        }

        return $accounts;
    }
};
?>

<div class="flex min-h-screen flex-col bg-forest-600" wire:init="refreshPermissionStates">
    {{-- Profile View --}}
    @if (! $showSettings)
        {{-- Profile Header --}}
        <div class="relative overflow-hidden bg-forest-600 px-[16px] pb-[34px]" style="padding-top: calc(env(safe-area-inset-top, 0px) + 16px);">
            {{-- Decorative amber circle --}}
            <div class="pointer-events-none absolute right-[-24px] top-[-24px] h-[120px] w-[120px] rounded-full border-[22px]" style="border-color: rgba(245,166,35,0.1);"></div>

            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    {{-- Avatar --}}
                    <div class="flex h-[60px] w-[60px] items-center justify-center overflow-hidden rounded-full bg-amber-400" style="border: 2.5px solid rgba(255,255,255,0.2);">
                        @if (Auth::user()->avatarUrl)
                            <img src="{{ Auth::user()->avatarUrl }}" alt="" class="h-full w-full object-cover" />
                        @else
                            <span class="font-heading text-[24px] font-extrabold text-bark">{{ substr($name, 0, 1) }}</span>
                        @endif
                    </div>
                    <div>
                        <h1 class="font-heading text-[20px] font-bold text-white">{{ $name }}</h1>
                        <p class="text-[12px]" style="color: rgba(255,255,255,0.55);">{{ __('general.quest_master_since', ['year' => Auth::user()->createdAt ? \Carbon\Carbon::parse(Auth::user()->createdAt)->year : now()->year]) }}</p>
                    </div>
                </div>

                {{-- Gear icon button --}}
                <button wire:click="$toggle('showSettings')" class="flex h-[36px] w-[36px] items-center justify-center rounded-[10px]" style="background: rgba(255,255,255,0.12);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12.22 2h-.44a2 2 0 00-2 2v.18a2 2 0 01-1 1.73l-.43.25a2 2 0 01-2 0l-.15-.08a2 2 0 00-2.73.73l-.22.38a2 2 0 00.73 2.73l.15.1a2 2 0 011 1.72v.51a2 2 0 01-1 1.74l-.15.09a2 2 0 00-.73 2.73l.22.38a2 2 0 002.73.73l.15-.08a2 2 0 012 0l.43.25a2 2 0 011 1.73V20a2 2 0 002 2h.44a2 2 0 002-2v-.18a2 2 0 011-1.73l.43-.25a2 2 0 012 0l.15.08a2 2 0 002.73-.73l.22-.39a2 2 0 00-.73-2.73l-.15-.08a2 2 0 01-1-1.74v-.5a2 2 0 011-1.74l.15-.09a2 2 0 00.73-2.73l-.22-.38a2 2 0 00-2.73-.73l-.15.08a2 2 0 01-2 0l-.43-.25a2 2 0 01-1-1.73V4a2 2 0 00-2-2z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Content area with cream background --}}
        <div class="flex-1 bg-cream">
        {{-- Stats Row (overlapping header) --}}
        <div class="-mt-[18px] relative z-10">
            <x-stat-row :stats="[
                ['value' => $stats['quests_played'] ?? 0, 'label' => __('general.played')],
                ['value' => $stats['quests_created'] ?? 0, 'label' => __('general.created')],
                ['value' => number_format($stats['total_points'] ?? 0), 'label' => __('general.points')],
            ]" />
        </div>

        {{-- Flash Messages --}}
        @if (session('message'))
            <div class="mx-[16px] mt-3 rounded-xl bg-[#D4EDE4] p-3 text-sm font-medium text-forest-600">
                {{ session('message') }}
            </div>
        @endif

        {{-- Recent Activity --}}
        <div class="px-[16px] pb-2 pt-[18px]">
            <h2 class="font-heading text-[16px] font-bold text-bark">{{ __('general.recent_activity') }}</h2>
        </div>

        <div class="flex flex-col gap-2.5 px-[16px] pb-4">
            @forelse ($recentActivity as $activity)
                <div class="flex items-center gap-3 rounded-[14px] bg-white p-[13px_16px]">
                    <div class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-[11px] {{ ($activity['type'] ?? '') === 'completed' ? 'bg-[#D4EDE4]' : (($activity['type'] ?? '') === 'published' ? 'bg-amber-100' : 'bg-[#F3E8FF]') }}">
                        @if (($activity['type'] ?? '') === 'completed')
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                        @elseif (($activity['type'] ?? '') === 'published')
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#C8811A" stroke-width="2.5" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                        @else
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.5" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-semibold text-bark">{{ $activity['title'] ?? '' }}</p>
                        <p class="mt-0.5 text-[11px] text-muted">{{ $activity['subtitle'] ?? '' }}</p>
                    </div>
                </div>
            @empty
                {{-- Placeholder activities --}}
                <div class="flex items-center gap-3 rounded-[14px] bg-white p-[13px_16px]">
                    <div class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-[11px] bg-[#D4EDE4]">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-semibold text-muted">{{ __('general.no_recent_activity') }}</p>
                        <p class="mt-0.5 text-[11px] text-muted">{{ __('general.start_playing') }}</p>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Log Out Button --}}
        <div class="px-[16px] pb-6">
            <button wire:click="logout" class="flex w-full items-center gap-3 rounded-[14px] bg-white p-[14px_16px]" style="border: 1.5px solid #E5DDD0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#A0937D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span class="flex-1 text-left text-[14px] font-semibold text-muted">{{ __('general.logout') }}</span>
            </button>
        </div>
        </div>{{-- end cream content area --}}

    @else
        {{-- Settings Screen --}}
        <div class="flex flex-1 flex-col bg-cream">
            {{-- Settings Header --}}
            <div class="flex items-center gap-3 px-[16px] pb-[12px]" style="padding-top: calc(env(safe-area-inset-top, 0px) + 16px);">
                <button wire:click="$toggle('showSettings')" class="flex h-[36px] w-[36px] items-center justify-center rounded-[10px] bg-cream-dark">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2C1810" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </button>
                <h1 class="font-heading text-[20px] font-extrabold text-bark">{{ __('general.settings') }}</h1>
            </div>

            <div class="space-y-[20px] px-[16px] pb-8">

                {{-- Avatar with a visible "Change photo" trigger for the hidden file input --}}
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 overflow-hidden rounded-full bg-forest-100">
                        @if (Auth::user()->avatarUrl)
                            <img src="{{ Auth::user()->avatarUrl }}" alt="" class="h-full w-full object-cover" />
                        @else
                            <div class="flex h-full w-full items-center justify-center font-heading text-[22px] font-bold text-forest-600">{{ strtoupper(substr($name, 0, 1)) }}</div>
                        @endif
                    </div>
                    <label for="avatar-upload" class="cursor-pointer rounded-[10px] bg-cream-dark px-4 py-2 text-[13px] font-semibold text-bark">
                        {{ __('general.change_photo') }}
                    </label>
                    <span wire:loading wire:target="avatar" class="text-[12px] text-muted">…</span>
                </div>
                <input type="file" wire:model="avatar" accept="image/*" class="hidden" id="avatar-upload" />
                @error('avatar') <p class="text-sm text-coral">{{ $message }}</p> @enderror

                {{-- Language Section --}}
                <div>
                    <p class="mb-[8px] px-[16px] text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.language') }}</p>
                    <div class="overflow-hidden rounded-[14px] bg-white" style="border: 1.5px solid #E5DDD0;">
                        {{-- Danish --}}
                        <button wire:click="setLocale('da')" class="flex w-full items-center gap-3 px-[16px] py-[13px] text-left border-b" style="border-color: #E5DDD0;">
                            <span class="text-[20px]">🇩🇰</span>
                            <span class="flex-1 text-[14px] font-semibold {{ $locale === 'da' ? 'text-bark' : 'text-muted' }}">Dansk</span>
                            @if ($locale === 'da')
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                            @else
                                <div class="h-[20px] w-[20px] rounded-full" style="border: 1.5px solid #E5DDD0;"></div>
                            @endif
                        </button>
                        {{-- English --}}
                        <button wire:click="setLocale('en')" class="flex w-full items-center gap-3 px-[16px] py-[13px] text-left">
                            <span class="text-[20px]">🇬🇧</span>
                            <span class="flex-1 text-[14px] font-semibold {{ $locale === 'en' ? 'text-bark' : 'text-muted' }}">English</span>
                            @if ($locale === 'en')
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                            @else
                                <div class="h-[20px] w-[20px] rounded-full" style="border: 1.5px solid #E5DDD0;"></div>
                            @endif
                        </button>
                    </div>
                </div>

                {{-- Connected Accounts Section --}}
                <div>
                    <p class="mb-[8px] px-[16px] text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.connected_accounts') }}</p>
                    <div class="overflow-hidden rounded-[14px] bg-white" style="border: 1.5px solid #E5DDD0;">
                        @foreach ($this->linkedAccounts as $provider => $isLinked)
                            {{-- The whole row is the action: connect when it is not
                                 linked, re-authenticate when it already is. --}}
                            <a
                                href="{{ app(AppInfoService::class)->socialRedirectUrl($provider) }}"
                                class="flex w-full items-center gap-3 px-[16px] py-[13px] text-left {{ ! $loop->last ? 'border-b' : '' }}"
                                style="{{ ! $loop->last ? 'border-color: #E5DDD0;' : '' }}"
                            >
                                {{-- Provider Icon --}}
                                <div class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-[10px]
                                    @if ($provider === 'google') bg-[#F3E8FF]
                                    @elseif ($provider === 'facebook') bg-[#E8F0FE]
                                    @elseif ($provider === 'apple') bg-[#F0F0F0]
                                    @elseif ($provider === 'microsoft') bg-[#FFF3E0]
                                    @endif
                                ">
                                    @if ($provider === 'google')
                                        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 001 12c0 1.77.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                                    @elseif ($provider === 'facebook')
                                        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="#1877F2"/></svg>
                                    @elseif ($provider === 'apple')
                                        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.32 2.32-2.11 4.45-3.74 4.25z" fill="#000"/></svg>
                                    @elseif ($provider === 'microsoft')
                                        <svg width="18" height="18" viewBox="0 0 24 24"><rect x="1" y="1" width="10" height="10" fill="#F25022"/><rect x="13" y="1" width="10" height="10" fill="#7FBA00"/><rect x="1" y="13" width="10" height="10" fill="#00A4EF"/><rect x="13" y="13" width="10" height="10" fill="#FFB900"/></svg>
                                    @endif
                                </div>

                                {{-- Provider Name --}}
                                <span class="flex-1 text-[14px] font-semibold text-bark">{{ ucfirst($provider) }}</span>

                                {{-- Status Badge --}}
                                @if ($isLinked)
                                    <span class="rounded-full bg-[#D4EDE4] px-[10px] py-[4px] text-[11px] font-bold text-[#0A5A3A]">{{ __('general.connected') }}</span>
                                @else
                                    <span class="rounded-full px-[10px] py-[4px] text-[11px] font-semibold text-muted" style="border: 1.5px solid #E5DDD0;">{{ __('general.connect') }}</span>
                                @endif
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#C0B8B0" stroke-width="2.5" stroke-linecap="round" class="ml-1 shrink-0"><path d="M9 18l6-6-6-6"/></svg>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Permissions Section: what the device actually allows, with a
                     tap to ask when it does not. --}}
                <div>
                    <p class="mb-[8px] px-[16px] text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.permissions') }}</p>
                    <div class="overflow-hidden rounded-[14px] bg-white" style="border: 1.5px solid #E5DDD0;">
                        {{-- Location --}}
                        <button
                            type="button"
                            wire:click="requestLocationPermission"
                            @disabled($locationPermission === 'granted')
                            class="flex w-full items-center gap-3 border-b px-[16px] py-[13px] text-left"
                            style="border-color: #E5DDD0;"
                        >
                            <div class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-[10px] bg-[#E8F5E9]">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5" fill="#0B3D2E" stroke="none"/></svg>
                            </div>
                            <span class="flex-1 text-[14px] font-semibold text-bark">{{ __('general.location') }}</span>
                            <x-permission-badge :status="$locationPermission" />
                        </button>

                        {{-- Push Notifications --}}
                        <div class="flex items-center gap-3 border-b px-[16px] py-[13px]" style="border-color: #E5DDD0;">
                            <div class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-[10px] bg-amber-100">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#C8811A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                    <path d="M13.73 21a2 2 0 01-3.46 0"/>
                                </svg>
                            </div>
                            <span class="flex-1 text-[14px] font-semibold text-bark">{{ __('general.push_notifications') }}</span>
                            @if ($pushPermission !== 'granted')
                                <button type="button" wire:click="requestPushPermission" class="mr-2">
                                    <x-permission-badge :status="$pushPermission" />
                                </button>
                            @endif
                            {{-- Toggle --}}
                            <button
                                wire:click="togglePushNotifications"
                                class="relative h-[26px] w-[44px] rounded-[13px] transition-colors duration-200"
                                style="background-color: {{ $notifications_enabled ? '#0B3D2E' : '#E5DDD0' }};"
                                role="switch"
                                aria-checked="{{ $notifications_enabled ? 'true' : 'false' }}"
                            >
                                <span class="absolute top-[2px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-all duration-200 {{ $notifications_enabled ? 'left-[20px]' : 'left-[2px]' }}"></span>
                            </button>
                        </div>

                        {{-- Email Notifications --}}
                        <div class="flex items-center gap-3 px-[16px] py-[13px]">
                            <div class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-[10px] bg-[#DBEAFE]">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                            </div>
                            <span class="flex-1 text-[14px] font-semibold text-bark">{{ __('general.email_notifications') }}</span>
                            {{-- Toggle --}}
                            <button
                                wire:click="toggleEmailNotifications"
                                class="relative h-[26px] w-[44px] rounded-[13px] transition-colors duration-200"
                                style="background-color: {{ $email_notifications_enabled ? '#0B3D2E' : '#E5DDD0' }};"
                                role="switch"
                                aria-checked="{{ $email_notifications_enabled ? 'true' : 'false' }}"
                            >
                                <span class="absolute top-[2px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-all duration-200 {{ $email_notifications_enabled ? 'left-[20px]' : 'left-[2px]' }}"></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Privacy Section --}}
                <div>
                    <p class="mb-[8px] px-[16px] text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.privacy') }}</p>
                    <div class="overflow-hidden rounded-[14px] bg-white" style="border: 1.5px solid #E5DDD0;">
                        <div class="flex items-center gap-3 px-[16px] py-[13px]">
                            <div class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-[10px] bg-[#F3E8FF]">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 010 7.75"/>
                                </svg>
                            </div>
                            <span class="flex-1 text-[14px] font-semibold text-bark">{{ __('general.show_on_leaderboard') }}</span>
                            {{-- Toggle --}}
                            <button
                                wire:click="$toggle('show_on_leaderboard')"
                                class="relative h-[26px] w-[44px] rounded-[13px] transition-colors duration-200"
                                style="background-color: {{ $show_on_leaderboard ? '#0B3D2E' : '#E5DDD0' }};"
                                role="switch"
                                aria-checked="{{ $show_on_leaderboard ? 'true' : 'false' }}"
                            >
                                <span class="absolute top-[2px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-all duration-200 {{ $show_on_leaderboard ? 'left-[20px]' : 'left-[2px]' }}"></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Delete Account --}}
                <div>
                    <button
                        wire:click="$dispatch('show-dialog', {
                            type: 'destructive',
                            title: '{{ __('general.delete_account_title') }}',
                            message: '{{ __('general.delete_account_message') }}',
                            confirmLabel: '{{ __('general.delete') }}',
                            cancelLabel: '{{ __('general.cancel') }}',
                            confirmEvent: 'confirm-delete-account'
                        })"
                        class="flex w-full items-center gap-3 rounded-[14px] bg-coral-light p-[14px_16px]"
                        style="border: 1.5px solid rgba(232,92,58,0.2);"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#E85C3A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                            <line x1="10" y1="11" x2="10" y2="17"/>
                            <line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                        <span class="flex-1 text-left text-[14px] font-semibold text-coral">{{ __('general.delete_account') }}</span>
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>

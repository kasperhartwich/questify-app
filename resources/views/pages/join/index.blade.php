<?php

use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiNotFoundException;
use App\Livewire\Concerns\RequestsLocation;
use App\Livewire\Concerns\WithApiClient;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Geolocation\LocationReceived;
use Native\Mobile\Events\Scanner\CodeScanned;
use Native\Mobile\Facades\Scanner;
use Native\Mobile\Facades\System;

new
#[Layout('layouts.app')]
#[Title('Join a Quest')]
class extends Component
{
    use RequestsLocation, WithApiClient;

    private const NEAREST_COUNT = 3;

    private const NEARBY_RADIUS_KM = 50;

    public string $joinCode = '';

    public bool $isNative = false;

    public bool $isGuest = true;

    /** locating, ready or denied: where the nearest-quests part of the screen stands. */
    public string $nearestState = 'locating';

    public string $locationStatus = '';

    /** @var array<int, array<string, mixed>> */
    public array $nearestQuests = [];

    public function mount(): void
    {
        $this->isNative = System::isMobile();
        $this->isGuest = ! Auth::check();

        // Outside the app there is no GPS: a browser check gets Discover's default spot.
        if (! $this->isGuest && ! $this->isNative) {
            $this->loadNearestQuests(55.6761, 12.5683);
        }
    }

    #[OnNative(LocationReceived::class)]
    public function onLocationReceived(bool $success = false, ?float $latitude = null, ?float $longitude = null): void
    {
        if ($this->isGuest) {
            return;
        }

        if (! $success || $latitude === null || $longitude === null) {
            $this->nearestState = 'denied';

            return;
        }

        $this->loadNearestQuests($latitude, $longitude);
    }

    protected function onLocationPermissionDenied(string $status): void
    {
        $this->locationStatus = $status;
        $this->nearestState = 'denied';
    }

    /**
     * The same nearby call Discover makes, cut down to the closest few.
     */
    private function loadNearestQuests(float $latitude, float $longitude): void
    {
        try {
            $response = $this->api->quests()->nearby($latitude, $longitude, ['radius' => self::NEARBY_RADIUS_KM]);
        } catch (ApiException) {
            $response = [];
        }

        $this->nearestQuests = collect($response['data'] ?? [])
            ->filter(fn (mixed $quest): bool => is_array($quest) && isset($quest['id'], $quest['title']))
            ->sortBy(fn (array $quest): float => (float) ($quest['distance_to_start_km'] ?? INF))
            ->take(self::NEAREST_COUNT)
            ->values()
            ->all();
        $this->nearestState = 'ready';
    }

    public function formatDistance(mixed $km): ?string
    {
        if (! is_numeric($km) || (float) $km <= 0) {
            return null;
        }

        return (float) $km < 1
            ? (int) (round((float) $km * 100) * 10).' m'
            : number_format((float) $km, 1).' km';
    }

    public function joinByCode(): void
    {
        $this->validate([
            'joinCode' => ['required', 'string', 'size:6'],
        ]);

        $code = strtoupper($this->joinCode);

        if ($this->canJoin($code)) {
            $this->redirect('/join/' . $code . '/name');
        }
    }

    /**
     * Ask the session before leaving this screen, so a mistyped code is
     * answered here with what the player typed still in place.
     */
    private function canJoin(string $code): bool
    {
        try {
            $status = data_get($this->api->sessions()->show($code), 'data.status');
        } catch (ApiNotFoundException) {
            $this->addError('joinCode', __('sessions.code_not_found'));

            return false;
        } catch (ApiException) {
            return true;
        }

        if ($status !== null && $status !== 'waiting') {
            $this->addError('joinCode', __('sessions.already_started'));

            return false;
        }

        return true;
    }

    public function scanQr(): void
    {
        if ($this->isNative) {
            Scanner::scan()->prompt(__('general.scan_qr'))->formats(['qr'])->id('join-scan');

            return;
        }

        $this->dispatch('scan-qr-browser');
    }

    #[OnNative(CodeScanned::class)]
    public function onCodeScanned(string $data = '', string $format = '', ?string $id = null): void
    {
        if ($id !== 'join-scan') {
            return;
        }

        $code = $this->extractSessionCode($data);

        if (! $code) {
            $this->addError('joinCode', __('general.invalid_qr'));

            return;
        }

        if ($this->canJoin($code)) {
            $this->redirect('/join/' . $code . '/name');
        }
    }

    private function extractSessionCode(string $data): ?string
    {
        // The code sits inside the path, not at the end of it: the address we
        // share is /join/XK92PL/name, so basename() returned "name" and the
        // app could not read the very code it had just handed out.
        $path = parse_url($data, PHP_URL_PATH) ?? '';

        if (preg_match('#/join/([A-Z0-9]{6})(?:/|$)#i', $path, $matches)) {
            return strtoupper($matches[1]);
        }

        // Bare 6-character code
        $trimmed = trim($data);
        if (preg_match('/^[A-Z0-9]{6}$/i', $trimmed)) {
            return strtoupper($trimmed);
        }

        return null;
    }
};
?>

<div class="flex flex-col bg-cream px-5 pt-4" @unless ($isGuest) wire:init="requestLocation" @endunless>
    {{-- Back button (guest only — logged-in users have the bottom nav) --}}
    @if ($isGuest)
        <div class="flex items-center pb-2">
            <a href="/" class="flex h-[36px] w-[36px] items-center justify-center rounded-[11px] bg-cream-dark" wire:navigate>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
        </div>
    @endif

    {{-- The 3 nearest quests (signed-in players only: a quest page needs an account) --}}
    @unless ($isGuest)
        <div class="mb-2 mt-1" data-nearest-state="{{ $nearestState }}">
            <p class="mb-3 text-[11px] font-bold uppercase tracking-wide text-muted">{{ __('general.nearest_quests') }}</p>

            @if ($nearestState === 'ready' && count($nearestQuests) > 0)
                @php($first = $nearestQuests[0])
                <a href="/quests/{{ $first['id'] }}" wire:navigate data-nearest="first" class="relative mb-2.5 block overflow-hidden rounded-[18px] bg-forest-600 px-4 pb-4 pt-4 shadow-[0_4px_14px_rgba(11,61,46,0.25)]">
                    <div class="pointer-events-none absolute right-[-24px] top-[-24px] h-[96px] w-[96px] rounded-full border-[16px] border-white/[0.08]"></div>
                    <div class="relative flex items-center gap-1.5 text-[12px] font-semibold text-amber-400">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                        <span>{{ $this->formatDistance($first['distance_to_start_km'] ?? null) ?? __('general.nearest_closest') }}</span>
                    </div>
                    <h3 class="relative mt-1.5 font-heading text-[20px] font-[800] leading-tight text-white">{{ $first['title'] }}</h3>
                    @if (! empty($first['estimated_duration_minutes']))
                        <p class="relative mt-1 text-[12px] text-white/60">{{ $first['estimated_duration_minutes'] }} {{ __('general.minutes') }}</p>
                    @endif
                    <span class="relative mt-3.5 block w-full rounded-xl bg-amber-400 py-3 text-center font-heading text-[14px] font-bold text-bark">{{ __('general.nearest_open') }} &rarr;</span>
                </a>

                @foreach (array_slice($nearestQuests, 1) as $quest)
                    <a href="/quests/{{ $quest['id'] }}" wire:navigate data-nearest="other" class="mb-2 flex items-center gap-3 rounded-[13px] border-[1.5px] border-cream-border bg-white px-3.5 py-2.5">
                        <span class="text-forest-600"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg></span>
                        <span class="min-w-0 flex-1 truncate text-[13px] font-semibold text-bark">{{ $quest['title'] }}</span>
                        @if ($this->formatDistance($quest['distance_to_start_km'] ?? null))
                            <span class="shrink-0 text-[12px] text-muted">{{ $this->formatDistance($quest['distance_to_start_km']) }}</span>
                        @endif
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-muted" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                @endforeach
            @elseif ($nearestState === 'ready')
                <p data-nearest="none" class="rounded-[13px] border-[1.5px] border-dashed border-cream-border px-4 py-5 text-center text-[13px] text-muted">{{ __('general.nearest_none') }}</p>
            @elseif ($nearestState === 'denied')
                <div data-nearest="denied" class="rounded-[13px] border-[1.5px] border-dashed border-cream-border px-4 py-4 text-center">
                    <p class="text-[13px] text-muted">{{ $locationStatus === 'permanently_denied' ? __('general.location_permission_blocked') : __('general.nearest_location_off') }}</p>
                    <button type="button" wire:click="requestLocation" class="mt-2.5 text-[13px] font-semibold text-forest-400">{{ __('general.nearest_try_again') }}</button>
                </div>
            @else
                <p data-nearest="locating" class="rounded-[13px] border-[1.5px] border-dashed border-cream-border px-4 py-5 text-center text-[13px] text-muted">{{ __('general.nearest_locating') }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3 pb-1 pt-4">
            <div class="h-px flex-1 bg-cream-border"></div>
            <span class="text-[11px] font-semibold uppercase tracking-widest text-muted">{{ __('general.or') }}</span>
            <div class="h-px flex-1 bg-cream-border"></div>
        </div>
    @endunless

    {{-- Hero: headline + intro --}}
    <div class="mb-7 mt-3">
        <h1 class="mb-1 font-heading text-[24px] font-[800] leading-tight text-bark">{{ __('general.join_quest') }}</h1>
        <p class="text-[13px] leading-relaxed text-muted">{{ __('general.join_intro') }}</p>
    </div>

    {{-- Session code input --}}
    <div class="mb-4">
        <p class="mb-3 text-[11px] font-bold uppercase tracking-wide text-muted">{{ __('general.enter_session_code') }}</p>
        <x-code-boxes wire-model="joinCode" />
        @error('joinCode') <p class="mt-2 text-center text-[10px] text-coral">{{ $message }}</p> @enderror
    </div>

    {{-- Continue button --}}
    <button
        wire:click="joinByCode"
        class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark transition-opacity"
        @if (strlen($joinCode) < 6) style="opacity: 0.45" @endif
        @if (strlen($joinCode) < 6) disabled @endif
    >
        {{ __('general.continue') }} &rarr;
    </button>

    {{-- OR divider --}}
    <div class="flex items-center gap-3 py-5">
        <div class="h-px flex-1 bg-cream-border"></div>
        <span class="text-[11px] font-semibold uppercase tracking-widest text-muted">{{ __('general.or') }}</span>
        <div class="h-px flex-1 bg-cream-border"></div>
    </div>

    {{-- Scan QR --}}
    <button wire:click="scanQr" class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-cream-border bg-white px-4 py-3 text-[13px] font-semibold text-bark">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="2" width="7" height="7" rx="1"/><rect x="3.5" y="3.5" width="4" height="4" fill="currentColor" stroke="none"/><rect x="15" y="2" width="7" height="7" rx="1"/><rect x="16.5" y="3.5" width="4" height="4" fill="currentColor" stroke="none"/><rect x="2" y="15" width="7" height="7" rx="1"/><rect x="3.5" y="16.5" width="4" height="4" fill="currentColor" stroke="none"/><rect x="14" y="14" width="2.5" height="2.5"/><rect x="18" y="14" width="2.5" height="2.5"/><rect x="14" y="18" width="2.5" height="2.5"/><rect x="18" y="18" width="2.5" height="2.5"/></svg>
        {{ __('general.scan_qr') }}
    </button>

    {{-- Log in / Sign up link (guest only) --}}
    @if ($isGuest)
        <p class="mt-6 text-center text-[13px] text-muted">
            {{ __('general.have_an_account') }}
            <a href="/login" class="font-semibold text-forest-400" wire:navigate>{{ __('general.login') }}</a>
            {{ __('general.or_word') }}
            <a href="/register" class="font-semibold text-forest-400" wire:navigate>{{ __('general.register') }}</a>
        </p>
    @endif
</div>

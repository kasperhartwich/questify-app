<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Questify') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />

        <script src="https://js.sentry-cdn.com/09f17f769de593054c277df8493bef0a.min.js" crossorigin="anonymous" async></script>

        @if (Native\Mobile\Facades\System::isMobile())
        <script>
            window.__reverb = {
                key: @js(config('broadcasting.connections.reverb.key')),
                host: @js(config('broadcasting.connections.reverb.options.host')),
                port: {{ (int) config('broadcasting.connections.reverb.options.port', 80) }},
                scheme: @js(config('broadcasting.connections.reverb.options.scheme', 'http')),
            };
        </script>
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen {{ $bodyClass ?? 'bg-cream' }} nativephp-safe-area">
        {{-- WebSocket Reconnection Indicator --}}
        <div
            x-data="{
                connected: true,
                init() {
                    if (!window.Echo?.connector?.pusher) return;
                    const pusher = window.Echo.connector.pusher;
                    pusher.connection.bind('connected', () => this.connected = true);
                    pusher.connection.bind('connecting', () => this.connected = false);
                    pusher.connection.bind('unavailable', () => this.connected = false);
                    pusher.connection.bind('disconnected', () => this.connected = false);
                }
            }"
            x-show="!connected"
            x-transition
            x-cloak
            class="fixed inset-x-0 top-0 z-[100] bg-amber-500 py-1 text-center text-[11px] font-semibold text-white"
        >
            {{ __('sessions.reconnecting') }}
        </div>

        {{-- Main Content --}}
        <main class="min-h-screen bg-cream pt-[calc(env(safe-area-inset-top,0px)+12px)] pb-[calc(env(safe-area-inset-bottom,0px)+76px)]">
            {{ $slot }}
        </main>

        @if ($isNative ?? false)
        {{-- Native Bottom Navigation --}}
        <native:bottom-nav>
            <native:bottom-nav-item
                id="discover"
                icon="map"
                label="{{ __('general.discover') }}"
                url="/discover/list"
                :active="request()->is('discover*')"
            />
            <native:bottom-nav-item
                id="my-quests"
                icon="list.clipboard"
                label="{{ __('general.my_quests') }}"
                url="/my-quests"
                :active="request()->is('my-quests*')"
            />
            <native:bottom-nav-item
                id="join"
                icon="qrcode"
                label="{{ __('general.join') }}"
                url="/join"
                :active="request()->is('join*')"
            />
            <native:bottom-nav-item
                id="create"
                icon="mappin.circle"
                label="{{ __('general.create') }}"
                url="/create"
                :active="request()->is('create*')"
            />
            <native:bottom-nav-item
                id="profile"
                icon="person.circle"
                label="{{ __('general.profile') }}"
                url="/profile"
                :active="request()->is('profile*')"
            />
        </native:bottom-nav>
        @else
        {{-- HTML Tab Bar (browser fallback) --}}
        <nav class="fixed bottom-0 left-0 right-0 z-50 flex items-center border-t border-black/[0.07] bg-white px-0.5 pb-[calc(env(safe-area-inset-bottom,0px)+8px)] pt-2">
            {{-- Discover --}}
            <a href="/discover/list" class="flex flex-1 flex-col items-center justify-center gap-[3px] py-2" wire:navigate>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="{{ request()->is('discover*') ? '#0B3D2E' : '#C0B8B0' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="3,7 9,5 15,7 21,5 21,19 15,21 9,19 3,21"/>
                    <line x1="9" y1="5" x2="9" y2="19"/><line x1="15" y1="7" x2="15" y2="21"/>
                    <circle cx="15" cy="10" r="2" fill="{{ request()->is('discover*') ? '#0B3D2E' : '#C0B8B0' }}" stroke="none"/>
                </svg>
                <span class="whitespace-nowrap text-[7px] font-semibold tracking-[0.02em] {{ request()->is('discover*') ? 'text-forest-600' : 'text-[#C0B8B0]' }}">{{ __('general.discover') }}</span>
            </a>

            {{-- Quests --}}
            <a href="/my-quests" class="flex flex-1 flex-col items-center justify-center gap-[3px] py-2" wire:navigate>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="{{ request()->is('my-quests*') ? '#0B3D2E' : '#C0B8B0' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2h-2"/>
                    <rect x="9" y="1" width="6" height="4" rx="1"/>
                    <line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/>
                    <polyline points="7,9 8.5,10.5 11,8"/>
                </svg>
                <span class="whitespace-nowrap text-[7px] font-semibold tracking-[0.02em] {{ request()->is('my-quests*') ? 'text-forest-600' : 'text-[#C0B8B0]' }}">{{ __('general.my_quests') }}</span>
            </a>

            {{-- Join (center pill) --}}
            <a href="/join" class="flex flex-1 items-center justify-center" wire:navigate>
                <div class="flex h-[34px] w-[46px] items-center justify-center rounded-[11px] bg-forest-600 shadow-[0_3px_12px_rgba(11,61,46,0.35)]">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round">
                        <rect x="2" y="2" width="7" height="7" rx="1.5"/><rect x="3.5" y="3.5" width="4" height="4" fill="white" stroke="none"/>
                        <rect x="15" y="2" width="7" height="7" rx="1.5"/><rect x="16.5" y="3.5" width="4" height="4" fill="white" stroke="none"/>
                        <rect x="2" y="15" width="7" height="7" rx="1.5"/><rect x="3.5" y="16.5" width="4" height="4" fill="white" stroke="none"/>
                        <rect x="14" y="14" width="2.5" height="2.5" fill="white" stroke="none"/>
                        <rect x="18" y="14" width="2.5" height="2.5" fill="white" stroke="none"/>
                        <rect x="14" y="18" width="2.5" height="2.5" fill="white" stroke="none"/>
                        <rect x="18" y="18" width="2.5" height="2.5" fill="white" stroke="none"/>
                    </svg>
                </div>
            </a>

            {{-- Create --}}
            <a href="/create" class="flex flex-1 flex-col items-center justify-center gap-[3px] py-2" wire:navigate>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="{{ request()->is('create*') ? '#0B3D2E' : '#C0B8B0' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                    <line x1="9" y1="9" x2="15" y2="9"/><line x1="12" y1="6" x2="12" y2="12"/>
                </svg>
                <span class="whitespace-nowrap text-[7px] font-semibold tracking-[0.02em] {{ request()->is('create*') ? 'text-forest-600' : 'text-[#C0B8B0]' }}">{{ __('general.create') }}</span>
            </a>

            {{-- Profile --}}
            <a href="/profile" class="flex flex-1 flex-col items-center justify-center gap-[3px] py-2" wire:navigate>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="{{ request()->is('profile*') ? '#0B3D2E' : '#C0B8B0' }}" stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.58-7 8-7s8 3 8 7"/>
                </svg>
                <span class="whitespace-nowrap text-[7px] font-semibold tracking-[0.02em] {{ request()->is('profile*') ? 'text-forest-600' : 'text-[#C0B8B0]' }}">{{ __('general.profile') }}</span>
            </a>
        </nav>
        @endif

        <livewire:dialog />
        <livewire:push-notification-manager />

        @livewireScripts
    </body>
</html>

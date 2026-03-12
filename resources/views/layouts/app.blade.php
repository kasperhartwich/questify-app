<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Questify') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-gray-100 dark:bg-gray-900 nativephp-safe-area">
        {{-- Native Top Bar --}}
        <native:top-bar
            id="top-bar"
            title="{{ $title ?? config('app.name', 'Questify') }}"
            background-color="#4f46e5"
            text-color="#ffffff"
        />

        {{-- Main Content --}}
        <main>
            {{ $slot }}
        </main>

        {{-- Native Bottom Navigation --}}
        <native:bottom-nav>
            <native:bottom-nav-item
                id="discover"
                icon="magnifyingglass"
                label="{{ __('general.discover') }}"
                url="/discover/list"
                :active="request()->is('discover*')"
            />
            <native:bottom-nav-item
                id="my-quests"
                icon="list.bullet"
                label="{{ __('general.my_quests') }}"
                url="/my-quests"
                :active="request()->is('my-quests*')"
            />
            <native:bottom-nav-item
                id="create"
                icon="plus.circle.fill"
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

        @livewireScripts
    </body>
</html>

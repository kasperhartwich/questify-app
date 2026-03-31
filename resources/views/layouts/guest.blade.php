<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Questify') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-cream dark:bg-forest-800 nativephp-safe-area">
        {{-- Native Top Bar --}}
        <native:top-bar
            id="top-bar"
            title="{{ $title ?? config('app.name', 'Questify') }}"
            background-color="#0B3D2E"
            text-color="#ffffff"
        />

        {{-- Main Content --}}
        <main>
            {{ $slot }}
        </main>

        <livewire:error-modal />

        @livewireScripts
    </body>
</html>

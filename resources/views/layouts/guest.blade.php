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

        @livewireScripts
    </body>
</html>

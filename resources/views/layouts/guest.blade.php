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

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="h-dvh overflow-hidden {{ $bodyClass ?? 'bg-cream' }} nativephp-safe-area">
        {{-- Main Content --}}
        <main class="h-full pt-[calc(env(safe-area-inset-top,0px)+12px)] pb-[calc(env(safe-area-inset-bottom,0px)+12px)]">
            {{ $slot }}
        </main>

        <livewire:dialog />

        @livewireScripts
    </body>
</html>

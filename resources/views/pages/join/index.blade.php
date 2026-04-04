<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Scanner\CodeScanned;
use Native\Mobile\Facades\Scanner;
use Native\Mobile\Facades\System;

new
#[Layout('layouts.app')]
#[Title('Join a Quest')]
class extends Component
{
    public string $joinCode = '';

    public bool $isNative = false;

    public bool $isGuest = true;

    public function mount(): void
    {
        $this->isNative = System::isMobile();
        $this->isGuest = ! Auth::check();
    }

    public function joinByCode(): void
    {
        $this->validate([
            'joinCode' => ['required', 'string', 'size:6'],
        ]);

        $this->redirect('/join/' . strtoupper($this->joinCode) . '/name');
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

        $this->redirect('/join/' . $code . '/name');
    }

    private function extractSessionCode(string $data): ?string
    {
        // Try to extract 6-char code from a URL path (e.g. https://example.com/join/XK92PL/name)
        if (preg_match('/([A-Z0-9]{6})/i', basename(parse_url($data, PHP_URL_PATH) ?? ''), $matches)) {
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

<div class="flex flex-col bg-cream px-4 pt-4">
    {{-- Header (guest only — logged-in users have the bottom nav) --}}
    @if ($isGuest)
        <div class="flex items-center gap-3 pb-3">
            <a href="/" class="flex h-[36px] w-[36px] items-center justify-center rounded-[11px] bg-cream-dark" wire:navigate>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
            <h1 class="font-heading text-[18px] font-bold text-bark">{{ __('general.join_quest') }}</h1>
        </div>
    @endif

    {{-- Session code input --}}
    <div class="mb-4">
        <p class="mb-3 text-[11px] font-bold uppercase tracking-wide text-muted">{{ __('general.enter_session_code') }}</p>
        <x-code-boxes wire-model="joinCode" />
        @error('joinCode') <p class="mt-2 text-center text-[10px] text-coral">{{ $message }}</p> @enderror
        <p class="mt-3 text-center text-[13px] text-muted">{{ __('general.ask_quest_master_code') }}</p>
    </div>

    {{-- OR divider --}}
    <div class="flex items-center gap-3 py-4">
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
        <p class="mt-5 text-center text-[13px] text-muted">
            {{ __('general.have_an_account') }}
            <a href="/login" class="font-semibold text-forest-400" wire:navigate>{{ __('general.login') }}</a>
            {{ __('general.or_word') }}
            <a href="/register" class="font-semibold text-forest-400" wire:navigate>{{ __('general.register') }}</a>
        </p>
    @endif

    {{-- Continue button --}}
    <div class="mt-5">
        <button
            wire:click="joinByCode"
            class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark transition-opacity"
            @if (strlen($joinCode) < 6) style="opacity: 0.45" @endif
            @if (strlen($joinCode) < 6) disabled @endif
        >
            {{ __('general.continue') }} &rarr;
        </button>
    </div>
</div>

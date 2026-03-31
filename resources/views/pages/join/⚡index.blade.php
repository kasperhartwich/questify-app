<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Join a Quest')]
class extends Component
{
    public string $joinCode = '';

    public function joinByCode(): void
    {
        $this->validate([
            'joinCode' => ['required', 'string', 'size:6'],
        ]);

        $this->redirect('/join/' . strtoupper($this->joinCode) . '/name');
    }

    public function scanQr(): void
    {
        $this->dispatch('scan-qr');
    }
};
?>

<div class="flex min-h-screen flex-col bg-cream">
    {{-- Header --}}
    <div class="flex items-center gap-2.5 px-4 pb-3 pt-4">
        <a href="/" class="flex h-[30px] w-[30px] items-center justify-center rounded-[9px] bg-cream-dark" wire:navigate>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 class="font-heading text-base font-bold text-bark">{{ __('general.join_quest') }}</h1>
    </div>

    <div class="flex flex-1 flex-col px-4">
        {{-- Session code input --}}
        <div class="mb-4">
            <p class="mb-2.5 text-[11px] font-semibold uppercase tracking-wider text-muted">{{ __('general.enter_session_code') ?? 'Enter session code' }}</p>
            <x-code-boxes wire-model="joinCode" />
            @error('joinCode') <p class="mt-2 text-center text-[10px] text-coral">{{ $message }}</p> @enderror
            <p class="mt-2.5 text-center text-[10px] text-muted">{{ __('general.ask_quest_master_code') ?? 'Ask your Quest Master for the 6-character code' }}</p>
        </div>

        {{-- OR divider --}}
        <div class="flex items-center gap-2.5 py-4">
            <div class="h-px flex-1 bg-cream-border"></div>
            <span class="text-[10px] font-semibold uppercase tracking-widest text-muted">{{ __('general.or') }}</span>
            <div class="h-px flex-1 bg-cream-border"></div>
        </div>

        {{-- Scan QR --}}
        <button wire:click="scanQr" class="flex w-full items-center justify-center gap-2 rounded-xl border-[1.5px] border-cream-border bg-white px-4 py-3 text-[13px] font-semibold text-bark">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="2" width="7" height="7" rx="1"/><rect x="3.5" y="3.5" width="4" height="4" fill="currentColor" stroke="none"/><rect x="15" y="2" width="7" height="7" rx="1"/><rect x="16.5" y="3.5" width="4" height="4" fill="currentColor" stroke="none"/><rect x="2" y="15" width="7" height="7" rx="1"/><rect x="3.5" y="16.5" width="4" height="4" fill="currentColor" stroke="none"/><rect x="14" y="14" width="2.5" height="2.5"/><rect x="18" y="14" width="2.5" height="2.5"/><rect x="14" y="18" width="2.5" height="2.5"/><rect x="18" y="18" width="2.5" height="2.5"/></svg>
            {{ __('general.scan_qr') ?? 'Scan QR Code' }}
        </button>

        <div class="flex-1"></div>

        {{-- Continue button --}}
        <div class="pb-2">
            <button
                wire:click="joinByCode"
                class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark"
                @if (strlen($joinCode) < 6) style="opacity: 0.5" @endif
                @if (strlen($joinCode) < 6) disabled @endif
            >
                {{ __('general.continue') ?? 'Continue' }} &rarr;
            </button>
        </div>
    </div>
</div>

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

        $this->redirect('/sessions/' . strtoupper($this->joinCode));
    }

    public function scanQr(): void
    {
        $this->dispatch('scan-qr');
    }
};
?>

<div class="flex min-h-screen flex-col bg-cream">
    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pb-3 pt-4">
        <a href="/" class="flex h-8 w-8 items-center justify-center rounded-lg bg-cream-dark" wire:navigate>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h1 class="font-heading text-lg font-bold text-bark">{{ __('general.join_quest') }}</h1>
    </div>

    <div class="flex flex-1 flex-col px-4">
        {{-- Session code input --}}
        <div class="mb-4">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted">{{ __('general.enter_session_code') ?? 'Enter session code' }}</p>
            <form wire:submit="joinByCode" class="space-y-3">
                <div class="flex justify-center gap-1.5">
                    <input
                        type="text"
                        wire:model="joinCode"
                        placeholder="{{ __('general.enter_code') }}"
                        maxlength="6"
                        class="w-full rounded-xl border-2 border-cream-border bg-white px-4 py-3.5 text-center font-heading text-xl font-extrabold uppercase tracking-[0.3em] text-bark focus:border-forest-600 focus:outline-none"
                    />
                </div>
                @error('joinCode') <p class="text-center text-sm text-coral">{{ $message }}</p> @enderror
                <p class="text-center text-xs text-muted">{{ __('general.ask_quest_master_code') ?? 'Ask your Quest Master for the 6-character code' }}</p>
            </form>
        </div>

        {{-- OR divider --}}
        <div class="flex items-center gap-3 py-4">
            <div class="h-px flex-1 bg-cream-border"></div>
            <span class="text-xs font-semibold tracking-wider text-muted">{{ __('general.or') }}</span>
            <div class="h-px flex-1 bg-cream-border"></div>
        </div>

        {{-- Scan QR --}}
        <button wire:click="scanQr" class="flex w-full items-center justify-center gap-2 rounded-xl border-[1.5px] border-cream-border bg-white px-4 py-3 text-sm font-semibold text-bark">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="2" width="7" height="7" rx="1"/><rect x="15" y="2" width="7" height="7" rx="1"/><rect x="2" y="15" width="7" height="7" rx="1"/><rect x="14" y="14" width="2.5" height="2.5"/><rect x="18" y="14" width="2.5" height="2.5"/><rect x="14" y="18" width="2.5" height="2.5"/><rect x="18" y="18" width="2.5" height="2.5"/></svg>
            {{ __('general.scan_qr') }}
        </button>

        <div class="flex-1"></div>

        {{-- Continue button --}}
        <div class="pb-6">
            <button
                wire:click="joinByCode"
                class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark hover:bg-amber-500 disabled:opacity-50"
                {{ strlen($joinCode) < 6 ? 'disabled' : '' }}
            >
                {{ __('general.continue') ?? 'Continue' }} →
            </button>
        </div>
    </div>
</div>

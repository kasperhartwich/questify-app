<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Welcome')]
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

<div class="flex min-h-screen flex-col bg-forest-600">
    {{-- Decorative circles --}}
    <div class="pointer-events-none absolute right-[-50px] top-[-50px] h-[200px] w-[200px] rounded-full border-[36px] border-amber-400/[0.08]"></div>
    <div class="pointer-events-none absolute bottom-20 left-[-40px] h-[140px] w-[140px] rounded-full border-[24px] border-amber-400/[0.06]"></div>

    {{-- Logo & Branding --}}
    <div class="flex flex-1 flex-col items-center justify-center px-6">
        <x-questify-logo :size="72" variant="forest" />
        <h1 class="mt-3 font-heading text-3xl font-extrabold tracking-tight text-white">Questify</h1>
        <p class="mt-2 text-center text-sm leading-relaxed text-white/50">{{ __('quests.discover_description') }}</p>
    </div>

    {{-- CTAs --}}
    <div class="flex flex-col gap-3 px-6 pb-10">
        {{-- Join Quest --}}
        <form wire:submit="joinByCode" class="space-y-3">
            <input
                type="text"
                wire:model="joinCode"
                placeholder="{{ __('general.enter_code') }}"
                maxlength="6"
                class="w-full rounded-xl border-2 border-cream-border bg-white px-4 py-3 text-center text-lg font-bold uppercase tracking-widest text-bark"
            />
            @error('joinCode') <p class="text-sm text-red-400">{{ $message }}</p> @enderror
            <button type="submit" class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark hover:bg-amber-500">
                {{ __('general.join_quest') }}
            </button>
        </form>

        {{-- Scan QR Code --}}
        <button wire:click="scanQr" class="w-full rounded-xl border-[1.5px] border-white/[0.28] bg-white/[0.12] px-4 py-3 text-sm font-semibold text-white">
            {{ __('general.scan_qr') }}
        </button>

        {{-- Auth links --}}
        <a href="/register" class="w-full rounded-xl border-[1.5px] border-white/[0.28] bg-white/[0.12] px-4 py-3 text-center text-sm font-semibold text-white">
            {{ __('general.register') }}
        </a>
        <a href="/login" class="block py-2 text-center text-sm font-semibold text-white/50">
            {{ __('general.login') }}
        </a>
    </div>
</div>

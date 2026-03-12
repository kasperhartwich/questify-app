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

<div class="flex flex-col items-center justify-center min-h-screen px-6 py-12">
    {{-- Logo & Branding --}}
    <div class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-indigo-600 dark:text-indigo-400">Questify</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">{{ __('quests.discover_description') }}</p>
    </div>

    {{-- Join Quest by Code --}}
    <div class="w-full max-w-sm space-y-4">
        <form wire:submit="joinByCode" class="space-y-3">
            <input
                type="text"
                wire:model="joinCode"
                placeholder="{{ __('general.enter_code') }}"
                maxlength="6"
                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-center text-lg uppercase tracking-widest dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            />
            @error('joinCode') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700">
                {{ __('general.join_quest') }}
            </button>
        </form>

        {{-- Scan QR Code --}}
        <button wire:click="scanQr" class="w-full rounded-lg border border-indigo-600 px-4 py-3 font-semibold text-indigo-600 hover:bg-indigo-50 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-gray-800">
            {{ __('general.scan_qr') }}
        </button>

        {{-- Divider --}}
        <div class="flex items-center gap-3">
            <div class="h-px flex-1 bg-gray-300 dark:bg-gray-600"></div>
            <span class="text-sm text-gray-500">{{ __('general.or') }}</span>
            <div class="h-px flex-1 bg-gray-300 dark:bg-gray-600"></div>
        </div>

        {{-- Auth Buttons --}}
        <a href="/login" class="block w-full rounded-lg bg-white px-4 py-3 text-center font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-700">
            {{ __('general.login') }}
        </a>
        <a href="/register" class="block w-full rounded-lg bg-gray-900 px-4 py-3 text-center font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
            {{ __('general.register') }}
        </a>
    </div>
</div>

<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Welcome')]
class extends Component
{
};
?>

<div class="relative flex min-h-screen flex-col bg-forest-600 overflow-hidden">
    {{-- Decorative circles --}}
    <div class="pointer-events-none absolute right-[-60px] top-[-60px] h-[240px] w-[240px] rounded-full border-[40px]" style="border-color: rgba(245,166,35,0.07)"></div>
    <div class="pointer-events-none absolute bottom-24 left-[-50px] h-[160px] w-[160px] rounded-full border-[28px]" style="border-color: rgba(245,166,35,0.05)"></div>

    {{-- Logo & Branding --}}
    <div class="flex flex-1 flex-col items-center justify-center px-6">
        <x-questify-logo :size="72" variant="forest" />
        <h1 class="mt-3 font-heading text-[38px] font-[800] leading-tight tracking-tight text-white">Questify</h1>
        <p class="mt-2 text-center text-[14px] leading-relaxed text-white/50">
            Real places · Real questions<br>Real adventure
        </p>
    </div>

    {{-- CTAs --}}
    <div class="flex flex-col gap-[11px] px-10 pb-6">
        {{-- Join a Quest --}}
        <a href="/join" class="flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-[15px] font-heading text-sm font-bold text-bark" wire:navigate>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="2" y="2" width="7" height="7" rx="1.5"/><rect x="15" y="2" width="7" height="7" rx="1.5"/><rect x="2" y="15" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="2.5" height="2.5"/><rect x="18.5" y="14" width="2.5" height="2.5"/><rect x="14" y="18.5" width="2.5" height="2.5"/><rect x="18.5" y="18.5" width="2.5" height="2.5"/></svg>
            {{ __('general.join_quest') }}
        </a>

        {{-- Sign Up --}}
        <a href="/register" class="w-full rounded-[14px] border-[1.5px] border-white/25 px-4 py-[13px] text-center text-sm font-semibold text-white" wire:navigate>
            {{ __('general.register') }}
        </a>

        {{-- Log In --}}
        <a href="/login" class="block py-[6px] text-center text-sm font-semibold text-white/50" wire:navigate>
            {{ __('general.login') }}
        </a>
    </div>

    <p class="pb-8 text-center text-[11px] text-white/30">{{ __('general.no_account_needed') }}</p>
</div>

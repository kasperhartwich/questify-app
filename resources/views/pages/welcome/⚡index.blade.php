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

<div class="flex min-h-screen flex-col bg-forest-600">
    {{-- Decorative circles --}}
    <div class="pointer-events-none absolute right-[-50px] top-[-50px] h-[200px] w-[200px] rounded-full border-[36px] border-amber-400/[0.08]"></div>
    <div class="pointer-events-none absolute bottom-20 left-[-40px] h-[140px] w-[140px] rounded-full border-[24px] border-amber-400/[0.06]"></div>
    <div class="pointer-events-none absolute right-[-20px] top-[30%] h-[80px] w-[80px] rounded-full border-[14px] border-amber-400/[0.05]"></div>

    {{-- Logo & Branding --}}
    <div class="flex flex-1 flex-col items-center justify-center px-6">
        <x-questify-logo :size="72" variant="forest" />
        <h1 class="mt-3 font-heading text-3xl font-extrabold tracking-tight text-white">Questify</h1>
        <p class="mt-2 text-center text-sm leading-relaxed text-white/50">{{ __('quests.discover_description') }}</p>
    </div>

    {{-- CTAs --}}
    <div class="flex flex-col gap-3 px-10 pb-6">
        {{-- Join a Quest --}}
        <a href="/join" class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark" wire:navigate>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="2" y="2" width="7" height="7" rx="1.5"/><rect x="15" y="2" width="7" height="7" rx="1.5"/><rect x="2" y="15" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="2.5" height="2.5"/><rect x="18.5" y="14" width="2.5" height="2.5"/><rect x="14" y="18.5" width="2.5" height="2.5"/><rect x="18.5" y="18.5" width="2.5" height="2.5"/></svg>
            {{ __('general.join_quest') }}
        </a>

        {{-- Sign Up --}}
        <a href="/register" class="w-full rounded-xl border-[1.5px] border-white/[0.28] bg-white/[0.12] px-4 py-3 text-center text-sm font-semibold text-white" wire:navigate>
            {{ __('general.register') }}
        </a>

        {{-- Log In --}}
        <a href="/login" class="block py-1 text-center text-sm font-semibold text-white/50" wire:navigate>
            {{ __('general.login') }}
        </a>
    </div>

    <p class="pb-8 text-center text-xs text-white/30">{{ __('general.no_account_needed') ?? 'No account needed to join a quest' }}</p>
</div>

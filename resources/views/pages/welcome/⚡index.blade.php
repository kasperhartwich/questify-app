<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest', ['bodyClass' => 'bg-forest-600'])]
#[Title('Welcome')]
class extends Component
{
    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirect('/discover/list');
        }
    }
};
?>

<div class="relative flex h-dvh flex-col bg-forest-600 overflow-hidden pt-6 pb-4">
    {{-- Decorative circles --}}
    <div class="pointer-events-none absolute right-[-60px] top-[-60px] h-[240px] w-[240px] rounded-full border-[40px]" style="border-color: rgba(245,166,35,0.07)"></div>
    <div class="pointer-events-none absolute bottom-24 left-[-50px] h-[160px] w-[160px] rounded-full border-[28px]" style="border-color: rgba(245,166,35,0.05)"></div>

    {{-- Language selector --}}
    <div x-data="{ open: false, selected: @js(app()->getLocale()) }" class="absolute left-4 top-[60px] z-20">
        {{-- Trigger button --}}
        <button @click="open = true" class="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-2 text-[13px] font-semibold text-white">
            <span class="text-[16px]">{{ app()->getLocale() === 'da' ? '🇩🇰' : '🇬🇧' }}</span>
            <span>{{ strtoupper(app()->getLocale()) }}</span>
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
        </button>

        {{-- Bottom sheet overlay --}}
        <template x-teleport="body">
            <div x-show="open" x-cloak class="fixed inset-0 z-50">
                {{-- Backdrop --}}
                <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

                {{-- Sheet --}}
                <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full" class="absolute inset-x-0 bottom-0 rounded-t-[24px] bg-white px-6 pb-8 pt-6">
                    {{-- Handle --}}
                    <div class="mx-auto mb-5 h-[4px] w-10 rounded-full bg-cream-border"></div>

                    <h2 class="font-heading text-[20px] font-extrabold text-bark">{{ __('general.select_language') }}</h2>
                    <p class="mb-4 text-[13px] text-muted">{{ __('general.choose_language') }}</p>

                    <div class="flex flex-col gap-2.5">
                        {{-- Danish --}}
                        <button @click="selected = 'da'" class="flex items-center gap-3 rounded-[14px] border-[1.5px] px-4 py-3.5" :class="selected === 'da' ? 'border-forest-600 bg-[#F4FBF7]' : 'border-cream-border'">
                            <span class="text-[22px]">🇩🇰</span>
                            <div class="flex-1 text-left">
                                <div class="text-[14px] font-bold text-bark">Dansk</div>
                                <div class="text-[12px] text-muted">Danish</div>
                            </div>
                            <div class="flex h-5 w-5 items-center justify-center rounded-full border-2" :class="selected === 'da' ? 'border-forest-600' : 'border-cream-border'">
                                <div x-show="selected === 'da'" class="h-2.5 w-2.5 rounded-full bg-forest-600"></div>
                            </div>
                        </button>

                        {{-- English --}}
                        <button @click="selected = 'en'" class="flex items-center gap-3 rounded-[14px] border-[1.5px] px-4 py-3.5" :class="selected === 'en' ? 'border-forest-600 bg-[#F4FBF7]' : 'border-cream-border'">
                            <span class="text-[22px]">🇬🇧</span>
                            <div class="flex-1 text-left">
                                <div class="text-[14px] font-bold text-bark">English</div>
                                <div class="text-[12px] text-muted">English</div>
                            </div>
                            <div class="flex h-5 w-5 items-center justify-center rounded-full border-2" :class="selected === 'en' ? 'border-forest-600' : 'border-cream-border'">
                                <div x-show="selected === 'en'" class="h-2.5 w-2.5 rounded-full bg-forest-600"></div>
                            </div>
                        </button>
                    </div>

                    {{-- Confirm --}}
                    <a :href="'/locale/' + selected" class="mt-5 block w-full rounded-[14px] bg-amber-400 py-[14px] text-center font-heading text-[15px] font-bold text-bark">
                        {{ __('general.confirm') }}
                    </a>
                </div>
            </div>
        </template>
    </div>

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

    <p class="pb-[env(safe-area-inset-bottom,8px)] text-center text-[11px] text-white/30">{{ __('general.no_account_needed') }}</p>
</div>

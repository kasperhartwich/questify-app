<?php

use App\Auth\QuestifyApiGuard;
use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiValidationException;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use App\Services\AppInfoService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Sign Up')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    /** 1 = method, 2 = details, 3 = phone verify */
    public int $step = 1;

    public string $signup_method = 'email';

    /** @var array<int, string> */
    public array $socialProviders = [];

    public bool $emailEnabled = true;

    public bool $phoneEnabled = true;

    public function mount(): void
    {
        $appInfo = app(AppInfoService::class);
        $this->socialProviders = $appInfo->enabledSocialProviders();
        $this->emailEnabled = $appInfo->isAuthMethodEnabled('email');
        $this->phoneEnabled = $appInfo->isAuthMethodEnabled('phone');
    }

    public string $first_name = '';

    public string $last_name = '';

    public string $display_name = '';

    public string $email = '';

    public string $password = '';

    public string $phone_number = '';

    public string $phone_code = '';

    public function goToEmailSignup(): void
    {
        $this->signup_method = 'email';
        $this->step = 2;
    }

    public function goToPhoneSignup(): void
    {
        $this->signup_method = 'phone';
        $this->step = 2;
    }

    public function goBack(): void
    {
        $this->resetErrorBag();

        if ($this->step > 1) {
            $this->step--;
        } else {
            $this->redirect('/');
        }
    }

    public function register(): void
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'display_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ];

        if ($this->signup_method === 'phone') {
            $rules['phone_number'] = ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/'];
        }

        $this->validate($rules);

        $name = trim($this->first_name . ' ' . $this->last_name);
        $phone = $this->signup_method === 'phone' ? $this->phone_number : null;

        try {
            $response = $this->api->auth()->register(
                $name,
                $this->email,
                $this->password,
                $this->password,
                $phone,
            );

            /** @var QuestifyApiGuard $guard */
            $guard = Auth::guard();
            $guard->login($response['data']['user'], $response['data']['token']);

            if ($phone) {
                $this->api->auth()->submitPhone($phone);
                $this->step = 3;
            } else {
                $this->redirect('/discover/list');
            }
        } catch (ApiValidationException $e) {
            foreach ($e->errors as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        } catch (ApiException $e) {
            $this->dispatch('api-error', message: $e->getMessage());
        }
    }

    public function verifyPhone(): void
    {
        $this->validate([
            'phone_code' => ['required', 'string', 'size:6'],
        ]);

        try {
            $this->api->auth()->verifyPhone($this->phone_code);
            $this->redirect('/discover/list');
        } catch (ApiValidationException $e) {
            $this->addError('phone_code', __('auth.invalid_code'));
        } catch (ApiException $e) {
            $this->dispatch('api-error', message: $e->getMessage());
        }
    }

    public function resendCode(): void
    {
        try {
            $this->api->auth()->resendVerification();
            session()->flash('code_resent', true);
        } catch (ApiValidationException) {
            // Silently handle
        } catch (ApiException) {
            // Silently handle
        }
    }
};
?>

<div class="flex min-h-screen flex-col bg-cream px-5 pb-6 pt-2">
    {{-- Step 1: Method Selection --}}
    @if ($step === 1)
        {{-- Back + Logo --}}
        <div class="flex items-center gap-2.5 pb-5 pt-1">
            <a href="/" class="flex h-[36px] w-[36px] items-center justify-center rounded-[11px] bg-cream-dark" wire:navigate>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
            <div class="flex items-center gap-1.5">
                <x-questify-logo :size="22" variant="light" />
                <span class="font-heading text-sm font-extrabold tracking-tight text-bark">Questify</span>
            </div>
        </div>

        {{-- Heading --}}
        <h1 class="mb-1 font-heading text-[24px] font-[800] leading-tight text-bark">{!! nl2br(e(__('auth.create_your_account'))) !!}</h1>
        <p class="mb-[22px] text-[13px] text-muted">{{ __('auth.create_account_subtitle') }}</p>

        {{-- 2x3 Social auth grid --}}
        <x-social-auth-grid
            :providers="$socialProviders"
            :phone-enabled="$phoneEnabled"
            phone-action="goToPhoneSignup"
            :email-enabled="$emailEnabled"
            email-action="goToEmailSignup"
        />

        {{-- Login link --}}
        <p class="mt-auto text-center text-[13px] text-muted">
            {{ __('general.already_have_account') }}
            <a href="/login" class="font-semibold text-forest-400" wire:navigate>{{ __('general.login') }}</a>
        </p>

        {{-- Terms --}}
        <p class="mt-2 text-center text-[10px] leading-relaxed text-muted">
            {{ __('auth.terms_agreement_prefix') }}
            <a href="#" class="text-forest-400">{{ __('auth.terms_of_service') }}</a>
            {{ __('auth.and') }}
            <a href="#" class="text-forest-400">{{ __('auth.privacy_policy') }}</a>
        </p>

    {{-- Step 2: Details --}}
    @elseif ($step === 2)
        <x-step-indicator :current="2" :total="3" back-action="goBack" />

        {{-- Heading --}}
        <h1 class="mb-1 font-heading text-[20px] font-[800] leading-tight text-bark">{{ __('auth.your_details') }}</h1>
        <p class="mb-5 text-[12px] text-muted">{{ __('auth.step_2_of_3') }}</p>

        <form wire:submit="register" class="flex flex-1 flex-col gap-2.5">
            {{-- First + Last name --}}
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('auth.first_name') }}</label>
                    <input type="text" wire:model="first_name" placeholder="Anna" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white px-3.5 py-[13px] text-[13px] text-bark focus:border-forest-600 focus:outline-none" required />
                    @error('first_name') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                </div>
                <div class="flex-1">
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('auth.last_name') }}</label>
                    <input type="text" wire:model="last_name" placeholder="Jensen" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white px-3.5 py-[13px] text-[13px] text-bark focus:border-forest-600 focus:outline-none" />
                </div>
            </div>

            {{-- Display name --}}
            <div>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('auth.display_name') }}</label>
                <input type="text" wire:model="display_name" placeholder="AdventureAnna" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white px-3.5 py-[13px] text-[13px] text-bark focus:border-forest-600 focus:outline-none" required />
                <p class="mt-1 text-[9px] text-muted">{{ __('auth.display_name_hint') }}</p>
                @error('display_name') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.email') }}</label>
                <div class="relative">
                    <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-muted"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                    </div>
                    <input type="email" wire:model="email" placeholder="anna@example.com" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-3.5 text-[13px] text-bark focus:border-forest-600 focus:outline-none" required />
                </div>
                @error('email') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
            </div>

            {{-- Phone number (only for phone signup) --}}
            @if ($signup_method === 'phone')
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('auth.phone_number') }}</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        </div>
                        <input type="tel" wire:model="phone_number" placeholder="+45 20 12 34 56" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-3.5 text-[13px] text-bark focus:border-forest-600 focus:outline-none" required />
                    </div>
                    <p class="mt-1 text-[9px] text-muted">{{ __('auth.phone_e164_hint') }}</p>
                    @error('phone_number') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- Password --}}
            <div>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.password') }}</label>
                <div class="relative">
                    <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-muted"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                    <input type="password" wire:model="password" placeholder="{{ __('auth.min_8_characters') }}" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-3.5 text-[13px] text-bark focus:border-forest-600 focus:outline-none" required />
                </div>
                {{-- Strength meter --}}
                <div x-data="{ get strength() { const len = $wire.password?.length || 0; if (len === 0) return 0; if (len < 6) return 1; if (len < 10) return 2; if (len < 14) return 3; return 4; } }">
                    <div class="mt-1.5 flex gap-[3px]">
                        <template x-for="i in 4">
                            <div class="h-[3px] flex-1 rounded-full" :class="i <= strength ? (strength <= 1 ? 'bg-coral' : strength <= 2 ? 'bg-amber-400' : 'bg-forest-600') : 'bg-cream-border'"></div>
                        </template>
                    </div>
                    <p class="mt-1 text-[9px]" :class="strength <= 1 ? 'text-coral' : strength <= 2 ? 'text-amber-600' : 'text-forest-600'" x-show="strength > 0" x-text="strength <= 1 ? '{{ __('auth.weak_password') }}' : strength <= 2 ? '{{ __('auth.medium_password') }}' : '{{ __('auth.strong_password') }}'"></p>
                </div>
                @error('password') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
            </div>

            <div class="mt-auto pt-4">
                <button type="submit" class="w-full rounded-[14px] bg-amber-400 px-4 py-[13px] font-heading text-sm font-bold text-bark">
                    {{ __('auth.continue') }}
                </button>
            </div>
        </form>

    {{-- Step 3: Phone Verification --}}
    @elseif ($step === 3)
        <x-step-indicator :current="3" :total="3" back-action="goBack" />

        {{-- Icon --}}
        <div class="relative mb-4 flex h-16 w-16 items-center justify-center rounded-[18px] bg-amber-100">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#C8811A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            <div class="absolute -right-1 -top-1 flex h-3.5 w-3.5 items-center justify-center rounded-full border-2 border-cream bg-forest-600">
                <div class="h-1.5 w-1.5 rounded-full bg-white"></div>
            </div>
        </div>

        {{-- Heading --}}
        <h1 class="mb-1.5 font-heading text-[22px] font-extrabold leading-tight text-bark">{{ __('auth.check_messages') }}</h1>
        <p class="mb-1.5 text-xs leading-relaxed text-muted">{{ __('auth.sent_code_to') }}</p>
        <div class="mb-6 flex items-center gap-2">
            <span class="text-[13px] font-bold text-bark">{{ $phone_number }}</span>
        </div>

        {{-- Code input --}}
        <form wire:submit="verifyPhone" class="flex flex-1 flex-col">
            <label class="mb-2.5 block text-center text-[9px] font-bold uppercase tracking-wider text-muted">{{ __('auth.enter_6_digit_code') }}</label>
            <x-code-boxes wire-model="phone_code" inputmode="numeric" />
            @error('phone_code') <p class="mt-2 text-center text-[10px] text-coral">{{ $message }}</p> @enderror

            @if (session('code_resent'))
                <p class="mt-2 text-center text-[11px] font-semibold text-forest-600">{{ __('auth.code_resent') }}</p>
            @endif

            <p class="mt-5 text-center text-[11px] text-muted">
                {{ __('auth.didnt_get_it') }}
                <button type="button" wire:click="resendCode" class="font-semibold text-forest-600">{{ __('auth.resend') }}</button>
            </p>

            <div class="mt-auto pt-6">
                <button type="submit" class="w-full rounded-[14px] bg-forest-600 px-4 py-3.5 text-center font-heading text-sm font-bold text-white" @if(strlen($phone_code) < 6) style="opacity:0.5" @endif>
                    {{ __('auth.verify') }}
                </button>
            </div>
        </form>
    @endif
</div>

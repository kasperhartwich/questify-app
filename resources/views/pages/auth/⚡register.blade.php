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

    public string $country_code = '+45';

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

    public function submitPhoneOnly(): void
    {
        $this->validate([
            'phone_number' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
        ]);

        try {
            $this->api->auth()->submitPhone($this->phone_number);
            $this->step = 3;
        } catch (ApiValidationException $e) {
            foreach ($e->errors as $field => $messages) {
                $fieldKey = $field === 'phone_number' ? 'phone_number' : $field;
                $this->addError($fieldKey, $messages[0]);
            }
        } catch (ApiException $e) {
            $this->dispatch('api-error', message: $e->getMessage());
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

        if (! empty($this->phone_number)) {
            $rules['phone_number'] = ['string', 'regex:/^\+[1-9]\d{6,14}$/'];
        }

        $this->validate($rules);

        $name = trim($this->first_name . ' ' . $this->last_name);
        $phone = ! empty($this->phone_number) ? $this->phone_number : null;

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
            $userData = $response['data']['user'] ?? $response['user'] ?? null;
            $token = $response['data']['token'] ?? $response['token'] ?? null;

            if (! $userData || ! $token) {
                $this->addError('email', __('auth.failed'));

                return;
            }

            $guard->login($userData, $token);

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
        {{-- Heading --}}
        <h1 class="mb-1 mt-4 font-heading text-[24px] font-[800] leading-tight text-bark">{!! nl2br(e(__('auth.create_your_account'))) !!}</h1>
        <p class="mb-[22px] text-[13px] text-muted">{{ __('auth.create_account_subtitle') }}</p>

        {{-- Social auth grid (OAuth providers only, 2x2) --}}
        @if (count($socialProviders) > 0)
            <div class="mb-[4px] grid grid-cols-2 gap-[8px]">
                @foreach ($socialProviders as $provider)
                    <a href="/auth/{{ $provider }}/redirect" class="flex w-full items-center justify-center gap-[8px] rounded-[14px] border-[1.5px] border-cream-border bg-transparent py-[13px] text-[14px] font-semibold text-bark">
                        @if ($provider === 'google')
                            <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                        @elseif ($provider === 'facebook')
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        @elseif ($provider === 'apple')
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#1D1D1F"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                        @elseif ($provider === 'microsoft')
                            <svg width="18" height="18" viewBox="0 0 24 24"><rect x="1" y="1" width="10.5" height="10.5" fill="#F25022"/><rect x="12.5" y="1" width="10.5" height="10.5" fill="#7FBA00"/><rect x="1" y="12.5" width="10.5" height="10.5" fill="#00A4EF"/><rect x="12.5" y="12.5" width="10.5" height="10.5" fill="#FFB900"/></svg>
                        @endif
                        {{ ucfirst($provider) }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- OR divider --}}
        @if ($emailEnabled)
            <div class="flex items-center gap-3 py-[10px]">
                <div class="h-px flex-1 bg-cream-border"></div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-muted">{{ __('general.or') }}</span>
                <div class="h-px flex-1 bg-cream-border"></div>
            </div>

            {{-- Email inline row --}}
            <div>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.email') }}</label>
                <form wire:submit="goToEmailSignup" class="relative">
                    <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-muted"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                    </div>
                    <input type="email" wire:model="email" placeholder="{{ __('auth.email_placeholder') }}" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-[120px] text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" />
                    <button type="submit" class="absolute right-[5px] top-1/2 -translate-y-1/2 rounded-[10px] bg-forest-600 px-4 py-[8px] text-[12px] font-bold text-white">
                        {{ __('auth.continue') }}
                    </button>
                </form>
            </div>
        @endif

        {{-- OR divider --}}
        @if ($phoneEnabled)
            <div class="flex items-center gap-3 py-[10px]">
                <div class="h-px flex-1 bg-cream-border"></div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-muted">{{ __('general.or') }}</span>
                <div class="h-px flex-1 bg-cream-border"></div>
            </div>

            {{-- Phone inline row --}}
            <div>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('auth.phone_number') }}</label>
                <form wire:submit="goToPhoneSignup" class="flex gap-2">
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[14px]">🇩🇰</span>
                        <select
                            wire:model="country_code"
                            class="h-full appearance-none rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-8 pr-7 text-[13px] font-semibold text-bark focus:border-forest-600 focus:outline-none"
                        >
                            <option value="+45">+45</option>
                            <option value="+1">+1</option>
                            <option value="+44">+44</option>
                            <option value="+46">+46</option>
                            <option value="+47">+47</option>
                            <option value="+49">+49</option>
                        </select>
                        <div class="pointer-events-none absolute right-1.5 top-1/2 -translate-y-1/2">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2.5" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                        </div>
                    </div>
                    <div class="relative flex-1">
                        <input
                            type="tel"
                            wire:model="phone_number"
                            placeholder="20 12 34 56"
                            class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white px-3.5 py-[13px] pr-[110px] text-[13px] font-semibold text-bark placeholder:font-normal placeholder:text-forest-300 focus:border-forest-600 focus:outline-none"
                            inputmode="tel"
                        />
                        <button type="submit" class="absolute right-[5px] top-1/2 -translate-y-1/2 rounded-[10px] bg-forest-600 px-3 py-[8px] text-[12px] font-bold text-white">
                            {{ __('auth.send_sms') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Login link --}}
        <p class="mt-auto pt-6 text-center text-[13px] text-muted">
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

        @if ($signup_method === 'phone')
            {{-- Phone-only signup flow --}}
            <div class="flex items-center gap-2.5 pb-6 pt-1">
                <button wire:click="goBack" class="flex h-[36px] w-[36px] items-center justify-center rounded-[11px] bg-cream-dark">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
            </div>

            {{-- Icon --}}
            <div class="mb-4 flex h-[60px] w-[60px] items-center justify-center rounded-[17px] bg-[#D4EDE4]">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="1.8" stroke-linecap="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18" stroke-width="2.5"/></svg>
            </div>

            <h1 class="mb-1.5 font-heading text-[22px] font-extrabold leading-tight text-bark">{!! nl2br(e(__('auth.login_with_phone'))) !!}</h1>
            <p class="mb-6 text-[13px] leading-relaxed text-muted">{{ __('auth.phone_login_subtitle') }}</p>

            <form wire:submit="submitPhoneOnly" class="flex flex-1 flex-col">
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('auth.phone_number') }}</label>
                <input
                    type="tel"
                    wire:model="phone_number"
                    placeholder="+45 20 12 34 56"
                    class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white px-3.5 py-[13px] text-base font-semibold text-bark focus:border-forest-600 focus:outline-none"
                    inputmode="tel"
                    autofocus
                    required
                />
                @error('phone_number') <p class="mt-1.5 text-[10px] text-coral">{{ $message }}</p> @enderror
                <p class="mt-2 text-[11px] leading-relaxed text-muted">{{ __('auth.phone_sms_disclaimer') }}</p>

                <div class="mt-auto pt-6">
                    <button type="submit" class="w-full rounded-[14px] bg-amber-400 px-4 py-[13px] font-heading text-sm font-bold text-bark">
                        {{ __('auth.send_code') }} &rarr;
                    </button>
                </div>

                <p class="mt-3 text-center text-[12px] text-muted">
                    <button type="button" wire:click="goToEmailSignup" class="font-semibold text-forest-600">{{ __('auth.use_email_instead') }}</button>
                </p>
            </form>
        @else
            {{-- Email signup flow --}}
            <x-step-indicator :current="2" :total="3" back-action="goBack" />

            <h1 class="mb-1 font-heading text-[20px] font-[800] leading-tight text-bark">{{ __('auth.your_details') }}</h1>
            <p class="mb-5 text-[12px] text-muted">{{ __('auth.step_2_of_3') }}</p>

            <form wire:submit="register" class="flex flex-1 flex-col gap-2.5">
                {{-- First + Last name --}}
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('auth.first_name') }}</label>
                        <input type="text" wire:model="first_name" placeholder="Anna" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white px-3.5 py-[13px] text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" required />
                        @error('first_name') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex-1">
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('auth.last_name') }}</label>
                        <input type="text" wire:model="last_name" placeholder="Jensen" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white px-3.5 py-[13px] text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" />
                    </div>
                </div>

                {{-- Display name --}}
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('auth.display_name') }}</label>
                    <input type="text" wire:model="display_name" placeholder="AdventureAnna" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white px-3.5 py-[13px] text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" required />
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
                        <input type="email" wire:model="email" placeholder="anna@example.com" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-3.5 text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" required />
                    </div>
                    @error('email') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                </div>

                {{-- Phone number (optional) --}}
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('auth.phone_number') }} <span class="normal-case text-muted/60">({{ __('general.optional') }})</span></label>
                    <div class="relative">
                        <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        </div>
                        <input type="tel" wire:model="phone_number" placeholder="+45 20 12 34 56" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-3.5 text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" />
                    </div>
                    @error('phone_number') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.password') }}</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-muted"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        </div>
                        <input type="password" wire:model="password" placeholder="{{ __('auth.min_8_characters') }}" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-3.5 text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" required />
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
        @endif

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
                <button type="submit" class="w-full rounded-[14px] bg-forest-600 px-4 py-3.5 text-center font-heading text-sm font-bold text-white transition-opacity" wire:loading.attr="disabled" wire:loading.class="opacity-50">
                    {{ __('auth.verify') }}
                </button>
            </div>
        </form>
    @endif
</div>

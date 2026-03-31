<?php

use App\Auth\QuestifyApiGuard;
use App\Exceptions\Api\ApiAuthenticationException;
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
#[Title('Log In')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    public string $step = 'login';

    public string $email = '';

    public string $password = '';

    public string $login_token = '';

    public string $otp_code = '';

    public string $phone_number = '';

    public string $country_code = '+45';

    public string $phone_local = '';

    /** @var array<int, string> */
    public array $socialProviders = [];

    public bool $emailEnabled = true;

    public bool $phoneEnabled = false;

    public function mount(): void
    {
        $appInfo = app(AppInfoService::class);
        $this->socialProviders = $appInfo->enabledSocialProviders();
        $this->emailEnabled = $appInfo->isAuthMethodEnabled('email');
        $this->phoneEnabled = $appInfo->isAuthMethodEnabled('phone');
    }

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $response = $this->api->auth()->login($this->email, $this->password);

            if (! empty($response['requires_otp'])) {
                $this->login_token = $response['login_token'];
                $this->step = 'otp';

                return;
            }

            /** @var QuestifyApiGuard $guard */
            $guard = Auth::guard();
            $guard->login($response['data']['user'], $response['data']['token']);

            $this->redirect('/discover/list');
        } catch (ApiAuthenticationException) {
            $this->addError('email', __('auth.failed'));
        } catch (ApiValidationException $e) {
            foreach ($e->errors as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        } catch (ApiException $e) {
            $this->dispatch('api-error', message: $e->getMessage());
        }
    }

    public function verifyOtp(): void
    {
        $this->validate([
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        try {
            $response = $this->api->auth()->verifyOtp($this->otp_code, $this->login_token);

            /** @var QuestifyApiGuard $guard */
            $guard = Auth::guard();
            $guard->login($response['data']['user'], $response['data']['token']);

            $this->redirect('/discover/list');
        } catch (ApiAuthenticationException) {
            $this->addError('otp_code', __('auth.invalid_or_expired_token'));
        } catch (ApiValidationException) {
            $this->addError('otp_code', __('auth.invalid_code'));
        } catch (ApiException $e) {
            $this->dispatch('api-error', message: $e->getMessage());
        }
    }

    public function backToLogin(): void
    {
        $this->step = 'login';
        $this->otp_code = '';
        $this->login_token = '';
        $this->phone_number = '';
        $this->resetErrorBag();
    }

    public function goToPhoneLogin(): void
    {
        $this->step = 'phone';
        $this->resetErrorBag();
    }

    public function sendPhoneOtp(): void
    {
        $this->validate([
            'country_code' => ['required', 'string', 'regex:/^\+\d{1,4}$/'],
            'phone_local' => ['required', 'string', 'min:4', 'max:15'],
        ]);

        $this->phone_number = $this->country_code . preg_replace('/\s+/', '', $this->phone_local);

        try {
            $response = $this->api->auth()->login($this->phone_number, '');

            if (! empty($response['requires_otp'])) {
                $this->login_token = $response['login_token'];
                $this->step = 'otp';
            }
        } catch (ApiAuthenticationException) {
            $this->addError('phone_local', __('auth.failed'));
        } catch (ApiValidationException $e) {
            foreach ($e->errors as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        } catch (ApiException $e) {
            $this->dispatch('api-error', message: $e->getMessage());
        }
    }
};
?>

<div class="flex min-h-screen flex-col bg-cream px-5 pb-6 pt-2">
    @if ($step === 'login')
        {{-- Back + Logo --}}
        <div class="flex items-center gap-2.5 pb-5 pt-1">
            <a href="/" class="flex h-[30px] w-[30px] items-center justify-center rounded-[9px] bg-cream-dark" wire:navigate>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
            <div class="flex items-center gap-1.5">
                <x-questify-logo :size="22" variant="light" />
                <span class="font-heading text-sm font-extrabold tracking-tight text-bark">Questify</span>
            </div>
        </div>

        {{-- Heading --}}
        <h1 class="mb-1 font-heading text-[22px] font-extrabold leading-tight text-bark">{{ __('auth.welcome_back') }}</h1>
        <p class="mb-5 text-xs text-muted">{{ __('auth.login_subtitle') }}</p>

        {{-- Social buttons + Phone --}}
        <x-social-auth-grid :providers="$socialProviders" :phone-enabled="$phoneEnabled" phone-action="goToPhoneLogin" />

        {{-- OR divider --}}
        @if (count($socialProviders) > 0 && $emailEnabled)
            <div class="mb-3.5 flex items-center gap-2.5">
                <div class="h-px flex-1 bg-cream-border"></div>
                <span class="text-[10px] font-semibold uppercase tracking-widest text-muted">{{ __('general.or') }}</span>
                <div class="h-px flex-1 bg-cream-border"></div>
            </div>
        @endif

        {{-- Email + Password form --}}
        @if ($emailEnabled)
        <form wire:submit="login" class="flex flex-1 flex-col">
            <div class="mb-3.5 flex flex-col gap-2.5">
                <div class="relative">
                    <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-muted"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                    </div>
                    <input type="email" wire:model="email" placeholder="{{ __('auth.email_placeholder') }}" class="w-full rounded-xl border-2 border-cream-border bg-white py-3 pl-9 pr-3.5 text-[13px] text-bark focus:border-forest-600 focus:outline-none" required />
                </div>
                @error('email') <p class="-mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror

                <div class="relative">
                    <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-muted"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                    <input type="password" wire:model="password" placeholder="{{ __('general.password') }}" class="w-full rounded-xl border-2 border-cream-border bg-white py-3 pl-9 pr-3.5 text-[13px] text-bark focus:border-forest-600 focus:outline-none" required />
                </div>
                @error('password') <p class="-mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4 text-right">
                <span class="text-[11px] font-semibold text-forest-400">{{ __('auth.forgot_password') }}</span>
            </div>

            <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-3.5 text-center font-heading text-sm font-bold text-white">
                {{ __('general.login') }}
            </button>
        </form>
        @endif

        {{-- Sign up link --}}
        <p class="mt-3.5 text-center text-xs text-muted">
            {{ __('general.dont_have_account') }}
            <a href="/register" class="font-semibold text-forest-600 hover:text-forest-500" wire:navigate>{{ __('general.register') }}</a>
        </p>

    {{-- OTP Verification --}}
    @elseif ($step === 'otp')
        {{-- Back --}}
        <div class="flex items-center gap-2.5 pb-6 pt-1">
            <button wire:click="backToLogin" class="flex h-[30px] w-[30px] items-center justify-center rounded-[9px] bg-cream-dark">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
        </div>

        {{-- Icon --}}
        <div class="relative mb-4 flex h-16 w-16 items-center justify-center rounded-[18px] bg-amber-100">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#C8811A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            <div class="absolute -right-1 -top-1 flex h-3.5 w-3.5 items-center justify-center rounded-full border-2 border-cream bg-forest-600">
                <div class="h-1.5 w-1.5 rounded-full bg-white"></div>
            </div>
        </div>

        {{-- Heading --}}
        <h1 class="mb-1.5 font-heading text-[22px] font-extrabold leading-tight text-bark">{{ __('auth.verify_login') }}</h1>
        <p class="mb-1.5 text-xs leading-relaxed text-muted">{{ __('auth.otp_sent_to_phone') }}</p>
        <p class="mb-6 text-[13px] font-bold text-bark">{{ $email }}</p>

        {{-- Code input --}}
        <form wire:submit="verifyOtp" class="flex flex-1 flex-col">
            <label class="mb-2.5 block text-center text-[9px] font-bold uppercase tracking-wider text-muted">{{ __('auth.enter_6_digit_code') }}</label>
            <x-code-boxes wire-model="otp_code" inputmode="numeric" />
            @error('otp_code') <p class="mt-2 text-center text-[10px] text-coral">{{ $message }}</p> @enderror

            <div class="mt-auto pt-6">
                <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-3.5 text-center font-heading text-sm font-bold text-white" @if(strlen($otp_code) < 6) style="opacity:0.5" @endif>
                    {{ __('auth.verify') }}
                </button>
            </div>
        </form>

    {{-- Phone Login Step --}}
    @elseif ($step === 'phone')
        {{-- Back --}}
        <div class="flex items-center gap-2.5 pb-6 pt-1">
            <button wire:click="backToLogin" class="flex h-[30px] w-[30px] items-center justify-center rounded-[9px] bg-cream-dark">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
        </div>

        {{-- Icon --}}
        <div class="relative mb-4 flex h-16 w-16 items-center justify-center rounded-[18px] bg-[#E0EDE8]">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
        </div>

        {{-- Heading --}}
        <h1 class="mb-1.5 font-heading text-[22px] font-extrabold leading-tight text-bark">{!! nl2br(e(__('auth.login_with_phone'))) !!}</h1>
        <p class="mb-6 text-xs leading-relaxed text-muted">{{ __('auth.phone_login_subtitle') }}</p>

        <form wire:submit="sendPhoneOtp" class="flex flex-1 flex-col">
            {{-- Phone Number Label --}}
            <label class="mb-2 block text-[9px] font-bold uppercase tracking-wider text-muted">{{ __('auth.phone_number') }}</label>

            {{-- Country code + local number --}}
            <div class="mb-1.5 flex gap-2">
                {{-- Country code selector --}}
                <div class="relative">
                    <select
                        wire:model="country_code"
                        class="h-full appearance-none rounded-xl border-2 border-cream-border bg-white py-3 pl-3 pr-8 text-[13px] font-semibold text-bark focus:border-forest-600 focus:outline-none"
                    >
                        <option value="+45">🇩🇰 +45</option>
                        <option value="+1">🇺🇸 +1</option>
                        <option value="+44">🇬🇧 +44</option>
                        <option value="+46">🇸🇪 +46</option>
                        <option value="+47">🇳🇴 +47</option>
                        <option value="+49">🇩🇪 +49</option>
                        <option value="+33">🇫🇷 +33</option>
                        <option value="+34">🇪🇸 +34</option>
                        <option value="+39">🇮🇹 +39</option>
                        <option value="+31">🇳🇱 +31</option>
                        <option value="+48">🇵🇱 +48</option>
                        <option value="+351">🇵🇹 +351</option>
                        <option value="+91">🇮🇳 +91</option>
                        <option value="+61">🇦🇺 +61</option>
                        <option value="+81">🇯🇵 +81</option>
                    </select>
                    <div class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2.5" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                </div>

                {{-- Local number --}}
                <input
                    type="tel"
                    wire:model="phone_local"
                    placeholder=""
                    class="flex-1 rounded-xl border-2 border-cream-border bg-white px-3.5 py-3 text-base font-semibold text-bark focus:border-forest-600 focus:outline-none"
                    inputmode="tel"
                    autofocus
                />
            </div>

            @error('phone_local') <p class="mb-1.5 text-[10px] text-coral">{{ $message }}</p> @enderror
            @error('country_code') <p class="mb-1.5 text-[10px] text-coral">{{ $message }}</p> @enderror

            <p class="mb-4 text-[10px] leading-relaxed text-muted">{{ __('auth.phone_sms_disclaimer') }}</p>

            <button type="submit" class="w-full rounded-xl bg-amber-400 px-4 py-3.5 text-center font-heading text-sm font-bold text-bark">
                {{ __('auth.send_code') }}
            </button>

            {{-- Use email instead --}}
            <button type="button" wire:click="backToLogin" class="mt-3 text-center text-xs text-muted">
                Use <span class="font-bold text-bark">email</span> instead
            </button>
        </form>
    @endif
</div>

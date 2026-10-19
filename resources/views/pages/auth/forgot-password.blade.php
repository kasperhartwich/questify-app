<?php

use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiValidationException;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Forgot Password')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    /** email, then the code from the email, then the new password */
    public string $step = 'email';

    public string $email = '';

    public string $code = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $email = request()->query('email');

        if (is_string($email)) {
            $this->email = $email;
        }
    }

    public function sendCode(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $this->api->auth()->forgotPassword($this->email);
            $this->code = '';
            $this->step = 'code';
        } catch (ApiValidationException $e) {
            foreach ($e->errors as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        } catch (ApiException $e) {
            $this->reportApiFailure($e, $this->step === 'code' ? 'code' : 'email');
        }
    }

    /**
     * The code is only checked together with the new password, so a wrong one
     * comes back from savePassword() and lands the player here again.
     */
    public function confirmCode(): void
    {
        $this->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => __('auth.reset_code_wrong'),
            'code.digits' => __('auth.reset_code_wrong'),
        ]);

        $this->step = 'password';
    }

    public function savePassword(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $this->api->auth()->resetPasswordWithCode($this->email, $this->code, $this->password, $this->password_confirmation);
        } catch (ApiValidationException $e) {
            if (isset($e->errors['code'])) {
                $this->reset('code', 'password', 'password_confirmation');
                $this->step = 'code';
                $this->addError('code', __('auth.reset_code_wrong'));

                return;
            }

            foreach ($e->errors as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        } catch (ApiException $e) {
            $this->reportApiFailure($e, 'password');

            return;
        }

        session()->flash('status', __('auth.password_reset_done'));

        $this->redirect('/login?'.http_build_query(['email' => $this->email]), navigate: true);
    }

    /**
     * The reset endpoints are rate limited: say so in the app's own words
     * instead of passing on the backend's "Too Many Attempts.".
     */
    private function reportApiFailure(ApiException $e, string $field): void
    {
        if ($e->statusCode === 429) {
            $this->addError($field, __('auth.reset_too_many'));

            return;
        }

        $this->dispatch('api-error', message: $e->getMessage());
    }

    public function changeEmail(): void
    {
        $this->reset('code', 'password', 'password_confirmation');
        $this->step = 'email';
    }
};
?>

<div class="flex h-full flex-col overflow-hidden bg-cream px-5 pb-6 pt-2">
    {{-- Back + Logo --}}
    <div class="flex items-center gap-2.5 pb-5 pt-1">
        <a href="/login" class="flex h-[36px] w-[36px] items-center justify-center rounded-[11px] bg-cream-dark" wire:navigate>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div class="flex items-center gap-1.5">
            <x-questify-logo :size="22" variant="light" />
            <span class="font-heading text-sm font-extrabold tracking-tight text-bark">Questify</span>
        </div>
    </div>

    {{-- Icon --}}
    <div class="relative mb-4 flex h-16 w-16 items-center justify-center rounded-[18px] bg-amber-100">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#C8811A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0110 0v4"/>
        </svg>
    </div>

    @if ($step === 'email')
        {{-- Heading --}}
        <h1 class="mb-1 mt-4 font-heading text-[24px] font-[800] leading-tight text-bark">{{ __('auth.forgot_password_title') }}</h1>
        <p class="mb-[22px] text-[13px] text-muted">{{ __('auth.forgot_password_subtitle') }}</p>

        {{-- Email form --}}
        <form wire:submit="sendCode" class="flex flex-1 flex-col">
            <div class="mb-4">
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.email') }}</label>
                <div class="relative">
                    <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-muted"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                    </div>
                    <input type="email" wire:model="email" autocomplete="email" placeholder="{{ __('auth.email_placeholder') }}" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-3.5 text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" required />
                </div>
                @error('email') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full rounded-[14px] bg-amber-400 px-4 py-[13px] text-center font-heading text-sm font-bold text-bark" wire:loading.attr="disabled" wire:loading.class="opacity-50">
                {{ __('auth.send_reset_code') }}
            </button>
        </form>
    @elseif ($step === 'code')
        {{-- Code from the email --}}
        <h1 class="mb-1 mt-4 font-heading text-[24px] font-[800] leading-tight text-bark">{{ __('auth.reset_password') }}</h1>
        <p class="mb-1.5 text-[13px] text-muted">{{ __('auth.reset_code_sent') }}</p>
        <button type="button" wire:click="changeEmail" class="mb-6 self-start text-[13px] font-bold text-bark">{{ $email }}</button>

        <form wire:submit="confirmCode" class="flex flex-1 flex-col">
            <label class="mb-2.5 block text-center text-[9px] font-bold uppercase tracking-wider text-muted">{{ __('auth.enter_6_digit_code') }}</label>
            <x-code-boxes wire-model="code" inputmode="numeric" />
            @error('code') <p class="mt-2 text-center text-[10px] text-coral">{{ $message }}</p> @enderror

            <button type="button" wire:click="sendCode" class="mt-4 text-center text-[11px] font-semibold text-forest-400" wire:loading.attr="disabled">{{ __('auth.resend') }}</button>

            <div class="mt-auto pt-6">
                <button type="submit" class="w-full rounded-[14px] bg-forest-600 px-4 py-3.5 text-center font-heading text-sm font-bold text-white transition-opacity" wire:loading.attr="disabled" wire:loading.class="opacity-50">
                    {{ __('auth.reset_code_next') }}
                </button>
            </div>
        </form>
    @else
        {{-- New password --}}
        <h1 class="mb-1 mt-4 font-heading text-[24px] font-[800] leading-tight text-bark">{{ __('auth.new_password_title') }}</h1>
        <p class="mb-[22px] text-[13px] text-muted">{{ __('auth.new_password_subtitle') }}</p>

        <form wire:submit="savePassword" class="flex flex-1 flex-col">
            <div class="mb-2.5">
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('auth.new_password') }}</label>
                <div class="relative">
                    <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-muted"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                    <input type="password" wire:model="password" name="password" autocomplete="new-password" placeholder="{{ __('auth.new_password') }}" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-3.5 text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" required />
                </div>
                @error('password') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.password_confirmation') }}</label>
                <div class="relative">
                    <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-muted"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                    <input type="password" wire:model="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="{{ __('general.password_confirmation') }}" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-3.5 text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" required />
                </div>
            </div>

            <button type="submit" class="w-full rounded-[14px] bg-amber-400 px-4 py-[13px] text-center font-heading text-sm font-bold text-bark" wire:loading.attr="disabled" wire:loading.class="opacity-50">
                {{ __('auth.save_new_password') }}
            </button>
        </form>
    @endif

    {{-- Back to login link --}}
    <p class="mt-auto text-center text-[13px] text-muted">
        <a href="/login" class="font-semibold text-forest-400" wire:navigate>{{ __('auth.back_to_login') }}</a>
    </p>
</div>

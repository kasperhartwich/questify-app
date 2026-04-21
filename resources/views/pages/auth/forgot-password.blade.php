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

    public string $email = '';

    public bool $linkSent = false;

    public function sendResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $this->api->auth()->forgotPassword($this->email);
            $this->linkSent = true;
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

    @if (! $linkSent)
        {{-- Heading --}}
        <h1 class="mb-1 mt-4 font-heading text-[24px] font-[800] leading-tight text-bark">{{ __('auth.forgot_password_title') }}</h1>
        <p class="mb-[22px] text-[13px] text-muted">{{ __('auth.forgot_password_subtitle') }}</p>

        {{-- Email form --}}
        <form wire:submit="sendResetLink" class="flex flex-1 flex-col">
            <div class="mb-4">
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted">{{ __('general.email') }}</label>
                <div class="relative">
                    <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-muted"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                    </div>
                    <input type="email" wire:model="email" placeholder="{{ __('auth.email_placeholder') }}" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-3.5 text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" required />
                </div>
                @error('email') <p class="mt-1 text-[10px] text-coral">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full rounded-[14px] bg-amber-400 px-4 py-[13px] text-center font-heading text-sm font-bold text-bark">
                {{ __('auth.send_reset_link') }}
            </button>
        </form>
    @else
        {{-- Success State --}}
        <h1 class="mb-1 mt-4 font-heading text-[24px] font-[800] leading-tight text-bark">{{ __('auth.reset_password') }}</h1>
        <p class="mb-[22px] text-[13px] text-muted">{{ __('auth.reset_link_sent') }}</p>

        <div class="mb-4 rounded-[14px] bg-[#D4EDE4] p-4 text-[13px] font-medium text-forest-600">
            {{ $email }}
        </div>
    @endif

    {{-- Back to login link --}}
    <p class="mt-auto text-center text-[13px] text-muted">
        <a href="/login" class="font-semibold text-forest-400" wire:navigate>{{ __('auth.back_to_login') }}</a>
    </p>
</div>

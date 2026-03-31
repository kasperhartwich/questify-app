<?php

use App\Auth\QuestifyApiGuard;
use App\Exceptions\Api\ApiValidationException;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
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

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $response = $this->api->auth()->register(
                $this->name,
                $this->email,
                $this->password,
                $this->password_confirmation,
            );

            /** @var QuestifyApiGuard $guard */
            $guard = Auth::guard();
            $guard->login($response['data']['user'], $response['data']['token']);

            $this->redirect('/discover/list');
        } catch (ApiValidationException $e) {
            foreach ($e->errors as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }
};
?>

<div class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
    <div class="w-full max-w-sm">
        <h2 class="mb-6 text-center font-heading text-2xl font-extrabold text-bark dark:text-white">{{ __('general.register') }}</h2>

        <form wire:submit="register" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-muted dark:text-gray-300">{{ __('general.name') }}</label>
                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    class="mt-1 w-full rounded-xl border-2 border-cream-border bg-white px-4 py-3 text-bark dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    required
                />
                @error('name') <p class="mt-1 text-sm text-coral">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-muted dark:text-gray-300">{{ __('general.email') }}</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    class="mt-1 w-full rounded-xl border-2 border-cream-border bg-white px-4 py-3 text-bark dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    required
                />
                @error('email') <p class="mt-1 text-sm text-coral">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-muted dark:text-gray-300">{{ __('general.password') }}</label>
                <input
                    id="password"
                    type="password"
                    wire:model="password"
                    class="mt-1 w-full rounded-xl border-2 border-cream-border bg-white px-4 py-3 text-bark dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    required
                />
                @error('password') <p class="mt-1 text-sm text-coral">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-muted dark:text-gray-300">{{ __('general.password_confirmation') }}</label>
                <input
                    id="password_confirmation"
                    type="password"
                    wire:model="password_confirmation"
                    class="mt-1 w-full rounded-xl border-2 border-cream-border bg-white px-4 py-3 text-bark dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    required
                />
            </div>

            <button type="submit" class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark hover:bg-amber-500">
                {{ __('general.register') }}
            </button>
        </form>

        {{-- Social Register --}}
        <div class="mt-6 space-y-3">
            <div class="flex items-center gap-3">
                <div class="h-px flex-1 bg-cream-border dark:bg-gray-600"></div>
                <span class="text-sm font-semibold text-muted">{{ __('general.or') }}</span>
                <div class="h-px flex-1 bg-cream-border dark:bg-gray-600"></div>
            </div>

            @foreach (['google', 'facebook', 'apple', 'microsoft'] as $provider)
                <a href="/auth/{{ $provider }}/redirect" class="block w-full rounded-xl border-[1.5px] border-cream-border bg-white px-4 py-3 text-center text-sm font-semibold text-bark hover:bg-cream-dark dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    {{ __('general.continue_with', ['provider' => ucfirst($provider)]) }}
                </a>
            @endforeach
        </div>

        <p class="mt-6 text-center text-sm text-muted dark:text-gray-400">
            {{ __('general.already_have_account') }}
            <a href="/login" class="font-semibold text-forest-600 hover:text-forest-500 dark:text-forest-400" wire:navigate>{{ __('general.login') }}</a>
        </p>
    </div>
</div>

<?php

use App\Auth\QuestifyApiGuard;
use App\Exceptions\Api\ApiAuthenticationException;
use App\Exceptions\Api\ApiValidationException;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
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

    public string $email = '';

    public string $password = '';

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $response = $this->api->auth()->login($this->email, $this->password);

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
        }
    }
};
?>

<div class="flex flex-col items-center justify-center min-h-screen px-6 py-12">
    <div class="w-full max-w-sm">
        <h2 class="mb-6 text-center text-2xl font-bold text-gray-900 dark:text-white">{{ __('general.login') }}</h2>

        <form wire:submit="login" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.email') }}</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    required
                />
                @error('email') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.password') }}</label>
                <input
                    id="password"
                    type="password"
                    wire:model="password"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    required
                />
                @error('password') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700">
                {{ __('general.login') }}
            </button>
        </form>

        {{-- Social Login --}}
        <div class="mt-6 space-y-3">
            <div class="flex items-center gap-3">
                <div class="h-px flex-1 bg-gray-300 dark:bg-gray-600"></div>
                <span class="text-sm text-gray-500">{{ __('general.or') }}</span>
                <div class="h-px flex-1 bg-gray-300 dark:bg-gray-600"></div>
            </div>

            @foreach (['google', 'facebook', 'apple', 'microsoft'] as $provider)
                <a href="/auth/{{ $provider }}/redirect" class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-center font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                    {{ __('general.continue_with', ['provider' => ucfirst($provider)]) }}
                </a>
            @endforeach
        </div>

        <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
            {{ __('general.dont_have_account') }}
            <a href="/register" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" wire:navigate>{{ __('general.register') }}</a>
        </p>
    </div>
</div>

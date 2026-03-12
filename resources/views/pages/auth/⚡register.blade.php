<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Sign Up')]
class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        $this->redirect('/discover/list');
    }
};
?>

<div class="flex flex-col items-center justify-center min-h-screen px-6 py-12">
    <div class="w-full max-w-sm">
        <h2 class="mb-6 text-center text-2xl font-bold text-gray-900 dark:text-white">{{ __('general.register') }}</h2>

        <form wire:submit="register" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.name') }}</label>
                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    required
                />
                @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

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

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.password_confirmation') }}</label>
                <input
                    id="password_confirmation"
                    type="password"
                    wire:model="password_confirmation"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    required
                />
            </div>

            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700">
                {{ __('general.register') }}
            </button>
        </form>

        {{-- Social Register --}}
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
            {{ __('general.already_have_account') }}
            <a href="/login" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" wire:navigate>{{ __('general.login') }}</a>
        </p>
    </div>
</div>

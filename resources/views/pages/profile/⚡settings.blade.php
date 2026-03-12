<?php

use App\Enums\SocialProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Title('Profile')]
class extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $locale = 'en';

    public $avatar = null;

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public bool $notifications_enabled = true;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->locale = $user->locale ?? 'en';
    }

    public function updateProfile(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
            'locale' => ['required', 'string', 'in:en,da'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = Auth::user();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->locale = $validated['locale'];

        if ($this->avatar) {
            $user->avatar_path = $this->avatar->store('avatars', 'public');
        }

        $user->save();

        app()->setLocale($validated['locale']);

        session()->flash('message', __('general.profile_updated'));
    }

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', __('auth.password'));

            return;
        }

        $user->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('password_message', __('general.password_changed'));
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/');
    }

    public function deleteAccount(): void
    {
        $user = Auth::user();
        $user->tokens()->delete();

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $user->delete();

        $this->redirect('/');
    }

    /**
     * @return array<string, bool>
     */
    public function getLinkedAccountsProperty(): array
    {
        $linked = Auth::user()->socialAccounts()->pluck('provider')->toArray();

        $accounts = [];
        foreach (SocialProvider::cases() as $provider) {
            $accounts[$provider->value] = in_array($provider->value, $linked);
        }

        return $accounts;
    }
};
?>

<div class="flex flex-col">
    <div class="space-y-6 p-4">
        {{-- Flash Messages --}}
        @if (session('message'))
            <div class="rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                {{ session('message') }}
            </div>
        @endif

        {{-- Avatar & Basic Info --}}
        <form wire:submit="updateProfile" class="space-y-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white">{{ __('general.profile') }}</h2>

            {{-- Avatar --}}
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-indigo-100 dark:bg-indigo-900/30">
                    @if (Auth::user()->avatar_path)
                        <img src="{{ Storage::url(Auth::user()->avatar_path) }}" alt="{{ __('general.avatar') }}" class="h-full w-full object-cover" />
                    @else
                        <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ substr($name, 0, 1) }}</span>
                    @endif
                </div>
                <div>
                    <input type="file" wire:model="avatar" accept="image/*" class="text-sm text-gray-500" />
                    @error('avatar') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.name') }}</label>
                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-2.5 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    required
                />
                @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.email') }}</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-2.5 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    required
                />
                @error('email') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Language Selector --}}
            <div>
                <label for="locale" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.language') }}</label>
                <select
                    id="locale"
                    wire:model="locale"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-2.5 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="en">{{ __('general.english') }}</option>
                    <option value="da">{{ __('general.danish') }}</option>
                </select>
            </div>

            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white hover:bg-indigo-700">
                {{ __('general.save') }}
            </button>
        </form>

        {{-- Change Password --}}
        <form wire:submit="changePassword" class="space-y-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white">{{ __('general.change_password') }}</h2>

            @if (session('password_message'))
                <div class="rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                    {{ session('password_message') }}
                </div>
            @endif

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.current_password') }}</label>
                <input id="current_password" type="password" wire:model="current_password" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-2.5 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
                @error('current_password') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.new_password') }}</label>
                <input id="new_password" type="password" wire:model="new_password" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-2.5 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
                @error('new_password') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.password_confirmation') }}</label>
                <input id="new_password_confirmation" type="password" wire:model="new_password_confirmation" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-2.5 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
            </div>

            <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2.5 font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                {{ __('general.change_password') }}
            </button>
        </form>

        {{-- Linked Social Accounts --}}
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <h2 class="mb-3 font-semibold text-gray-900 dark:text-white">{{ __('general.linked_accounts') }}</h2>
            <div class="space-y-2">
                @foreach ($this->linkedAccounts as $provider => $isLinked)
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ ucfirst($provider) }}</span>
                        @if ($isLinked)
                            <span class="text-sm text-green-600 dark:text-green-400">{{ __('general.linked') ?? 'Linked' }}</span>
                        @else
                            <a href="/auth/{{ $provider }}/redirect" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Connect</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Notification Preferences --}}
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <h2 class="mb-3 font-semibold text-gray-900 dark:text-white">{{ __('general.notification_preferences') }}</h2>
            <label class="flex items-center gap-3">
                <input type="checkbox" wire:model.live="notifications_enabled" class="h-5 w-5 rounded border-gray-300 text-indigo-600" />
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('general.notification_preferences') }}</span>
            </label>
        </div>

        {{-- Logout --}}
        <button wire:click="logout" class="w-full rounded-lg border border-gray-300 px-4 py-3 font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
            {{ __('general.logout') }}
        </button>

        {{-- Delete Account --}}
        <button wire:click="deleteAccount" wire:confirm="{{ __('general.delete_account_confirm') }}" class="w-full rounded-lg bg-red-600 px-4 py-3 font-semibold text-white hover:bg-red-700">
            {{ __('general.delete_account') }}
        </button>
    </div>
</div>

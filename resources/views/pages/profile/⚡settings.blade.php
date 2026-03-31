<?php

use App\Auth\QuestifyApiGuard;
use App\Enums\SocialProvider;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Title('Profile')]
class extends Component
{
    use HandlesApiErrors, WithApiClient, WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $locale = 'en';

    public $avatar = null;

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
            'locale' => ['required', 'string', 'in:en,da'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $avatarPath = null;
        if ($this->avatar) {
            $avatarPath = $this->avatar->getRealPath();
        }

        $this->tryApiCall(fn () => $this->api->user()->updateProfile([
            'name' => $validated['name'],
            'locale' => $validated['locale'],
        ], $avatarPath));

        // Update session user data
        $meResponse = $this->tryApiCall(fn () => $this->api->auth()->me());
        if ($meResponse) {
            session()->put('questify_user', $meResponse['data']);
        }

        app()->setLocale($validated['locale']);

        session()->flash('message', __('general.profile_updated'));
    }

    public function logout(): void
    {
        /** @var QuestifyApiGuard $guard */
        $guard = Auth::guard();
        $guard->logout();

        $this->redirect('/');
    }

    public function deleteAccount(): void
    {
        $this->tryApiCall(fn () => $this->api->user()->deleteAccount());

        /** @var QuestifyApiGuard $guard */
        $guard = Auth::guard();
        $guard->logout();

        $this->redirect('/');
    }

    /**
     * @return array<string, bool>
     */
    public function getLinkedAccountsProperty(): array
    {
        $accounts = [];
        foreach (SocialProvider::cases() as $provider) {
            $accounts[$provider->value] = false;
        }

        return $accounts;
    }
};
?>

<div class="flex flex-col">
    {{-- Profile Header --}}
    <div class="relative overflow-hidden bg-forest-600 px-4 py-5">
        <div class="pointer-events-none absolute right-[-30px] top-[-30px] h-[120px] w-[120px] rounded-full border-[20px] border-amber-400/10"></div>
        <div class="relative z-10 flex items-center gap-3">
            <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full border-[3px] border-white/20 bg-amber-400">
                @if (Auth::user()->avatar_path)
                    <img src="{{ Storage::url(Auth::user()->avatar_path) }}" alt="{{ __('general.avatar') }}" class="h-full w-full object-cover" />
                @else
                    <span class="font-heading text-xl font-extrabold text-bark">{{ substr($name, 0, 1) }}</span>
                @endif
            </div>
            <div>
                <h1 class="font-heading text-lg font-bold text-white">{{ $name }}</h1>
                <p class="text-xs text-white/50">{{ $email }}</p>
            </div>
        </div>
    </div>

    <div class="space-y-4 p-4">
        {{-- Flash Messages --}}
        @if (session('message'))
            <div class="rounded-xl bg-forest-50 p-3 text-sm font-medium text-forest-600 dark:bg-green-900/30 dark:text-green-400">
                {{ session('message') }}
            </div>
        @endif

        {{-- Avatar & Basic Info --}}
        <form wire:submit="updateProfile" class="space-y-4 rounded-[14px] bg-white p-4 shadow-sm dark:bg-gray-800">

            {{-- Avatar Upload --}}
            <div>
                <input type="file" wire:model="avatar" accept="image/*" class="text-sm text-muted" />
                @error('avatar') <p class="mt-1 text-sm text-coral">{{ $message }}</p> @enderror
            </div>

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-muted dark:text-gray-300">{{ __('general.name') }}</label>
                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    class="mt-1 w-full rounded-xl border-2 border-cream-border bg-white px-4 py-2.5 text-bark dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    required
                />
                @error('name') <p class="mt-1 text-sm text-coral">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-muted dark:text-gray-300">{{ __('general.email') }}</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    class="mt-1 w-full rounded-xl border-2 border-cream-border bg-white px-4 py-2.5 text-bark dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    required
                />
                @error('email') <p class="mt-1 text-sm text-coral">{{ $message }}</p> @enderror
            </div>

            {{-- Language Selector --}}
            <div>
                <label for="locale" class="block text-sm font-medium text-muted dark:text-gray-300">{{ __('general.language') }}</label>
                <select
                    id="locale"
                    wire:model="locale"
                    class="mt-1 w-full rounded-xl border-2 border-cream-border bg-white px-4 py-2.5 text-bark dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="en">{{ __('general.english') }}</option>
                    <option value="da">{{ __('general.danish') }}</option>
                </select>
            </div>

            <button type="submit" class="w-full rounded-xl bg-amber-400 px-4 py-3 font-heading text-sm font-bold text-bark hover:bg-amber-500">
                {{ __('general.save') }}
            </button>
        </form>

        {{-- Change Password --}}
        <form wire:submit="changePassword" class="space-y-4 rounded-[14px] bg-white p-4 shadow-sm dark:bg-gray-800">
            <h2 class="font-heading text-sm font-bold text-bark dark:text-white">{{ __('general.change_password') }}</h2>

            @if (session('password_message'))
                <div class="rounded-xl bg-forest-50 p-3 text-sm font-medium text-forest-600 dark:bg-green-900/30 dark:text-green-400">
                    {{ session('password_message') }}
                </div>
            @endif

            <div>
                <label for="current_password" class="block text-sm font-medium text-muted dark:text-gray-300">{{ __('general.current_password') }}</label>
                <input id="current_password" type="password" wire:model="current_password" class="mt-1 w-full rounded-xl border-2 border-cream-border bg-white px-4 py-2.5 text-bark dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
                @error('current_password') <p class="mt-1 text-sm text-coral">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="new_password" class="block text-sm font-medium text-muted dark:text-gray-300">{{ __('general.new_password') }}</label>
                <input id="new_password" type="password" wire:model="new_password" class="mt-1 w-full rounded-xl border-2 border-cream-border bg-white px-4 py-2.5 text-bark dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
                @error('new_password') <p class="mt-1 text-sm text-coral">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="new_password_confirmation" class="block text-sm font-medium text-muted dark:text-gray-300">{{ __('general.password_confirmation') }}</label>
                <input id="new_password_confirmation" type="password" wire:model="new_password_confirmation" class="mt-1 w-full rounded-xl border-2 border-cream-border bg-white px-4 py-2.5 text-bark dark:border-gray-600 dark:bg-gray-700 dark:text-white" required />
            </div>

            <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-3 font-heading text-sm font-bold text-white hover:bg-forest-700">
                {{ __('general.change_password') }}
            </button>
        </form>

        {{-- Settings & Actions --}}
        <div class="overflow-hidden rounded-[14px] bg-white shadow-sm dark:bg-gray-800">
            {{-- Linked Social Accounts --}}
            <div class="border-b border-cream-border p-4 dark:border-gray-700">
                <h2 class="mb-3 font-heading text-sm font-bold text-bark dark:text-white">{{ __('general.linked_accounts') }}</h2>
                <div class="space-y-2">
                    @foreach ($this->linkedAccounts as $provider => $isLinked)
                        <div class="flex items-center justify-between rounded-xl border-[1.5px] border-cream-border p-3 dark:border-gray-700">
                            <span class="text-sm font-medium text-bark dark:text-gray-300">{{ ucfirst($provider) }}</span>
                            @if ($isLinked)
                                <span class="text-xs font-semibold text-forest-500 dark:text-green-400">{{ __('general.linked') ?? 'Linked' }}</span>
                            @else
                                <a href="/auth/{{ $provider }}/redirect" class="text-xs font-semibold text-forest-600 dark:text-forest-400">Connect</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Notification Preferences --}}
            <div class="border-b border-cream-border p-4 dark:border-gray-700">
                <label class="flex items-center gap-3">
                    <input type="checkbox" wire:model.live="notifications_enabled" class="h-5 w-5 rounded border-cream-border text-forest-600" />
                    <span class="text-sm font-medium text-bark dark:text-gray-300">{{ __('general.notification_preferences') }}</span>
                </label>
            </div>

            {{-- Logout --}}
            <button wire:click="logout" class="flex w-full items-center gap-3 border-b border-cream-border p-4 text-left text-sm font-medium text-coral dark:border-gray-700">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                {{ __('general.logout') }}
            </button>

            {{-- Delete Account --}}
            <button wire:click="deleteAccount" wire:confirm="{{ __('general.delete_account_confirm') }}" class="flex w-full items-center gap-3 p-4 text-left text-sm font-medium text-red-600">
                {{ __('general.delete_account') }}
            </button>
        </div>
    </div>
</div>

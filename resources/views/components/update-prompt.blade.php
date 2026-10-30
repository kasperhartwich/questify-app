<?php

use App\Services\AppUpdateService;
use App\Services\InstallChannel;
use App\Services\StoreLink;
use Livewire\Component;

new class extends Component
{
    public bool $show = false;

    public bool $required = false;

    public ?string $version = null;

    public ?int $build = null;

    public ?string $url = null;

    /**
     * Asked on start and again every time the app comes back to the front.
     */
    public function check(AppUpdateService $updates): void
    {
        $prompt = $updates->prompt();

        $this->show = $prompt !== null;
        $this->required = $prompt['required'] ?? false;
        $this->version = $prompt['version'] ?? null;
        $this->build = $prompt['build'] ?? null;
        $this->url = $prompt['url'] ?? null;
    }

    public function open(StoreLink $links): void
    {
        if ($this->url !== null) {
            $links->open($this->url);
        }
    }

    public function later(AppUpdateService $updates): void
    {
        if ($this->required) {
            return;
        }

        $updates->dismiss($this->version, $this->build);
        $this->show = false;
    }

    public function isBeta(): bool
    {
        return app(InstallChannel::class)->isBeta();
    }
};
?>

<div
    wire:init="check"
    x-data
    x-on:visibilitychange.document="if (document.visibilityState === 'visible') $wire.check()"
>
    @if ($show)
        <div class="fixed inset-0 z-[60] flex items-end justify-center" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/50" @if (! $required) wire:click="later" @endif></div>

            <div class="relative w-full max-w-md rounded-t-3xl bg-cream px-6 pb-[calc(env(safe-area-inset-bottom,0px)+24px)] pt-6 text-center shadow-xl">
                <h2 class="font-display text-xl font-bold text-bark">
                    {{ $required ? __('update.required_title') : __('update.title') }}
                </h2>

                <p class="mt-2 text-base text-bark/70">
                    @if ($required)
                        {{ __('update.required_body') }}
                    @elseif ($version !== null)
                        {{ __('update.body', ['version' => $build !== null ? "{$version} ({$build})" : $version]) }}
                    @else
                        {{ __('update.body_unnamed') }}
                    @endif
                </p>

                @if ($url !== null)
                    <button wire:click="open" class="mt-6 w-full rounded-[14px] bg-forest-600 px-4 py-3.5 text-[15px] font-bold text-white">
                        {{ $this->isBeta() ? __('update.open_testflight') : __('update.open_store') }}
                    </button>
                @endif

                @unless ($required)
                    <button wire:click="later" class="mt-2 w-full px-4 py-3 text-[14px] font-semibold text-bark/60">
                        {{ __('update.later') }}
                    </button>
                @endunless
            </div>
        </div>
    @endif
</div>

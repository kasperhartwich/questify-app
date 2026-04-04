<?php

use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $show = false;

    public string $type = 'error';

    public string $title = '';

    public string $message = '';

    public string $confirmLabel = '';

    public string $cancelLabel = '';

    public string $confirmEvent = '';

    public array $confirmParams = [];

    #[On('api-error')]
    public function showError(string $message = ''): void
    {
        $this->type = 'error';
        $this->title = __('general.something_went_wrong');
        $this->message = config('app.debug') && $message
            ? $message
            : __('general.unexpected_error');
        $this->confirmLabel = __('general.ok');
        $this->cancelLabel = '';
        $this->confirmEvent = '';
        $this->confirmParams = [];
        $this->show = true;
    }

    #[On('show-dialog')]
    public function showDialog(
        string $type = 'error',
        string $title = '',
        string $message = '',
        string $confirmLabel = '',
        string $cancelLabel = '',
        string $confirmEvent = '',
        array $confirmParams = [],
    ): void {
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->confirmLabel = $confirmLabel ?: __('general.ok');
        $this->cancelLabel = $cancelLabel;
        $this->confirmEvent = $confirmEvent;
        $this->confirmParams = $confirmParams;
        $this->show = true;
    }

    public function confirm(): void
    {
        if ($this->confirmEvent) {
            $this->dispatch($this->confirmEvent, ...$this->confirmParams);
        }

        $this->close();
    }

    public function close(): void
    {
        $this->show = false;
        $this->reset('type', 'title', 'message', 'confirmLabel', 'cancelLabel', 'confirmEvent', 'confirmParams');
    }
};
?>

<div>
    @if ($show)
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-6"
            wire:click.self="close"
        >
            <div class="fixed inset-0 bg-black/50"></div>

            {{-- Dialog --}}
            <div class="relative w-full max-w-sm rounded-2xl bg-cream p-6 text-center shadow-xl">
                {{-- Icon --}}
                <div @class([
                    'mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full',
                    'bg-coral/10' => $type === 'error' || $type === 'destructive',
                    'bg-amber-100' => $type === 'warning',
                    'bg-forest-100' => $type === 'success',
                ])>
                    @if ($type === 'error')
                        {{-- Warning triangle --}}
                        <svg class="h-7 w-7 text-coral" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    @elseif ($type === 'warning')
                        {{-- Exclamation circle --}}
                        <svg class="h-7 w-7 text-amber-dark" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    @elseif ($type === 'success')
                        {{-- Checkmark circle --}}
                        <svg class="h-7 w-7 text-forest-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    @elseif ($type === 'destructive')
                        {{-- Trash --}}
                        <svg class="h-7 w-7 text-coral" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    @endif
                </div>

                {{-- Title --}}
                <h3 class="mb-2 font-heading text-lg font-bold text-bark">
                    {{ $title }}
                </h3>

                {{-- Message --}}
                @if ($message)
                    <p class="mb-5 text-sm text-muted">
                        {{ $message }}
                    </p>
                @endif

                {{-- Buttons --}}
                <div @class([
                    'flex gap-3',
                    'flex-col' => ! $cancelLabel,
                ])>
                    @if ($cancelLabel)
                        <button
                            wire:click="close"
                            class="flex-1 rounded-xl border border-cream-border bg-transparent px-4 py-3 text-sm font-semibold text-muted transition hover:bg-cream-dark"
                        >
                            {{ $cancelLabel }}
                        </button>
                    @endif

                    <button
                        wire:click="confirm"
                        @class([
                            'rounded-xl px-4 py-3 text-sm font-semibold text-white transition',
                            'flex-1' => (bool) $cancelLabel,
                            'w-full' => ! $cancelLabel,
                            'bg-forest-600 hover:bg-forest-500' => $type !== 'destructive',
                            'bg-coral hover:bg-coral/90' => $type === 'destructive',
                        ])
                    >
                        {{ $confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

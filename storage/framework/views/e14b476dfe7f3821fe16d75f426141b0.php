<?php
use Livewire\Attributes\On;
use Livewire\Component;
?>

<div>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($show): ?>
        
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-6"
            wire:click.self="close"
        >
            <div class="fixed inset-0 bg-black/50"></div>

            
            <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl dark:bg-forest-700">
                
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-coral/10">
                    <svg class="h-7 w-7 text-coral" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>

                <h3 class="mb-2 font-heading text-lg font-bold text-bark dark:text-cream">
                    <?php echo e(__('Something went wrong')); ?>

                </h3>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(config('app.debug') && $message): ?>
                    <p class="mb-4 rounded-lg bg-forest-50 p-3 text-left text-xs text-forest-700 dark:bg-forest-800 dark:text-forest-200">
                        <?php echo e($message); ?>

                    </p>
                <?php else: ?>
                    <p class="mb-4 text-sm text-muted dark:text-forest-200">
                        <?php echo e(__('An unexpected error occurred. Our developers have been notified and are working on it.')); ?>

                    </p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <button
                    wire:click="close"
                    class="w-full rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-forest-500"
                >
                    <?php echo e(__('OK')); ?>

                </button>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH /Users/kasper/Projects/questify-app/storage/framework/views/livewire/views/da171852.blade.php ENDPATH**/ ?>
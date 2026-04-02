<?php
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
?>

<div class="relative flex min-h-screen flex-col bg-forest-600 overflow-hidden">
    
    <div class="pointer-events-none absolute right-[-60px] top-[-60px] h-[240px] w-[240px] rounded-full border-[40px]" style="border-color: rgba(245,166,35,0.07)"></div>
    <div class="pointer-events-none absolute bottom-24 left-[-50px] h-[160px] w-[160px] rounded-full border-[28px]" style="border-color: rgba(245,166,35,0.05)"></div>

    
    <div class="flex flex-1 flex-col items-center justify-center px-6">
        <?php if (isset($component)) { $__componentOriginal978df999e1c450c821dc8bf31aa146dc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal978df999e1c450c821dc8bf31aa146dc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.questify-logo','data' => ['size' => 72,'variant' => 'forest']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('questify-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 72,'variant' => 'forest']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal978df999e1c450c821dc8bf31aa146dc)): ?>
<?php $attributes = $__attributesOriginal978df999e1c450c821dc8bf31aa146dc; ?>
<?php unset($__attributesOriginal978df999e1c450c821dc8bf31aa146dc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal978df999e1c450c821dc8bf31aa146dc)): ?>
<?php $component = $__componentOriginal978df999e1c450c821dc8bf31aa146dc; ?>
<?php unset($__componentOriginal978df999e1c450c821dc8bf31aa146dc); ?>
<?php endif; ?>
        <h1 class="mt-3 font-heading text-[38px] font-[800] leading-tight tracking-tight text-white">Questify</h1>
        <p class="mt-2 text-center text-[14px] leading-relaxed text-white/50">
            Real places · Real questions<br>Real adventure
        </p>
    </div>

    
    <div class="flex flex-col gap-[11px] px-10 pb-6">
        
        <a href="/join" class="flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-[15px] font-heading text-sm font-bold text-bark" wire:navigate>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="2" y="2" width="7" height="7" rx="1.5"/><rect x="15" y="2" width="7" height="7" rx="1.5"/><rect x="2" y="15" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="2.5" height="2.5"/><rect x="18.5" y="14" width="2.5" height="2.5"/><rect x="14" y="18.5" width="2.5" height="2.5"/><rect x="18.5" y="18.5" width="2.5" height="2.5"/></svg>
            <?php echo e(__('general.join_quest')); ?>

        </a>

        
        <a href="/register" class="w-full rounded-[14px] border-[1.5px] border-white/25 px-4 py-[13px] text-center text-sm font-semibold text-white" wire:navigate>
            <?php echo e(__('general.register')); ?>

        </a>

        
        <a href="/login" class="block py-[6px] text-center text-sm font-semibold text-white/50" wire:navigate>
            <?php echo e(__('general.login')); ?>

        </a>
    </div>

    <p class="pb-8 text-center text-[11px] text-white/30"><?php echo e(__('general.no_account_needed')); ?></p>
</div><?php /**PATH /Users/kasper/Projects/questify-app/storage/framework/views/livewire/views/adbe41f9.blade.php ENDPATH**/ ?>
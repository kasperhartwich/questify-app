<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['current' => 1, 'total' => 4, 'backAction' => null, 'backUrl' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['current' => 1, 'total' => 4, 'backAction' => null, 'backUrl' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="flex items-center gap-2.5 pb-4 pt-1">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($backAction): ?>
        <button wire:click="<?php echo e($backAction); ?>" class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-[11px] bg-cream-dark">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
    <?php elseif($backUrl): ?>
        <a href="<?php echo e($backUrl); ?>" class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-[11px] bg-cream-dark" wire:navigate>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <div class="flex flex-1 gap-1">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 1; $i <= $total; $i++): ?>
            <div class="h-[3px] flex-1 rounded-[2px] <?php echo e($i <= $current ? 'bg-forest-600' : 'bg-cream-border'); ?>"></div>
        <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
    <span class="shrink-0 text-[11px] text-muted"><?php echo e(__('general.step_of', ['current' => $current, 'total' => $total])); ?></span>
</div>
<?php /**PATH /Users/kasper/Projects/questify-app/resources/views/components/step-indicator.blade.php ENDPATH**/ ?>
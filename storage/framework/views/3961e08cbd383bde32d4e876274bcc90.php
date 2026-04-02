<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'quest' => null,
    'variant' => 'discover',
    'progress' => null,
    'score' => null,
    'rank' => null,
    'status' => null,
    'ctaLabel' => null,
    'ctaUrl' => null,
    'showFavourite' => true,
]));

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

foreach (array_filter(([
    'quest' => null,
    'variant' => 'discover',
    'progress' => null,
    'score' => null,
    'rank' => null,
    'status' => null,
    'ctaLabel' => null,
    'ctaUrl' => null,
    'showFavourite' => true,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $headerColor = '#0B3D2E';

    $difficultyClass = match($quest->difficulty ?? '') {
        'hard' => 'bg-[#FCDDD7] text-[#C03A20]',
        'medium' => 'bg-[#D4EDE4] text-forest-600',
        default => 'bg-amber-100 text-amber-700',
    };
?>

<div class="relative mb-[12px] overflow-hidden rounded-[16px] bg-white shadow-sm <?php echo e($status === 'completed' ? 'opacity-70' : ($status === 'upcoming' ? 'opacity-60' : '')); ?>">
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showFavourite && auth()->check()): ?>
        <div class="absolute right-3 top-3 z-10"
            x-data="{ favourited: <?php echo \Illuminate\Support\Js::from((bool) ($quest->is_favourited ?? false))->toHtml() ?> }"
        >
            <button
                x-on:click.prevent.stop="
                    favourited = !favourited;
                    window.Livewire.find('<?php echo e($_instance->getId()); ?>').toggleCardFavourite(<?php echo e($quest->id ?? 0); ?>);
                "
                class="flex h-8 w-8 items-center justify-center rounded-[10px] bg-white/90 shadow-[0_2px_6px_rgba(0,0,0,0.15)]"
            >
                <svg x-show="!favourited" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2C1810" stroke-width="2.5" stroke-linecap="round"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                <svg x-show="favourited" x-cloak width="16" height="16" viewBox="0 0 24 24" fill="#0B3D2E" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
            </button>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <a href="<?php echo e($ctaUrl ?? '/quests/' . ($quest->id ?? '')); ?>" class="block" wire:navigate>
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($quest->cover_image_url)): ?>
        <img src="<?php echo e($quest->cover_image_url); ?>" alt="<?php echo e($quest->title); ?>" class="h-40 w-full object-cover" />
    <?php else: ?>
        <div class="relative overflow-hidden bg-forest-600 px-[16px] pb-[12px] pt-[14px]">
            <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <h3 class="font-heading text-[15px] font-bold leading-snug text-white"><?php echo e($quest->title); ?></h3>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quest->user?->name ?? null): ?>
                        <p class="mt-1 text-[12px] text-white/55"><?php echo e($quest->user->name); ?><?php echo e(!empty($quest->distance) ? ' · ' . $quest->distance : ''); ?></p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($quest->average_rating)): ?>
                    <span class="ml-2 shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-600">⭐ <?php echo e($quest->average_rating); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($progress !== null): ?>
                <div class="mt-2.5 h-[3px] w-full rounded-full bg-white/[0.18]">
                    <div class="h-full rounded-full bg-amber-400" style="width: <?php echo e($progress); ?>%"></div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div class="px-[16px] pb-[14px] pt-[12px]">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($quest->cover_image_url)): ?>
            <h3 class="font-heading text-base font-bold leading-snug text-bark"><?php echo e($quest->title); ?></h3>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <div class="flex flex-wrap items-center gap-3 text-[12px] text-muted">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($quest->estimated_duration_minutes)): ?>
                <span class="flex items-center gap-1">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <?php echo e($quest->estimated_duration_minutes); ?> <?php echo e(__('general.minutes')); ?>

                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($quest->checkpoints_count)): ?>
                <span class="flex items-center gap-1">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                    <?php echo e($quest->checkpoints_count); ?> <?php echo e(__('general.stops')); ?>

                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quest->difficulty ?? null): ?>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold <?php echo e($difficultyClass); ?>"><?php echo e(ucfirst($quest->difficulty)); ?></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($score || $rank): ?>
            <div class="mt-2 flex items-center gap-3 text-[12px] text-muted">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($score): ?>
                    <span><?php echo e(__('general.score')); ?>: <strong class="text-bark"><?php echo e(number_format($score)); ?> pts</strong></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rank): ?>
                    <span><?php echo e($rank); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ctaLabel): ?>
            <div class="mt-3">
                <span class="block w-full rounded-[12px] bg-forest-600 py-[11px] text-center text-[14px] font-bold text-white"><?php echo e($ctaLabel); ?></span>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
    </a>
</div>
<?php /**PATH /Users/kasper/Projects/questify-app/resources/views/components/quest-card.blade.php ENDPATH**/ ?>
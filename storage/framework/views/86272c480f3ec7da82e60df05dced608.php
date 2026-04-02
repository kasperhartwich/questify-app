<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e($title ?? config('app.name', 'Questify')); ?></title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

    </head>
    <body class="min-h-screen bg-cream dark:bg-forest-800 nativephp-safe-area">
        
        <?php if (isset($component)) { $__componentOriginalab5e27ce086159146bb5be096e5d3727 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab5e27ce086159146bb5be096e5d3727 = $attributes; } ?>
<?php $component = Native\Mobile\Edge\Components\Navigation\TopBar::resolve(['title' => ''.e($title ?? config('app.name', 'Questify')).'','backgroundColor' => '#0B3D2E','textColor' => '#ffffff'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('native-top-bar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Native\Mobile\Edge\Components\Navigation\TopBar::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'top-bar']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab5e27ce086159146bb5be096e5d3727)): ?>
<?php $attributes = $__attributesOriginalab5e27ce086159146bb5be096e5d3727; ?>
<?php unset($__attributesOriginalab5e27ce086159146bb5be096e5d3727); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab5e27ce086159146bb5be096e5d3727)): ?>
<?php $component = $__componentOriginalab5e27ce086159146bb5be096e5d3727; ?>
<?php unset($__componentOriginalab5e27ce086159146bb5be096e5d3727); ?>
<?php endif; ?>

        
        <main class="min-h-screen bg-cream pb-[60px]">
            <?php echo e($slot); ?>

        </main>

        
        <nav class="fixed bottom-0 left-0 right-0 z-50 flex h-[60px] items-center border-t border-black/[0.07] bg-white px-0.5">
            
            <a href="/discover/list" class="flex flex-1 flex-col items-center justify-center gap-[3px] py-2" wire:navigate>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="<?php echo e(request()->is('discover*') ? '#0B3D2E' : '#C0B8B0'); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="3,7 9,5 15,7 21,5 21,19 15,21 9,19 3,21"/>
                    <line x1="9" y1="5" x2="9" y2="19"/><line x1="15" y1="7" x2="15" y2="21"/>
                    <circle cx="15" cy="10" r="2" fill="<?php echo e(request()->is('discover*') ? '#0B3D2E' : '#C0B8B0'); ?>" stroke="none"/>
                </svg>
                <span class="whitespace-nowrap text-[7px] font-semibold tracking-[0.02em] <?php echo e(request()->is('discover*') ? 'text-forest-600' : 'text-[#C0B8B0]'); ?>"><?php echo e(__('general.discover')); ?></span>
            </a>

            
            <a href="/my-quests" class="flex flex-1 flex-col items-center justify-center gap-[3px] py-2" wire:navigate>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="<?php echo e(request()->is('my-quests*') ? '#0B3D2E' : '#C0B8B0'); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2h-2"/>
                    <rect x="9" y="1" width="6" height="4" rx="1"/>
                    <line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/>
                    <polyline points="7,9 8.5,10.5 11,8"/>
                </svg>
                <span class="whitespace-nowrap text-[7px] font-semibold tracking-[0.02em] <?php echo e(request()->is('my-quests*') ? 'text-forest-600' : 'text-[#C0B8B0]'); ?>"><?php echo e(__('general.my_quests')); ?></span>
            </a>

            
            <a href="/join" class="flex flex-1 items-center justify-center" wire:navigate>
                <div class="flex h-[34px] w-[46px] items-center justify-center rounded-[11px] bg-forest-600 shadow-[0_3px_12px_rgba(11,61,46,0.35)]">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round">
                        <rect x="2" y="2" width="7" height="7" rx="1.5"/><rect x="3.5" y="3.5" width="4" height="4" fill="white" stroke="none"/>
                        <rect x="15" y="2" width="7" height="7" rx="1.5"/><rect x="16.5" y="3.5" width="4" height="4" fill="white" stroke="none"/>
                        <rect x="2" y="15" width="7" height="7" rx="1.5"/><rect x="3.5" y="16.5" width="4" height="4" fill="white" stroke="none"/>
                        <rect x="14" y="14" width="2.5" height="2.5" fill="white" stroke="none"/>
                        <rect x="18" y="14" width="2.5" height="2.5" fill="white" stroke="none"/>
                        <rect x="14" y="18" width="2.5" height="2.5" fill="white" stroke="none"/>
                        <rect x="18" y="18" width="2.5" height="2.5" fill="white" stroke="none"/>
                    </svg>
                </div>
            </a>

            
            <a href="/create" class="flex flex-1 flex-col items-center justify-center gap-[3px] py-2" wire:navigate>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="<?php echo e(request()->is('create*') ? '#0B3D2E' : '#C0B8B0'); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                    <line x1="9" y1="9" x2="15" y2="9"/><line x1="12" y1="6" x2="12" y2="12"/>
                </svg>
                <span class="whitespace-nowrap text-[7px] font-semibold tracking-[0.02em] <?php echo e(request()->is('create*') ? 'text-forest-600' : 'text-[#C0B8B0]'); ?>"><?php echo e(__('general.create')); ?></span>
            </a>

            
            <a href="/profile" class="flex flex-1 flex-col items-center justify-center gap-[3px] py-2" wire:navigate>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="<?php echo e(request()->is('profile*') ? '#0B3D2E' : '#C0B8B0'); ?>" stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.58-7 8-7s8 3 8 7"/>
                </svg>
                <span class="whitespace-nowrap text-[7px] font-semibold tracking-[0.02em] <?php echo e(request()->is('profile*') ? 'text-forest-600' : 'text-[#C0B8B0]'); ?>"><?php echo e(__('general.profile')); ?></span>
            </a>
        </nav>

        
        <?php if (isset($component)) { $__componentOriginal6529c4a028ae21e0663a0ef763485168 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6529c4a028ae21e0663a0ef763485168 = $attributes; } ?>
<?php $component = Native\Mobile\Edge\Components\Navigation\BottomNav::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('native-bottom-nav'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Native\Mobile\Edge\Components\Navigation\BottomNav::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
            <?php if (isset($component)) { $__componentOriginal65e79709cc5dd13bb051ccf5036e597d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65e79709cc5dd13bb051ccf5036e597d = $attributes; } ?>
<?php $component = Native\Mobile\Edge\Components\Navigation\BottomNavItem::resolve(['id' => 'discover','icon' => 'map','label' => ''.e(__('general.discover')).'','url' => '/discover/list','active' => request()->is('discover*')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('native-bottom-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Native\Mobile\Edge\Components\Navigation\BottomNavItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65e79709cc5dd13bb051ccf5036e597d)): ?>
<?php $attributes = $__attributesOriginal65e79709cc5dd13bb051ccf5036e597d; ?>
<?php unset($__attributesOriginal65e79709cc5dd13bb051ccf5036e597d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65e79709cc5dd13bb051ccf5036e597d)): ?>
<?php $component = $__componentOriginal65e79709cc5dd13bb051ccf5036e597d; ?>
<?php unset($__componentOriginal65e79709cc5dd13bb051ccf5036e597d); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal65e79709cc5dd13bb051ccf5036e597d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65e79709cc5dd13bb051ccf5036e597d = $attributes; } ?>
<?php $component = Native\Mobile\Edge\Components\Navigation\BottomNavItem::resolve(['id' => 'my-quests','icon' => 'list.clipboard','label' => ''.e(__('general.my_quests')).'','url' => '/my-quests','active' => request()->is('my-quests*')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('native-bottom-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Native\Mobile\Edge\Components\Navigation\BottomNavItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65e79709cc5dd13bb051ccf5036e597d)): ?>
<?php $attributes = $__attributesOriginal65e79709cc5dd13bb051ccf5036e597d; ?>
<?php unset($__attributesOriginal65e79709cc5dd13bb051ccf5036e597d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65e79709cc5dd13bb051ccf5036e597d)): ?>
<?php $component = $__componentOriginal65e79709cc5dd13bb051ccf5036e597d; ?>
<?php unset($__componentOriginal65e79709cc5dd13bb051ccf5036e597d); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal65e79709cc5dd13bb051ccf5036e597d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65e79709cc5dd13bb051ccf5036e597d = $attributes; } ?>
<?php $component = Native\Mobile\Edge\Components\Navigation\BottomNavItem::resolve(['id' => 'join','icon' => 'qrcode','label' => ''.e(__('general.join')).'','url' => '/join','active' => request()->is('join*')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('native-bottom-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Native\Mobile\Edge\Components\Navigation\BottomNavItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65e79709cc5dd13bb051ccf5036e597d)): ?>
<?php $attributes = $__attributesOriginal65e79709cc5dd13bb051ccf5036e597d; ?>
<?php unset($__attributesOriginal65e79709cc5dd13bb051ccf5036e597d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65e79709cc5dd13bb051ccf5036e597d)): ?>
<?php $component = $__componentOriginal65e79709cc5dd13bb051ccf5036e597d; ?>
<?php unset($__componentOriginal65e79709cc5dd13bb051ccf5036e597d); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal65e79709cc5dd13bb051ccf5036e597d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65e79709cc5dd13bb051ccf5036e597d = $attributes; } ?>
<?php $component = Native\Mobile\Edge\Components\Navigation\BottomNavItem::resolve(['id' => 'create','icon' => 'mappin.circle','label' => ''.e(__('general.create')).'','url' => '/create','active' => request()->is('create*')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('native-bottom-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Native\Mobile\Edge\Components\Navigation\BottomNavItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65e79709cc5dd13bb051ccf5036e597d)): ?>
<?php $attributes = $__attributesOriginal65e79709cc5dd13bb051ccf5036e597d; ?>
<?php unset($__attributesOriginal65e79709cc5dd13bb051ccf5036e597d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65e79709cc5dd13bb051ccf5036e597d)): ?>
<?php $component = $__componentOriginal65e79709cc5dd13bb051ccf5036e597d; ?>
<?php unset($__componentOriginal65e79709cc5dd13bb051ccf5036e597d); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal65e79709cc5dd13bb051ccf5036e597d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65e79709cc5dd13bb051ccf5036e597d = $attributes; } ?>
<?php $component = Native\Mobile\Edge\Components\Navigation\BottomNavItem::resolve(['id' => 'profile','icon' => 'person.circle','label' => ''.e(__('general.profile')).'','url' => '/profile','active' => request()->is('profile*')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('native-bottom-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Native\Mobile\Edge\Components\Navigation\BottomNavItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65e79709cc5dd13bb051ccf5036e597d)): ?>
<?php $attributes = $__attributesOriginal65e79709cc5dd13bb051ccf5036e597d; ?>
<?php unset($__attributesOriginal65e79709cc5dd13bb051ccf5036e597d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65e79709cc5dd13bb051ccf5036e597d)): ?>
<?php $component = $__componentOriginal65e79709cc5dd13bb051ccf5036e597d; ?>
<?php unset($__componentOriginal65e79709cc5dd13bb051ccf5036e597d); ?>
<?php endif; ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6529c4a028ae21e0663a0ef763485168)): ?>
<?php $attributes = $__attributesOriginal6529c4a028ae21e0663a0ef763485168; ?>
<?php unset($__attributesOriginal6529c4a028ae21e0663a0ef763485168); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6529c4a028ae21e0663a0ef763485168)): ?>
<?php $component = $__componentOriginal6529c4a028ae21e0663a0ef763485168; ?>
<?php unset($__componentOriginal6529c4a028ae21e0663a0ef763485168); ?>
<?php endif; ?>

        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('error-modal', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-309906950-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split);
?>

        <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

    </body>
</html>
<?php /**PATH /Users/kasper/Projects/questify-app/resources/views/layouts/app.blade.php ENDPATH**/ ?>
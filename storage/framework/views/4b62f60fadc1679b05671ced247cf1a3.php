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

        
        <main>
            <?php echo e($slot); ?>

        </main>

        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('error-modal', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1095549182-0', $__key);

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
<?php /**PATH /Users/kasper/Projects/questify-app/resources/views/layouts/guest.blade.php ENDPATH**/ ?>
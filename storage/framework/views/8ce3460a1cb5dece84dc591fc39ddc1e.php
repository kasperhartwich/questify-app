<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['size' => 56, 'variant' => 'forest']));

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

foreach (array_filter((['size' => 56, 'variant' => 'forest']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $stroke = match($variant) {
        'light', 'amber' => '#0B3D2E',
        default => 'white',
    };
    $dotFill = match($variant) {
        'amber' => '#0B3D2E',
        default => '#F5A623',
    };
    $dotCenter = match($variant) {
        'amber' => '#F5A623',
        'dark' => '#1A1A1A',
        default => '#0B3D2E',
    };
?>

<svg <?php echo e($attributes->merge(['class' => ''])); ?> width="<?php echo e($size); ?>" height="<?php echo e($size); ?>" viewBox="0 0 84 84" fill="none" xmlns="http://www.w3.org/2000/svg">
    <line x1="46" y1="46" x2="68" y2="68" stroke="<?php echo e($stroke); ?>" stroke-width="7" stroke-linecap="round"/>
    <circle cx="40" cy="36" r="28" stroke="<?php echo e($stroke); ?>" stroke-width="7" fill="none"/>
    <circle cx="46" cy="46" r="6.5" fill="<?php echo e($dotFill); ?>"/><circle cx="46" cy="46" r="2.5" fill="<?php echo e($dotCenter); ?>"/>
    <circle cx="68" cy="68" r="6.5" fill="<?php echo e($dotFill); ?>"/><circle cx="68" cy="68" r="2.5" fill="<?php echo e($dotCenter); ?>"/>
</svg>
<?php /**PATH /Users/kasper/Projects/questify-app/resources/views/components/questify-logo.blade.php ENDPATH**/ ?>
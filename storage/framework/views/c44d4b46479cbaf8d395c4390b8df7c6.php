<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'wireModel' => '',
    'length' => 6,
    'inputmode' => 'text',
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
    'wireModel' => '',
    'length' => 6,
    'inputmode' => 'text',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div
    x-data="{
        code: <?php if ((object) ($wireModel) instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e($wireModel->value()); ?>')<?php echo e($wireModel->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e($wireModel); ?>')<?php endif; ?>,
        length: <?php echo e($length); ?>,
        boxes: Array(<?php echo e($length); ?>).fill(''),
        focusIndex: 0,
        init() {
            this.syncFromCode()
            this.$watch('code', () => this.syncFromCode())
            this.$nextTick(() => this.$refs['box0']?.focus())
        },
        syncFromCode() {
            const chars = (this.code || '').split('')
            this.boxes = Array(this.length).fill('').map((_, i) => chars[i] || '')
            this.focusIndex = Math.min(chars.length, this.length - 1)
        },
        handleInput(index, event) {
            const val = event.target.value.slice(-1).toUpperCase()
            this.boxes[index] = val
            this.code = this.boxes.join('')
            if (val && index < this.length - 1) {
                this.$refs['box' + (index + 1)].focus()
            } else if (val && this.boxes.every(b => b)) {
                this.$el.closest('form')?.requestSubmit()
            }
        },
        handleKeydown(index, event) {
            if (event.key === 'Backspace' && !this.boxes[index] && index > 0) {
                this.boxes[index - 1] = ''
                this.code = this.boxes.join('')
                this.$refs['box' + (index - 1)].focus()
            }
        },
        handlePaste(event) {
            event.preventDefault()
            const pasted = (event.clipboardData.getData('text') || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, this.length)
            pasted.split('').forEach((ch, i) => { this.boxes[i] = ch })
            this.code = this.boxes.join('')
            const nextIndex = Math.min(pasted.length, this.length - 1)
            this.$refs['box' + nextIndex].focus()
            if (this.boxes.every(b => b)) {
                this.$el.closest('form')?.requestSubmit()
            }
        },
    }"
    class="flex justify-center gap-[8px]"
>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 0; $i < $length; $i++): ?>
        <input
            type="text"
            maxlength="1"
            inputmode="<?php echo e($inputmode); ?>"
            x-ref="box<?php echo e($i); ?>"
            :value="boxes[<?php echo e($i); ?>]"
            @input="handleInput(<?php echo e($i); ?>, $event)"
            @keydown="handleKeydown(<?php echo e($i); ?>, $event)"
            @paste="handlePaste($event)"
            @focus="focusIndex = <?php echo e($i); ?>"
            :class="{
                'border-forest-600 bg-[#F0FAF5]': boxes[<?php echo e($i); ?>],
                'border-amber-400 bg-white': !boxes[<?php echo e($i); ?>] && focusIndex === <?php echo e($i); ?>,
                'border-cream-border bg-white': !boxes[<?php echo e($i); ?>] && focusIndex !== <?php echo e($i); ?>,
            }"
            class="flex h-[58px] w-0 min-w-0 flex-1 items-center justify-center rounded-[13px] border-2 text-center font-heading text-2xl font-extrabold text-bark focus:outline-none"
        />
    <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/kasper/Projects/questify-app/resources/views/components/code-boxes.blade.php ENDPATH**/ ?>
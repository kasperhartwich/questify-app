<style>
    .mapbox-quest-marker {
        background-color: #0B3D2E;
        border: 3px solid white;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
<div class="flex flex-col bg-cream">
    
    <div class="flex items-center gap-2 px-[16px] pb-[10px] pt-[6px]">
        <div class="flex flex-1 items-center gap-2 rounded-[13px] border-[1.5px] border-cream-border bg-white px-[14px] py-[11px]">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8A8078" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="<?php echo e(__('general.search_quests_near_you')); ?>"
                class="w-full border-none bg-transparent p-0 text-[13px] text-bark placeholder-[#B0A898] focus:outline-none focus:ring-0"
            />
        </div>
        <button
            x-data="{ open: false }"
            @click="$dispatch('toggle-filters')"
            class="flex h-[44px] w-[44px] shrink-0 items-center justify-center rounded-[13px] bg-forest-600"
        >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><path d="M3 6h18M7 12h10M11 18h2"/></svg>
        </button>
    </div>

    
    <div x-data="{ showFilters: false }" @toggle-filters.window="showFilters = !showFilters">
        <div x-show="showFilters" x-transition class="flex gap-2 px-[16px] pb-2">
            <select wire:model.live="category" class="flex-1 rounded-[13px] border-[1.5px] border-cream-border bg-white px-3 py-2 text-xs text-bark">
                <option value=""><?php echo e(__('general.all_categories')); ?></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </select>
            <select wire:model.live="difficulty" class="flex-1 rounded-[13px] border-[1.5px] border-cream-border bg-white px-3 py-2 text-xs text-bark">
                <option value=""><?php echo e(__('general.all_difficulties')); ?></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $difficulties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $diff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <option value="<?php echo e($diff->value); ?>"><?php echo e(ucfirst($diff->value)); ?></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </select>
        </div>
    </div>

    
    <div class="relative mx-[16px] mb-[12px] h-[120px] overflow-hidden rounded-[14px]"
        x-data
        x-init="
            mapboxgl.accessToken = <?php echo \Illuminate\Support\Js::from(config('services.mapbox.token'))->toHtml() ?>;
            const miniMap = new mapboxgl.Map({
                container: $refs.miniMap,
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [12.5683, 55.6761],
                zoom: 12,
                attributionControl: false,
                interactive: false,
            });
            miniMap.on('load', () => {
                const quests = <?php echo \Illuminate\Support\Js::from(
                    collect($quests)->filter(fn($q) => !empty($q->starting_checkpoint->latitude ?? ($q->checkpoints[0]->latitude ?? null)))
                    ->map(fn($q) => [
                        'lat' => (float) ($q->starting_checkpoint->latitude ?? $q->checkpoints[0]->latitude),
                        'lng' => (float) ($q->starting_checkpoint->longitude ?? $q->checkpoints[0]->longitude),
                    ])->values()->all()
                )->toHtml() ?>;
                quests.forEach(q => {
                    const el = document.createElement('div');
                    el.className = 'mapbox-quest-marker';
                    el.style.cssText = 'width:16px;height:16px;border-width:2px;';
                    new mapboxgl.Marker({ element: el }).setLngLat([q.lng, q.lat]).addTo(miniMap);
                });
                if (quests.length) {
                    const bounds = new mapboxgl.LngLatBounds();
                    quests.forEach(q => bounds.extend([q.lng, q.lat]));
                    miniMap.fitBounds(bounds, { padding: 20 });
                }
            });
        "
    >
        <div x-ref="miniMap" wire:ignore style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;"></div>
        
        <a href="/discover/map" class="absolute inset-0 z-10" wire:navigate></a>
        
        <div class="absolute bottom-2.5 right-2.5 z-20 rounded-[10px] bg-white px-2.5 py-1 text-[10px] font-semibold text-forest-600 shadow-md">
            <?php echo e(count($quests)); ?> <?php echo e(__('general.quests_nearby')); ?>

        </div>
    </div>

    
    <div class="flex items-center justify-between px-[16px] pb-2 pt-3">
        <h2 class="font-heading text-[16px] font-bold text-bark"><?php echo e(__('general.nearby_quests')); ?></h2>
        <a href="/discover/map" class="text-[13px] font-semibold text-forest-400" wire:navigate><?php echo e(__('general.see_all')); ?> &rarr;</a>
    </div>

    
    <div class="space-y-[12px] px-[16px] pb-5">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $quests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal0356ec8a1f70d4f8ae8a7b2b42dca8f7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0356ec8a1f70d4f8ae8a7b2b42dca8f7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.quest-card','data' => ['quest' => $quest,'variant' => 'discover','ctaLabel' => __('general.start_quest')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('quest-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['quest' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($quest),'variant' => 'discover','cta-label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('general.start_quest'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0356ec8a1f70d4f8ae8a7b2b42dca8f7)): ?>
<?php $attributes = $__attributesOriginal0356ec8a1f70d4f8ae8a7b2b42dca8f7; ?>
<?php unset($__attributesOriginal0356ec8a1f70d4f8ae8a7b2b42dca8f7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0356ec8a1f70d4f8ae8a7b2b42dca8f7)): ?>
<?php $component = $__componentOriginal0356ec8a1f70d4f8ae8a7b2b42dca8f7; ?>
<?php unset($__componentOriginal0356ec8a1f70d4f8ae8a7b2b42dca8f7); ?>
<?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            <div class="py-12 text-center text-muted">
                <p><?php echo e(__('general.no_quests_found')); ?></p>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($nextCursor)): ?>
            <button wire:click="$set('cursor', '<?php echo e($nextCursor); ?>')" class="mt-2 w-full rounded-[12px] bg-forest-600 px-4 py-[11px] text-sm font-bold text-white">
                <?php echo e(__('general.load_more')); ?>

            </button>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php /**PATH /Users/kasper/Projects/questify-app/resources/views/pages/discover/quest-list-view.blade.php ENDPATH**/ ?>
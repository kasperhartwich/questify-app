<div class="flex min-h-screen flex-col bg-cream">

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($step === 1): ?>
        <div class="flex flex-1 flex-col px-4">
            
            <?php if (isset($component)) { $__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.step-indicator','data' => ['current' => 1,'total' => 4,'backUrl' => '/']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('step-indicator'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['current' => 1,'total' => 4,'back-url' => '/']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd)): ?>
<?php $attributes = $__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd; ?>
<?php unset($__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd)): ?>
<?php $component = $__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd; ?>
<?php unset($__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd); ?>
<?php endif; ?>

            <h1 class="font-heading text-[22px] font-extrabold text-bark"><?php echo e(__('general.quest_info')); ?></h1>
            <p class="mb-5 mt-1 text-[13px] text-muted"><?php echo e(__('general.quest_info_subtitle')); ?></p>

            <div class="flex flex-1 flex-col gap-4 pb-4">
                
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('general.quest_name')); ?></label>
                    <input
                        type="text"
                        wire:model="title"
                        class="w-full rounded-[12px] border-2 border-cream-border bg-white px-3.5 py-3 text-[14px] font-semibold text-bark placeholder-muted/50 focus:border-forest-600 focus:outline-none focus:ring-0"
                        placeholder="<?php echo e(__('quests.title')); ?>"
                    />
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[10px] text-coral"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('quests.description')); ?></label>
                    <textarea
                        wire:model="description"
                        rows="3"
                        class="min-h-[72px] w-full rounded-[12px] border-2 border-cream-border bg-white px-3.5 py-3 text-[13px] text-bark placeholder-muted/50 focus:border-forest-600 focus:outline-none focus:ring-0"
                        placeholder="<?php echo e(__('quests.description')); ?>"
                    ></textarea>
                </div>

                
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('general.category')); ?></label>
                    <div class="flex flex-wrap gap-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <button
                                type="button"
                                wire:click="$set('categoryId', '<?php echo e($id); ?>')"
                                class="rounded-full border-[1.5px] px-[14px] py-[7px] text-[13px] font-semibold transition-colors
                                    <?php echo e($categoryId == $id
                                        ? 'border-forest-600 bg-forest-600 text-white'
                                        : 'border-cream-border bg-white text-muted'); ?>"
                            >
                                <?php echo e($name); ?>

                            </button>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['categoryId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[10px] text-coral"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('general.difficulty')); ?></label>
                    <div class="flex rounded-[11px] bg-cream-dark p-[3px]">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Enums\Difficulty::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $diff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <button
                                type="button"
                                wire:click="$set('difficulty', '<?php echo e($diff->value); ?>')"
                                class="flex-1 rounded-[9px] py-2 text-center text-[13px] font-semibold transition-all
                                    <?php echo e($difficulty === $diff->value
                                        ? 'bg-white text-bark shadow-sm'
                                        : 'text-muted'); ?>"
                            >
                                <?php echo e(ucfirst($diff->value)); ?>

                            </button>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['difficulty'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[10px] text-coral"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('general.language')); ?></label>
                    <div class="flex items-center gap-2">
                        <span class="text-[15px]"><?php echo e(app()->getLocale() === 'da' ? "\u{1F1E9}\u{1F1F0}" : "\u{1F1EC}\u{1F1E7}"); ?></span>
                        <span class="text-[14px] font-semibold text-bark"><?php echo e(app()->getLocale() === 'da' ? __('general.danish') : __('general.english')); ?></span>
                        <a href="/settings" class="ml-auto text-[13px] font-semibold text-forest-400" wire:navigate><?php echo e(__('general.change')); ?></a>
                    </div>
                </div>

                
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('general.cover_image')); ?></label>
                    <input type="file" wire:model="coverImage" accept="image/*" class="w-full text-xs text-muted" />
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['coverImage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[10px] text-coral"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            
            <div class="pb-4">
                <button wire:click="nextStep" class="flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark shadow-sm">
                    <?php echo e(__('general.next_add_checkpoints')); ?> &rarr;
                </button>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($step === 2): ?>
        <div class="flex flex-1 flex-col"
            x-data="{
                map: null,
                markers: [],
                initMap() {
                    mapboxgl.accessToken = <?php echo \Illuminate\Support\Js::from(config('services.mapbox.token'))->toHtml() ?>;
                    this.map = new mapboxgl.Map({
                        container: this.$refs.createMap,
                        style: 'mapbox://styles/mapbox/streets-v12',
                        center: [12.5683, 55.6761],
                        zoom: 13,
                        attributionControl: false,
                    });
                    this.map.on('load', () => {
                        <?php $__currentLoopData = $checkpoints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cpIndex => $checkpoint): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($checkpoint['latitude'] && $checkpoint['longitude']): ?>
                                this.addMarker(<?php echo e($cpIndex); ?>, <?php echo e($checkpoint['latitude']); ?>, <?php echo e($checkpoint['longitude']); ?>);
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    });
                    this.map.on('click', (e) => {
                        const nextIndex = this.markers.length;
                        window.Livewire.find('<?php echo e($_instance->getId()); ?>').addCheckpoint();
                        window.Livewire.find('<?php echo e($_instance->getId()); ?>').updateCheckpointCoordinates(nextIndex, e.lngLat.lat, e.lngLat.lng);
                        this.addMarker(nextIndex, e.lngLat.lat, e.lngLat.lng);
                    });
                },
                addMarker(index, lat, lng) {
                    const el = document.createElement('div');
                    el.className = 'create-map-pin';
                    const span = document.createElement('span');
                    span.className = 'create-map-pin-num';
                    span.textContent = index + 1;
                    el.appendChild(span);
                    const marker = new mapboxgl.Marker({ element: el, draggable: true })
                        .setLngLat([lng, lat])
                        .addTo(this.map);
                    marker.on('dragend', () => {
                        const lngLat = marker.getLngLat();
                        window.Livewire.find('<?php echo e($_instance->getId()); ?>').updateCheckpointCoordinates(index, lngLat.lat, lngLat.lng);
                    });
                    this.markers.push(marker);
                },
                locateUser() {
                    if (!navigator.geolocation) return;
                    navigator.geolocation.getCurrentPosition((pos) => {
                        this.map.flyTo({ center: [pos.coords.longitude, pos.coords.latitude], zoom: 15 });
                    });
                },
                focusCheckpoint(lat, lng) {
                    if (this.map && lat && lng) {
                        this.map.flyTo({ center: [lng, lat], zoom: 16 });
                    }
                }
            }"
            x-init="initMap()"
        >
            
            <div class="bg-forest-600 px-4 pb-4 pt-2">
                <div class="flex items-center gap-2.5 pb-3 pt-1">
                    <button wire:click="previousStep" class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-[11px] bg-white/15">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <div class="flex flex-1 gap-1">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 1; $i <= 4; $i++): ?>
                            <div class="h-[3px] flex-1 rounded-[2px] <?php echo e($i <= 2 ? 'bg-white' : 'bg-white/30'); ?>"></div>
                        <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <span class="shrink-0 text-[11px] text-white/70"><?php echo e(__('general.step_of', ['current' => 2, 'total' => 4])); ?></span>
                </div>
                <h1 class="font-heading text-[22px] font-extrabold text-white"><?php echo e(__('general.add_checkpoints')); ?></h1>
            </div>

            
            <style>
                .create-map-pin {
                    width: 28px; height: 28px; background: #0B3D2E; border: 2.5px solid white;
                    border-radius: 50% 50% 50% 0; transform: rotate(-45deg);
                    display: flex; align-items: center; justify-content: center;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.3); cursor: grab;
                }
                .create-map-pin-num {
                    transform: rotate(45deg); font-family: 'Exo 2', sans-serif;
                    font-size: 11px; font-weight: 800; color: white;
                }
            </style>
            <div class="relative h-[280px] bg-[#E4EDE4]">
                <div x-ref="createMap" wire:ignore style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;"></div>
                
                <button @click="locateUser()" type="button" class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-[11px] bg-white shadow-[0_2px_8px_rgba(0,0,0,0.15)]">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/><circle cx="12" cy="12" r="8"/></svg>
                </button>
                
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/40 to-transparent px-4 pb-3 pt-6">
                    <p class="text-center text-[13px] font-semibold text-white"><?php echo e(__('general.tap_map_to_add')); ?></p>
                </div>
            </div>

            
            <div class="flex-1 overflow-y-auto px-4 pb-4 pt-4">
                <p class="mb-3 text-[10px] font-bold uppercase tracking-wide text-muted">
                    <?php echo e(__('general.checkpoints_added', ['count' => count($checkpoints)])); ?>

                </p>

                <div class="flex flex-col gap-2.5">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $checkpoints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cpIndex => $checkpoint): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <div class="flex items-center gap-3 rounded-[12px] bg-white p-3 shadow-sm" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'cp-'.e($cpIndex).''; ?>wire:key="cp-<?php echo e($cpIndex); ?>">
                            <button
                                type="button"
                                x-on:click="focusCheckpoint(<?php echo e($checkpoint['latitude'] ?? 'null'); ?>, <?php echo e($checkpoint['longitude'] ?? 'null'); ?>)"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-forest-600 text-[11px] font-bold text-white"
                            ><?php echo e($cpIndex + 1); ?></button>
                            <div class="min-w-0 flex-1">
                                <input
                                    type="text"
                                    wire:model="checkpoints.<?php echo e($cpIndex); ?>.title"
                                    class="w-full border-none bg-transparent p-0 text-[13px] font-semibold text-bark placeholder-muted/50 focus:outline-none focus:ring-0"
                                    placeholder="<?php echo e(__('quests.title')); ?>"
                                />
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ["checkpoints.{$cpIndex}.title"];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-coral"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($checkpoint['latitude'] && $checkpoint['longitude']): ?>
                                    <p class="text-[11px] text-muted"><?php echo e(number_format($checkpoint['latitude'], 4)); ?>, <?php echo e(number_format($checkpoint['longitude'], 4)); ?></p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($checkpoints) > 1): ?>
                                <button wire:click="removeCheckpoint(<?php echo e($cpIndex); ?>)" class="shrink-0 text-muted">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            <?php else: ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                    
                    <button wire:click="addCheckpoint" class="flex items-center gap-3 rounded-[12px] border-2 border-dashed border-cream-border p-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-dashed border-cream-border">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        </div>
                        <span class="text-[13px] font-semibold text-forest-400"><?php echo e(__('general.add_another_checkpoint')); ?></span>
                    </button>
                </div>
            </div>

            
            <div class="border-t border-cream-border bg-white px-4 py-3">
                <button wire:click="nextStep" class="flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark shadow-sm">
                    <?php echo e(__('general.next_add_questions')); ?> &rarr;
                </button>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($step === 3): ?>
        <div class="flex flex-1 flex-col px-4">
            
            <?php if (isset($component)) { $__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.step-indicator','data' => ['current' => 3,'total' => 4,'backAction' => 'previousStep']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('step-indicator'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['current' => 3,'total' => 4,'back-action' => 'previousStep']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd)): ?>
<?php $attributes = $__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd; ?>
<?php unset($__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd)): ?>
<?php $component = $__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd; ?>
<?php unset($__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd); ?>
<?php endif; ?>

            <?php
                $cpI = $activeCheckpointIndex;
                $qI = $activeQuestionIndex;
                $currentCheckpoint = $checkpoints[$cpI] ?? $checkpoints[0] ?? ['title' => '', 'latitude' => null, 'longitude' => null];
                $currentQuestions = $questions[$cpI] ?? [];
            ?>

            <h1 class="font-heading text-[18px] font-extrabold text-bark">
                <?php echo e(__('general.stop_x_of_y', ['current' => $cpI + 1, 'total' => count($checkpoints)])); ?>: <?php echo e($currentCheckpoint['title'] ?: __('quests.checkpoint') . ' ' . ($cpI + 1)); ?>

            </h1>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($currentQuestions)): ?>
                <p class="mb-3 mt-0.5 text-[13px] text-muted"><?php echo e(__('quests.question')); ?> <?php echo e($qI + 1); ?></p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($currentCheckpoint['latitude'] && $currentCheckpoint['longitude']): ?>
                <div class="mb-4 flex items-center gap-2 rounded-[12px] bg-[#D4EDE4] px-[14px] py-[10px]">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" class="shrink-0 text-forest-600"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z" fill="currentColor"/></svg>
                    <div>
                        <p class="text-[12px] font-semibold text-forest-600"><?php echo e(__('general.location_confirmed')); ?></p>
                        <p class="text-[11px] text-forest-600/70"><?php echo e(number_format($currentCheckpoint['latitude'], 5)); ?>, <?php echo e(number_format($currentCheckpoint['longitude'], 5)); ?></p>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="flex-1 overflow-y-auto pb-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($currentQuestions)): ?>
                    <p class="py-8 text-center text-[13px] text-muted"><?php echo e(__('quests.no_questions')); ?></p>
                <?php else: ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $currentQuestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $qIndex => $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <div class="mb-4 flex flex-col gap-3" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'q-'.e($cpI).'-'.e($qIndex).''; ?>wire:key="q-<?php echo e($cpI); ?>-<?php echo e($qIndex); ?>">
                            
                            <div class="flex items-center justify-between">
                                <span class="text-[12px] font-bold text-muted"><?php echo e(__('quests.question')); ?> <?php echo e($qIndex + 1); ?></span>
                                <button wire:click="removeQuestion(<?php echo e($cpI); ?>, <?php echo e($qIndex); ?>)" class="text-[11px] font-semibold text-coral"><?php echo e(__('general.delete')); ?></button>
                            </div>

                            
                            <textarea
                                wire:model="questions.<?php echo e($cpI); ?>.<?php echo e($qIndex); ?>.body"
                                rows="2"
                                class="w-full rounded-[12px] border-2 border-cream-border bg-white px-3.5 py-3 text-[13px] text-bark placeholder-muted/50 focus:border-forest-600 focus:outline-none focus:ring-0"
                                placeholder="<?php echo e(__('quests.question')); ?>..."
                            ></textarea>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ["questions.{$cpI}.{$qIndex}.body"];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-coral"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            
                            <div>
                                <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('general.answer_type')); ?></label>
                                <div class="flex rounded-[11px] bg-cream-dark p-[3px]">
                                    <button
                                        type="button"
                                        wire:click="$set('questions.<?php echo e($cpI); ?>.<?php echo e($qIndex); ?>.type', '<?php echo e(\App\Enums\QuestionType::MultipleChoice->value); ?>')"
                                        class="flex-1 rounded-[9px] py-2 text-center text-[12px] font-semibold transition-all
                                            <?php echo e($question['type'] === \App\Enums\QuestionType::MultipleChoice->value ? 'bg-white text-bark shadow-sm' : 'text-muted'); ?>"
                                    >
                                        <?php echo e(__('general.multiple_choice')); ?>

                                    </button>
                                    <button
                                        type="button"
                                        wire:click="$set('questions.<?php echo e($cpI); ?>.<?php echo e($qIndex); ?>.type', '<?php echo e(\App\Enums\QuestionType::OpenText->value); ?>')"
                                        class="flex-1 rounded-[9px] py-2 text-center text-[12px] font-semibold transition-all
                                            <?php echo e($question['type'] === \App\Enums\QuestionType::OpenText->value ? 'bg-white text-bark shadow-sm' : 'text-muted'); ?>"
                                    >
                                        <?php echo e(__('general.text_answer')); ?>

                                    </button>
                                </div>
                            </div>

                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($question['type'] !== \App\Enums\QuestionType::OpenText->value): ?>
                                <div class="flex flex-col gap-2">
                                    <?php $letters = ['A', 'B', 'C', 'D', 'E', 'F']; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $question['answers']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $aIndex => $answer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                        <div
                                            class="flex items-center gap-2.5 rounded-[12px] border-[1.5px] px-3 py-2.5 transition-colors
                                                <?php echo e($answer['is_correct']
                                                    ? 'border-[#22C55E] bg-[#F0FDF4]'
                                                    : 'border-cream-border bg-white'); ?>"
                                            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'a-'.e($cpI).'-'.e($qIndex).'-'.e($aIndex).''; ?>wire:key="a-<?php echo e($cpI); ?>-<?php echo e($qIndex); ?>-<?php echo e($aIndex); ?>"
                                        >
                                            
                                            <button
                                                type="button"
                                                wire:click="$set('questions.<?php echo e($cpI); ?>.<?php echo e($qIndex); ?>.answers.<?php echo e($aIndex); ?>.is_correct', <?php echo e($answer['is_correct'] ? 'false' : 'true'); ?>)"
                                                class="flex h-[26px] w-[26px] shrink-0 items-center justify-center rounded-[8px] text-[11px] font-bold
                                                    <?php echo e($answer['is_correct']
                                                        ? 'bg-[#22C55E] text-white'
                                                        : 'bg-cream-dark text-muted'); ?>"
                                            >
                                                <?php echo e($letters[$aIndex] ?? chr(65 + $aIndex)); ?>

                                            </button>
                                            <input
                                                type="text"
                                                wire:model="questions.<?php echo e($cpI); ?>.<?php echo e($qIndex); ?>.answers.<?php echo e($aIndex); ?>.body"
                                                class="flex-1 border-none bg-transparent p-0 text-[13px] text-bark placeholder-muted/50 focus:outline-none focus:ring-0"
                                                placeholder="<?php echo e(__('quests.answer')); ?> <?php echo e($letters[$aIndex] ?? chr(65 + $aIndex)); ?>"
                                                <?php echo e($question['type'] === \App\Enums\QuestionType::TrueFalse->value ? 'disabled' : ''); ?>

                                            />
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($question['type'] !== \App\Enums\QuestionType::TrueFalse->value && count($question['answers']) > 2): ?>
                                                <button wire:click="removeAnswer(<?php echo e($cpI); ?>, <?php echo e($qIndex); ?>, <?php echo e($aIndex); ?>)" class="shrink-0 text-muted">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                                </button>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($question['type'] === \App\Enums\QuestionType::MultipleChoice->value && count($question['answers']) < 6): ?>
                                        <button wire:click="addAnswer(<?php echo e($cpI); ?>, <?php echo e($qIndex); ?>)" class="text-[12px] font-semibold text-forest-400">
                                            + <?php echo e(__('quests.answer')); ?>

                                        </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-[12px] text-muted"><?php echo e(__('sessions.open_ended_note')); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            
                            <input
                                type="text"
                                wire:model="questions.<?php echo e($cpI); ?>.<?php echo e($qIndex); ?>.hint"
                                class="w-full rounded-[12px] border-2 border-cream-border bg-white px-3.5 py-3 text-[13px] text-bark placeholder-muted/50 focus:border-forest-600 focus:outline-none focus:ring-0"
                                placeholder="<?php echo e(__('general.hint_optional')); ?>"
                            />
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <button wire:click="addQuestion(<?php echo e($cpI); ?>)" class="mt-2 flex w-full items-center justify-center gap-2 rounded-[12px] border-2 border-dashed border-cream-border py-3 text-[13px] font-semibold text-forest-400">
                    + <?php echo e(__('general.add_question')); ?>

                </button>
            </div>

            
            <div class="flex gap-3 pb-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cpI < count($checkpoints) - 1): ?>
                    <button
                        wire:click="$set('activeCheckpointIndex', <?php echo e($cpI + 1); ?>)"
                        class="flex flex-1 items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark shadow-sm"
                    >
                        <?php echo e(__('general.next_stop')); ?> &rarr;
                    </button>
                <?php else: ?>
                    <button wire:click="nextStep" class="flex flex-1 items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark shadow-sm">
                        <?php echo e(__('general.next')); ?>: <?php echo e(__('general.quest_settings')); ?> &rarr;
                    </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($step === 4): ?>
        <div class="flex flex-1 flex-col px-4">
            
            <?php if (isset($component)) { $__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.step-indicator','data' => ['current' => 4,'total' => 4,'backAction' => 'previousStep']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('step-indicator'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['current' => 4,'total' => 4,'back-action' => 'previousStep']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd)): ?>
<?php $attributes = $__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd; ?>
<?php unset($__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd)): ?>
<?php $component = $__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd; ?>
<?php unset($__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd); ?>
<?php endif; ?>

            <h1 class="mb-5 font-heading text-[22px] font-extrabold text-bark"><?php echo e(__('general.quest_settings')); ?></h1>

            <div class="flex flex-1 flex-col gap-5 overflow-y-auto pb-4">
                
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('general.visibility')); ?></label>
                    <div class="flex flex-col gap-2.5">
                        
                        <button
                            type="button"
                            wire:click="$set('visibility', 'public')"
                            class="flex items-center gap-3 rounded-[14px] border-[1.5px] px-4 py-[14px] text-left transition-colors
                                <?php echo e($visibility === 'public' ? 'border-forest-600 bg-[#F4FBF7]' : 'border-cream-border bg-white'); ?>"
                        >
                            <div class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full border-2 <?php echo e($visibility === 'public' ? 'border-forest-600' : 'border-cream-border'); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($visibility === 'public'): ?>
                                    <div class="h-2 w-2 rounded-full bg-forest-600"></div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div>
                                <p class="text-[14px] font-semibold text-bark"><?php echo e(__('general.public')); ?></p>
                                <p class="text-[12px] text-muted"><?php echo e(__('general.public_description')); ?></p>
                            </div>
                        </button>
                        
                        <button
                            type="button"
                            wire:click="$set('visibility', 'private')"
                            class="flex items-center gap-3 rounded-[14px] border-[1.5px] px-4 py-[14px] text-left transition-colors
                                <?php echo e($visibility === 'private' ? 'border-forest-600 bg-[#F4FBF7]' : 'border-cream-border bg-white'); ?>"
                        >
                            <div class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full border-2 <?php echo e($visibility === 'private' ? 'border-forest-600' : 'border-cream-border'); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($visibility === 'private'): ?>
                                    <div class="h-2 w-2 rounded-full bg-forest-600"></div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div>
                                <p class="text-[14px] font-semibold text-bark"><?php echo e(__('general.private')); ?></p>
                                <p class="text-[12px] text-muted"><?php echo e(__('general.private_description')); ?></p>
                            </div>
                        </button>
                    </div>
                </div>

                
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('general.play_modes')); ?></label>
                    <div class="flex flex-col gap-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Enums\PlayMode::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mode): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <button
                                type="button"
                                wire:click="$set('playMode', '<?php echo e($mode->value); ?>')"
                                class="flex items-center gap-3 rounded-[12px] border-[1.5px] bg-white px-4 py-3 text-left transition-colors
                                    <?php echo e($playMode === $mode->value ? 'border-forest-600' : 'border-cream-border'); ?>"
                            >
                                
                                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md <?php echo e($playMode === $mode->value ? 'bg-forest-600' : 'border-[1.5px] border-cream-border'); ?>">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($playMode === $mode->value): ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <span class="text-[14px] font-semibold text-bark"><?php echo e(str_replace('_', ' ', ucfirst($mode->value))); ?></span>
                            </button>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>

                
                <div>
                    <label class="mb-[5px] block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('general.scoring_settings')); ?></label>
                    <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white">
                        
                        <div class="flex items-center justify-between border-b border-cream-border px-4 py-3.5">
                            <div>
                                <p class="text-[14px] font-semibold text-bark"><?php echo e(__('general.speed_bonus')); ?></p>
                                <p class="text-[11px] text-muted"><?php echo e(__('general.speed_bonus_description')); ?></p>
                            </div>
                            <button
                                type="button"
                                wire:click="$toggle('scoringSpeedBonus')"
                                class="relative h-[26px] w-[44px] shrink-0 rounded-[13px] transition-colors <?php echo e($scoringSpeedBonus ? 'bg-forest-600' : 'bg-cream-border'); ?>"
                            >
                                <span class="absolute top-[2px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-all <?php echo e($scoringSpeedBonus ? 'left-[20px]' : 'left-[2px]'); ?>"></span>
                            </button>
                        </div>
                        
                        <div class="flex items-center justify-between border-b border-cream-border px-4 py-3.5">
                            <div>
                                <p class="text-[14px] font-semibold text-bark"><?php echo e(__('general.wrong_answer_penalty')); ?></p>
                                <p class="text-[11px] text-muted"><?php echo e(__('general.wrong_answer_penalty_description')); ?></p>
                            </div>
                            <button
                                type="button"
                                wire:click="$toggle('scoringWrongPenalty')"
                                class="relative h-[26px] w-[44px] shrink-0 rounded-[13px] transition-colors <?php echo e($scoringWrongPenalty ? 'bg-forest-600' : 'bg-cream-border'); ?>"
                            >
                                <span class="absolute top-[2px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-all <?php echo e($scoringWrongPenalty ? 'left-[20px]' : 'left-[2px]'); ?>"></span>
                            </button>
                        </div>
                        
                        <div class="flex items-center justify-between px-4 py-3.5">
                            <div>
                                <p class="text-[14px] font-semibold text-bark"><?php echo e(__('general.completion_bonus')); ?></p>
                                <p class="text-[11px] text-muted"><?php echo e(__('general.completion_bonus_description')); ?></p>
                            </div>
                            <button
                                type="button"
                                wire:click="$toggle('scoringCompletionBonus')"
                                class="relative h-[26px] w-[44px] shrink-0 rounded-[13px] transition-colors <?php echo e($scoringCompletionBonus ? 'bg-forest-600' : 'bg-cream-border'); ?>"
                            >
                                <span class="absolute top-[2px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-all <?php echo e($scoringCompletionBonus ? 'left-[20px]' : 'left-[2px]'); ?>"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="pb-4">
                <button wire:click="nextStep" class="flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark shadow-sm">
                    <?php echo e(__('general.review_and_publish')); ?> &rarr;
                </button>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($step === 5): ?>
        <div class="flex flex-1 flex-col px-4">
            <?php if (isset($component)) { $__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.step-indicator','data' => ['current' => 4,'total' => 4,'backAction' => 'previousStep']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('step-indicator'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['current' => 4,'total' => 4,'back-action' => 'previousStep']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd)): ?>
<?php $attributes = $__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd; ?>
<?php unset($__attributesOriginal0ad9e9fabd478bb72d410d6a9b21b8bd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd)): ?>
<?php $component = $__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd; ?>
<?php unset($__componentOriginal0ad9e9fabd478bb72d410d6a9b21b8bd); ?>
<?php endif; ?>

            <h1 class="mb-4 font-heading text-[22px] font-extrabold text-bark"><?php echo e(__('sessions.review_publish')); ?></h1>

            <div class="flex flex-1 flex-col gap-2.5 overflow-y-auto pb-4">
                
                <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white p-3.5">
                    <div class="text-[9px] font-bold uppercase tracking-widest text-muted"><?php echo e(__('quests.quest')); ?></div>
                    <h3 class="mt-1 font-heading text-sm font-bold text-bark"><?php echo e($title); ?></h3>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($description): ?>
                        <p class="mt-1 text-[11px] text-muted"><?php echo e(Str::limit($description, 100)); ?></p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white p-3.5">
                    <div class="mb-2 text-[9px] font-bold uppercase tracking-widest text-muted">
                        <?php echo e(__('quests.checkpoints')); ?>

                        <span class="font-normal normal-case text-forest-400"><?php echo e(count($checkpoints)); ?> <?php echo e(__('general.added')); ?></span>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $checkpoints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cpIndex => $checkpoint): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <div class="flex items-center gap-2 border-b border-cream-border py-1.5 last:border-b-0" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'review-cp-'.e($cpIndex).''; ?>wire:key="review-cp-<?php echo e($cpIndex); ?>">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-forest-600 text-[9px] font-bold text-white"><?php echo e($cpIndex + 1); ?></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-semibold text-bark"><?php echo e($checkpoint['title']); ?></p>
                                <p class="text-[9px] text-muted"><?php echo e(count($questions[$cpIndex] ?? [])); ?> <?php echo e(__('quests.question')); ?><?php echo e(count($questions[$cpIndex] ?? []) !== 1 ? 's' : ''); ?></p>
                            </div>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>

                
                <div class="flex gap-2">
                    <div class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white p-2.5 text-center">
                        <div class="text-[9px] text-muted"><?php echo e(__('general.visibility')); ?></div>
                        <div class="font-heading text-xs font-bold text-bark"><?php echo e(ucfirst($visibility)); ?></div>
                    </div>
                    <div class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white p-2.5 text-center">
                        <div class="text-[9px] text-muted"><?php echo e(__('general.mode')); ?></div>
                        <div class="font-heading text-xs font-bold text-bark"><?php echo e(ucfirst(str_replace('_', ' ', $playMode))); ?></div>
                    </div>
                    <div class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white p-2.5 text-center">
                        <div class="text-[9px] text-muted"><?php echo e(__('general.scoring')); ?></div>
                        <div class="font-heading text-xs font-bold text-bark"><?php echo e($scoringSpeedBonus ? 'Speed' : 'Standard'); ?></div>
                    </div>
                </div>

                
                <button wire:click="publish" class="mt-1 flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-3.5 font-heading text-[15px] font-bold text-bark shadow-sm">
                    <?php echo e(__('quests.publish')); ?> &rarr;
                </button>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/kasper/Projects/questify-app/resources/views/pages/create/quest-wizard-view.blade.php ENDPATH**/ ?>
<div class="flex min-h-screen flex-col bg-cream">
    
    <div class="px-[20px] py-[6px]">
        <h1 class="font-heading text-[24px] font-[800] text-bark"><?php echo e(__('general.my_quests')); ?></h1>
    </div>

    
    <div class="mt-[12px] flex border-b-2 border-cream-border px-[20px]">
        <a href="/my-quests" class="-mb-[2px] flex-1 border-b-2 border-b-transparent py-[12px] text-center text-[13px] font-semibold text-muted" wire:navigate>
            <?php echo e(__('general.playing')); ?>

        </a>
        <a href="/my-quests/created" class="-mb-[2px] flex-1 border-b-2 border-b-forest-600 py-[12px] text-center text-[13px] font-semibold text-forest-600">
            <?php echo e(__('general.created')); ?>

        </a>
        <a href="/my-quests" class="-mb-[2px] flex-1 border-b-2 border-b-transparent py-[12px] text-center text-[13px] font-semibold text-muted" wire:navigate>
            <?php echo e(__('general.history')); ?>

        </a>
    </div>

    
    <div class="space-y-3 p-[20px]">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $quests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
            <a href="/quests/<?php echo e($quest->id); ?>" class="block overflow-hidden rounded-[14px] bg-white shadow-sm" wire:navigate <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'created-'.e($quest->id).''; ?>wire:key="created-<?php echo e($quest->id); ?>">
                <div class="relative overflow-hidden bg-forest-600 px-4 py-3.5">
                    <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-heading text-[14px] font-bold leading-tight text-white"><?php echo e($quest->title); ?></h3>
                            <p class="mt-1 text-[11px] text-white/55"><?php echo e(ucfirst(str_replace('_', ' ', $quest->status ?? 'draft'))); ?></p>
                        </div>
                        <?php
                            $statusClass = match($quest->status ?? '') {
                                'published' => 'bg-[#D4EDE4] text-forest-600',
                                'pending_review' => 'bg-amber-100 text-amber-700',
                                default => 'bg-cream-dark text-muted',
                            };
                        ?>
                        <span class="ml-2 shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold <?php echo e($statusClass); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $quest->status ?? 'draft'))); ?></span>
                    </div>
                </div>
                <div class="flex items-center justify-between px-4 py-3">
                    <div class="flex items-center gap-3 text-[11px] text-muted">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($quest->sessions_count ?? null): ?>
                            <span><?php echo e($quest->sessions_count); ?> <?php echo e(__('general.plays')); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($quest->average_rating)): ?>
                            <span><?php echo e(number_format($quest->average_rating, 1)); ?> (<?php echo e($quest->sessions_count ?? 0); ?>)</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($quest->status ?? '') === 'draft'): ?>
                        <span class="text-[12px] font-semibold text-forest-400"><?php echo e(__('general.edit')); ?></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </a>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            
            <div class="flex flex-col items-center px-6 py-16">
                <div class="mb-5">
                    <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="120" height="120" rx="60" fill="#F0E8D6"/>
                        <path d="M20 75 Q35 55 50 65 Q65 75 80 55 Q95 35 105 50" stroke="#E5DDD0" stroke-width="3" fill="none" stroke-linecap="round"/>
                        <path d="M15 85 Q40 65 60 75 Q80 85 100 65" stroke="#E5DDD0" stroke-width="2" fill="none" stroke-linecap="round"/>
                        <circle cx="60" cy="52" r="22" fill="#0B3D2E"/>
                        <text x="60" y="60" text-anchor="middle" font-family="Exo 2, sans-serif" font-size="22" font-weight="800" fill="white">Q</text>
                        <circle cx="35" cy="42" r="4" fill="#F5A623" opacity="0.8"/>
                        <circle cx="85" cy="38" r="3" fill="#F5A623" opacity="0.6"/>
                        <circle cx="78" cy="72" r="3.5" fill="#F5A623" opacity="0.7"/>
                    </svg>
                </div>
                <h2 class="font-heading text-[20px] font-[800] text-bark"><?php echo e(__('general.no_created_quests_yet')); ?></h2>
                <p class="mt-2 whitespace-pre-line text-center text-[14px] leading-[1.6] text-muted"><?php echo e(__('general.no_created_quests_desc')); ?></p>
                <a href="/quests/create" class="mt-6 w-full rounded-[12px] bg-amber-400 py-3.5 text-center text-[14px] font-bold text-bark" wire:navigate><?php echo e(__('general.create_quest')); ?> &rarr;</a>
                <p class="mt-3 text-[13px] text-muted"><?php echo e(__('general.or_create_a_quest')); ?> <a href="/discover" class="font-semibold text-forest-600" wire:navigate><?php echo e(__('general.explore_quests')); ?></a></p>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($nextCursor)): ?>
            <button wire:click="$set('cursor', '<?php echo e($nextCursor); ?>')" class="mt-2 w-full rounded-[12px] bg-forest-600 px-4 py-3 text-[13px] font-bold text-white">
                <?php echo e(__('general.load_more')); ?>

            </button>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php /**PATH /Users/kasper/Projects/questify-app/resources/views/pages/my-quests/created-quests-view.blade.php ENDPATH**/ ?>
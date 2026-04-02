<?php
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Component;
?>

<?php
    $startCheckpoint = $questData->starting_checkpoint ?? null;
    $checkpointCount = (int) ($questData->checkpoint_count ?? 0);
    $visibleCount = min(3, $checkpointCount);
    $remainingCount = max(0, $checkpointCount - 3);
    $difficultyClass = match($questData->difficulty ?? '') {
        'hard' => 'bg-coral-light text-[#C03A20]',
        'medium' => 'bg-amber-light text-amber-dark',
        'easy' => 'bg-success text-[#0A5A3A]',
        default => 'bg-cream-dark text-muted',
    };
    $mapCenter = [
        'lng' => (float) ($startCheckpoint->longitude ?? 12.5683),
        'lat' => (float) ($startCheckpoint->latitude ?? 55.6761),
    ];
    $mapCheckpoints = $startCheckpoint ? [['lat' => (float) $startCheckpoint->latitude, 'lng' => (float) $startCheckpoint->longitude, 'num' => 1]] : [];
?>

<div class="flex flex-col"
    x-data="{
        activeTab: 'overview',
        playMode: 'solo',
        mapExpanded: false,
        map: null,
    }"
    x-init="
        mapboxgl.accessToken = <?php echo \Illuminate\Support\Js::from(config('services.mapbox.token'))->toHtml() ?>;
        map = new mapboxgl.Map({
            container: $refs.detailMap,
            style: 'mapbox://styles/mapbox/streets-v12',
            center: [<?php echo \Illuminate\Support\Js::from($mapCenter['lng'])->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($mapCenter['lat'])->toHtml() ?>],
            zoom: 13,
            attributionControl: false,
            interactive: false,
        });
        map.on('load', () => {
            const cps = <?php echo \Illuminate\Support\Js::from($mapCheckpoints)->toHtml() ?>;
            const coords = [];
            cps.forEach(cp => {
                coords.push([cp.lng, cp.lat]);
                const el = document.createElement('div');
                el.className = 'detail-map-pin';
                const span = document.createElement('span');
                span.className = 'detail-map-pin-num';
                span.textContent = cp.num;
                el.appendChild(span);
                new mapboxgl.Marker({ element: el }).setLngLat([cp.lng, cp.lat]).addTo(map);
            });
            if (coords.length > 1) {
                map.addSource('route', {
                    type: 'geojson',
                    data: { type: 'Feature', geometry: { type: 'LineString', coordinates: coords } },
                });
                map.addLayer({
                    id: 'route', type: 'line', source: 'route',
                    paint: { 'line-color': '#0B3D2E', 'line-width': 3, 'line-dasharray': [2, 1.5], 'line-opacity': 0.6 },
                });
                const bounds = new mapboxgl.LngLatBounds();
                coords.forEach(c => bounds.extend(c));
                map.fitBounds(bounds, { padding: 50 });
            }
        });
    "
>
    <style>
        .detail-map-pin {
            width: 28px; height: 28px; background: #0B3D2E; border: 2.5px solid white;
            border-radius: 50% 50% 50% 0; transform: rotate(-45deg);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .detail-map-pin-num {
            transform: rotate(45deg); font-family: 'Exo 2', sans-serif;
            font-size: 11px; font-weight: 800; color: white;
        }
    </style>

    
    <div class="relative flex-shrink-0 transition-all duration-300" :class="mapExpanded ? 'h-[70vh]' : 'h-[260px]'">
        <div x-ref="detailMap" wire:ignore style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;"></div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($remainingCount > 0): ?>
            <div class="absolute bottom-3 right-3 z-10 rounded-[10px] bg-forest-600/85 px-3 py-1.5 text-[11px] font-semibold text-white">+<?php echo e($remainingCount); ?> <?php echo e(__('general.more_stops')); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <a href="<?php echo e(url()->previous()); ?>" class="absolute left-4 top-[60px] z-10 flex h-9 w-9 items-center justify-center rounded-[11px] bg-white shadow-[0_2px_8px_rgba(0,0,0,0.15)]">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2C1810" stroke-width="2.5" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>
        </a>

        
        <button wire:click="toggleFavourite" class="absolute right-4 top-[60px] z-10 flex h-9 w-9 items-center justify-center rounded-[11px] bg-white shadow-[0_2px_8px_rgba(0,0,0,0.15)]">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isFavourited): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#0B3D2E" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
            <?php else: ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2C1810" stroke-width="2.5" stroke-linecap="round"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </button>

        
        <button @click="mapExpanded = !mapExpanded; setTimeout(() => map.resize(), 350)" class="absolute bottom-3 left-3 z-10 flex items-center gap-[5px] rounded-[10px] bg-white px-3 py-[7px] text-[12px] font-semibold text-forest-600 shadow-[0_2px_8px_rgba(0,0,0,0.1)]">
            <template x-if="!mapExpanded">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
            </template>
            <template x-if="mapExpanded">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M4 14h6v6M14 4h6v6M10 14l-7 7M21 3l-7 7"/></svg>
            </template>
            <span x-text="mapExpanded ? '<?php echo e(__('general.collapse_map')); ?>' : '<?php echo e(__('general.expand_map')); ?>'"></span>
        </button>
    </div>

    
    <div class="px-[18px] pt-[18px]">
        
        <div class="mb-2 flex items-start justify-between">
            <div class="flex-1">
                <h1 class="mb-1 font-heading text-[22px] font-extrabold leading-tight text-bark"><?php echo e($questData->title); ?></h1>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($questData->user): ?>
                    <div class="flex items-center gap-1.5 text-[13px] text-muted">
                        <div class="flex h-[22px] w-[22px] items-center justify-center rounded-full bg-[#1565C0] font-heading text-[9px] font-extrabold text-white">
                            <?php echo e(strtoupper(substr($questData->user->name ?? '', 0, 1))); ?>

                        </div>
                        <?php echo e(__('quests.by_creator', ['name' => $questData->user->name])); ?>

                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($questData->average_rating)): ?>
                <div class="ml-3 flex flex-shrink-0 flex-col items-center gap-0.5">
                    <span class="font-heading text-[22px] font-extrabold text-bark"><?php echo e(number_format($questData->average_rating, 1)); ?></span>
                    <div class="flex gap-px text-[12px] text-amber-400">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($s = 0; $s < 5; $s++): ?>
                            <span><?php echo e($s < round($questData->average_rating) ? '★' : '☆'); ?></span>
                        <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($questData->ratings_count ?? 0) > 0): ?>
                        <span class="text-[10px] text-muted"><?php echo e($questData->ratings_count); ?> <?php echo e(__('general.ratings')); ?></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <div class="mb-[14px] flex flex-wrap gap-1.5">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($questData->difficulty)): ?>
                <span class="<?php echo e($difficultyClass); ?> rounded-full px-2.5 py-[3px] text-[11px] font-bold"><?php echo e(ucfirst($questData->difficulty)); ?></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($questData->category): ?>
                <span class="rounded-full bg-amber-light px-2.5 py-[3px] text-[11px] font-bold text-amber-dark"><?php echo e($questData->category->name); ?></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <span class="rounded-full bg-[#E8EDF7] px-2.5 py-[3px] text-[11px] font-bold text-[#2A4A8A]"><?php echo e(ucfirst($questData->visibility ?? 'public')); ?></span>
        </div>

        
        <div class="mb-4 grid grid-cols-4 overflow-hidden rounded-[14px] border-[1.5px] border-cream-border bg-white">
            <div class="border-r border-cream-border px-2 py-3 text-center">
                <div class="font-heading text-[18px] font-extrabold text-forest-600"><?php echo e($checkpointCount); ?></div>
                <div class="mt-0.5 text-[10px] font-medium text-muted"><?php echo e(__('general.stops')); ?></div>
            </div>
            <div class="border-r border-cream-border px-2 py-3 text-center">
                <div class="font-heading text-[18px] font-extrabold text-forest-600"><?php echo e(number_format(($questData->total_distance_km ?? 0), 1)); ?></div>
                <div class="mt-0.5 text-[10px] font-medium text-muted">km</div>
            </div>
            <div class="border-r border-cream-border px-2 py-3 text-center">
                <div class="font-heading text-[18px] font-extrabold text-forest-600"><?php echo e($questData->estimated_duration_minutes ?? '-'); ?></div>
                <div class="mt-0.5 text-[10px] font-medium text-muted"><?php echo e(__('general.minutes')); ?></div>
            </div>
            <div class="px-2 py-3 text-center">
                <div class="font-heading text-[18px] font-extrabold text-forest-600"><?php echo e($questData->sessions_count ?? 0); ?></div>
                <div class="mt-0.5 text-[10px] font-medium text-muted"><?php echo e(__('general.plays')); ?></div>
            </div>
        </div>

        
        <div class="-mx-[18px] flex border-b-2 border-cream-border">
            <button @click="activeTab = 'overview'" class="-mb-[2px] flex-1 border-b-2 py-3 text-center text-[13px] font-semibold" :class="activeTab === 'overview' ? 'border-forest-600 text-forest-600' : 'border-transparent text-muted'"><?php echo e(__('general.overview')); ?></button>
            <button @click="activeTab = 'checkpoints'" class="-mb-[2px] flex-1 border-b-2 py-3 text-center text-[13px] font-semibold" :class="activeTab === 'checkpoints' ? 'border-forest-600 text-forest-600' : 'border-transparent text-muted'"><?php echo e(__('general.checkpoints')); ?></button>
            <button @click="activeTab = 'leaderboard'" class="-mb-[2px] flex-1 border-b-2 py-3 text-center text-[13px] font-semibold" :class="activeTab === 'leaderboard' ? 'border-forest-600 text-forest-600' : 'border-transparent text-muted'"><?php echo e(__('general.leaderboard')); ?></button>
        </div>
    </div>

    
    <div class="px-[18px] pb-6 pt-4">
        
        <div x-show="activeTab === 'overview'">
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($questData->description): ?>
                <p class="mb-4 text-[14px] leading-[1.7] text-[#4A4540]"><?php echo e($questData->description); ?></p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <h3 class="mb-2.5 font-heading text-[14px] font-bold text-bark"><?php echo e(__('general.checkpoints')); ?></h3>
            <div class="mb-4 flex flex-col gap-2">
                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($startCheckpoint): ?>
                    <div class="flex items-center gap-3 rounded-[12px] border-[1.5px] border-cream-border bg-white px-[14px] py-[11px]">
                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-forest-600 font-heading text-[12px] font-extrabold text-white">1</div>
                        <div class="flex-1">
                            <div class="text-[13px] font-semibold text-bark"><?php echo e(__('general.starting_point')); ?></div>
                            <div class="text-[11px] text-muted"><?php echo e($startCheckpoint->title ?? ''); ?></div>
                        </div>
                        <span class="rounded-full bg-success px-2 py-[2px] text-[10px] font-bold text-[#0A5A3A]"><?php echo e(__('general.start')); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 2; $i <= min(3, $checkpointCount); $i++): ?>
                    <div class="flex items-center gap-3 rounded-[12px] border-[1.5px] border-cream-border bg-white px-[14px] py-[11px]">
                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-cream-border font-heading text-[12px] font-extrabold text-muted"><?php echo e($i); ?></div>
                        <div class="flex-1">
                            <div class="text-[13px] font-semibold text-muted"><?php echo e(__('general.checkpoint')); ?> <?php echo e($i); ?></div>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#E5DDD0" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($remainingCount > 0): ?>
                    <p class="py-1 text-center text-[13px] font-semibold text-muted">+ <?php echo e($remainingCount); ?> <?php echo e(__('general.more_revealed_as_you_play')); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <h3 class="mb-2.5 font-heading text-[14px] font-bold text-bark"><?php echo e(__('general.play_mode')); ?></h3>
            <div class="mb-5 flex gap-2">
                <button @click="playMode = 'solo'" class="flex-1 rounded-[12px] border-[1.5px] px-3 py-3 text-center" :class="playMode === 'solo' ? 'border-forest-600 bg-[#F4FBF7]' : 'border-cream-border'">
                    <div class="text-[12px] font-bold" :class="playMode === 'solo' ? 'text-forest-600' : 'text-muted'"><?php echo e(__('general.solo')); ?></div>
                    <div class="text-[10px] text-muted"><?php echo e(__('general.just_you')); ?></div>
                </button>
                <button @click="playMode = 'individual'" class="flex-1 rounded-[12px] border-[1.5px] px-3 py-3 text-center" :class="playMode === 'individual' ? 'border-forest-600 bg-[#F4FBF7]' : 'border-cream-border'">
                    <div class="text-[12px] font-bold" :class="playMode === 'individual' ? 'text-forest-600' : 'text-muted'"><?php echo e(__('general.individual')); ?></div>
                    <div class="text-[10px] text-muted"><?php echo e(__('general.race_friends')); ?></div>
                </button>
                <button @click="playMode = 'teams'" class="flex-1 rounded-[12px] border-[1.5px] px-3 py-3 text-center" :class="playMode === 'teams' ? 'border-forest-600 bg-[#F4FBF7]' : 'border-cream-border'">
                    <div class="text-[12px] font-bold" :class="playMode === 'teams' ? 'text-forest-600' : 'text-muted'"><?php echo e(__('general.teams')); ?></div>
                    <div class="text-[10px] text-muted"><?php echo e(__('general.groups')); ?></div>
                </button>
            </div>

            
            <button wire:click="startQuest" class="mb-2.5 flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-[15px] font-heading text-[16px] font-bold text-bark">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                <?php echo e(__('general.start_quest')); ?>

            </button>
            <p class="text-center text-[13px] text-muted"><?php echo e(__('general.or')); ?> <span class="font-semibold text-forest-600"><?php echo e(__('general.host_a_session')); ?></span> <?php echo e(__('general.for_friends')); ?></p>
        </div>

        
        <div x-show="activeTab === 'checkpoints'" x-cloak>
            
            <div class="mb-4 flex gap-2.5 rounded-[12px] border-[1.5px] border-cream-border bg-cream-dark px-[14px] py-[11px]">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2" stroke-linecap="round" class="mt-0.5 flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <p class="text-[12px] leading-relaxed text-muted"><?php echo e(__('quests.checkpoints_hidden_note')); ?></p>
            </div>

            
            <div class="relative pl-5">
                <div class="absolute bottom-[14px] left-[10px] top-[14px] w-[2px] rounded-full bg-cream-border"></div>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($startCheckpoint): ?>
                    <div class="relative mb-3">
                        <div class="absolute -left-5 top-3 z-[2] flex h-[22px] w-[22px] items-center justify-center rounded-full border-2 border-cream bg-forest-600 font-heading text-[10px] font-extrabold text-white">1</div>
                        <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white px-[14px] py-[13px]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-heading text-[13px] font-bold text-bark"><?php echo e(__('general.starting_point')); ?></div>
                                    <div class="mt-0.5 text-[11px] text-muted"><?php echo e($startCheckpoint->title ?? ''); ?></div>
                                </div>
                                <span class="rounded-full bg-success px-[9px] py-[3px] text-[10px] font-bold text-[#0A5A3A]"><?php echo e(__('general.start')); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 flex items-center gap-1.5 text-muted">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M13 4a1 1 0 11-2 0 1 1 0 012 0zM5 20l2-6 3 3 2-6M21 4l-2 6-3-3-2 6"/></svg>
                        <span class="text-[12px] font-medium"><?php echo e(__('general.min_walk', ['minutes' => rand(5, 10)])); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 2; $i <= min(3, $checkpointCount); $i++): ?>
                    <div class="relative mb-3">
                        <div class="absolute -left-5 top-3 z-[2] flex h-[22px] w-[22px] items-center justify-center rounded-full border-2 border-cream bg-cream-border font-heading text-[10px] font-extrabold text-muted"><?php echo e($i); ?></div>
                        <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white px-[14px] py-[13px]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-[13px] font-semibold text-muted"><?php echo e(__('general.checkpoint')); ?> <?php echo e($i); ?></div>
                                </div>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E5DDD0" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            </div>
                        </div>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($i < min(3, $checkpointCount)): ?>
                        <div class="mb-3 flex items-center gap-1.5 text-cream-border">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M13 4a1 1 0 11-2 0 1 1 0 012 0zM5 20l2-6 3 3 2-6M21 4l-2 6-3-3-2 6"/></svg>
                            <span class="text-[12px] font-medium"><?php echo e(__('general.min_walk', ['minutes' => rand(3, 8)])); ?></span>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($remainingCount > 0): ?>
                    <div class="relative mb-2">
                        <div class="absolute -left-5 top-3 z-[2] h-[22px] w-[22px] rounded-full border-2 border-cream bg-cream-border"></div>
                        <div class="flex items-center justify-between rounded-[14px] border-[1.5px] border-dashed border-cream-border bg-white px-[14px] py-[13px]">
                            <div>
                                <div class="text-[13px] font-semibold text-muted">+ <?php echo e($remainingCount); ?> <?php echo e(__('general.more_checkpoints')); ?></div>
                                <div class="mt-0.5 text-[11px] text-muted"><?php echo e(__('general.revealed_as_you_play')); ?></div>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E5DDD0" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        </div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div class="mt-4">
                <button wire:click="startQuest" class="flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-[15px] font-heading text-[16px] font-bold text-bark">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    <?php echo e(__('general.start_quest_unlock')); ?>

                </button>
            </div>
        </div>

        
        <div x-show="activeTab === 'leaderboard'" x-cloak>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($questData->sessions_count ?? 0) > 0): ?>
                
                <p class="py-4 text-center text-[12px] text-muted"><?php echo e($questData->sessions_count ?? 0); ?> <?php echo e(__('general.players_total')); ?></p>

                
                <button wire:click="startQuest" class="flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-[15px] font-heading text-[16px] font-bold text-bark">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    <?php echo e(__('general.play_climb_board')); ?>

                </button>
            <?php else: ?>
                <p class="py-8 text-center text-[13px] text-muted"><?php echo e(__('general.no_leaderboard_yet')); ?></p>
                <button wire:click="startQuest" class="flex w-full items-center justify-center gap-2 rounded-[14px] bg-amber-400 px-4 py-[15px] font-heading text-[16px] font-bold text-bark">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    <?php echo e(__('general.be_the_first')); ?>

                </button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div><?php /**PATH /Users/kasper/Projects/questify-app/storage/framework/views/livewire/views/f55c1e41.blade.php ENDPATH**/ ?>
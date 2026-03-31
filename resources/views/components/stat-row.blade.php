@props(['stats' => []])

<div class="mx-3.5 flex overflow-hidden rounded-[14px] bg-white shadow-md">
    @foreach ($stats as $stat)
        <div class="flex flex-1 flex-col items-center border-r border-cream-border px-2 py-3 last:border-r-0">
            <span class="font-heading text-lg font-extrabold text-forest-600">{{ $stat['value'] }}</span>
            <span class="mt-0.5 text-[9px] font-medium text-muted">{{ $stat['label'] }}</span>
        </div>
    @endforeach
</div>

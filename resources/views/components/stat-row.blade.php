@props(['stats' => []])

<div class="mx-[16px] flex overflow-hidden rounded-[16px] bg-white shadow-md">
    @foreach ($stats as $stat)
        <div class="flex flex-1 flex-col items-center border-r border-cream-border px-2 py-3.5 last:border-r-0">
            <span class="font-heading text-[22px] font-extrabold leading-tight text-forest-600">{{ $stat['value'] }}</span>
            <span class="mt-1 text-[11px] font-medium text-muted">{{ $stat['label'] }}</span>
        </div>
    @endforeach
</div>

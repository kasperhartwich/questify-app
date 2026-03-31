@props(['size' => 56, 'variant' => 'forest'])

@php
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
@endphp

<svg {{ $attributes->merge(['class' => '']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 84 84" fill="none" xmlns="http://www.w3.org/2000/svg">
    <line x1="46" y1="46" x2="68" y2="68" stroke="{{ $stroke }}" stroke-width="7" stroke-linecap="round"/>
    <circle cx="40" cy="36" r="28" stroke="{{ $stroke }}" stroke-width="7" fill="none"/>
    <circle cx="46" cy="46" r="6.5" fill="{{ $dotFill }}"/><circle cx="46" cy="46" r="2.5" fill="{{ $dotCenter }}"/>
    <circle cx="68" cy="68" r="6.5" fill="{{ $dotFill }}"/><circle cx="68" cy="68" r="2.5" fill="{{ $dotCenter }}"/>
</svg>

@props(['status'])

{{-- Shows what the OS allows. Anything other than "granted" is tappable, so
     the row itself is the way to ask for the permission. --}}
@php
    $label = match ($status) {
        'granted' => __('general.permission_granted'),
        'denied', 'permanently_denied' => __('general.permission_denied'),
        default => __('general.permission_ask'),
    };
@endphp

<span @class([
    'rounded-full px-[10px] py-[4px] text-[11px] font-bold',
    'bg-[#D4EDE4] text-[#0A5A3A]' => $status === 'granted',
    'bg-red-50 text-coral' => in_array($status, ['denied', 'permanently_denied'], true),
    'bg-cream-dark text-muted' => ! in_array($status, ['granted', 'denied', 'permanently_denied'], true),
])>{{ $label }}</span>

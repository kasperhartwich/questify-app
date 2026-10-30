@props(['code'])

@if ($code === 'da')
    <svg {{ $attributes->merge(['class' => 'inline-block rounded-[2px]']) }} viewBox="0 0 37 28" aria-hidden="true">
        <rect width="37" height="28" fill="#C8102E"/>
        <path d="M12 0v28M0 14h37" stroke="#fff" stroke-width="4"/>
    </svg>
@else
    <svg {{ $attributes->merge(['class' => 'inline-block rounded-[2px]']) }} viewBox="0 0 60 40" aria-hidden="true">
        <rect width="60" height="40" fill="#012169"/>
        <path d="M0 0l60 40M60 0L0 40" stroke="#fff" stroke-width="8"/>
        <path d="M0 0l60 40M60 0L0 40" stroke="#C8102E" stroke-width="3"/>
        <path d="M30 0v40M0 20h60" stroke="#fff" stroke-width="12"/>
        <path d="M30 0v40M0 20h60" stroke="#C8102E" stroke-width="7"/>
    </svg>
@endif

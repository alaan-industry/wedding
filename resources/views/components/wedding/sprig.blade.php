@props(['filled' => false])

@php
    $leaves = collect([[148, -1, 1], [128, 1, .95], [108, -1, .9], [88, 1, .82], [68, -1, .74], [48, 1, .64], [30, -1, .52]])
        ->map(fn (array $leaf): array => ['y' => $leaf[0], 'side' => $leaf[1], 'scale' => $leaf[2]]);
@endphp

<svg {{ $attributes->merge(['class' => 'text-moss']) }} viewBox="0 0 60 170" fill="none" aria-hidden="true">
    <path d="M30 168C27 130 34 80 30 8" stroke="currentColor" stroke-width="1" stroke-linecap="round" />

    @foreach ($leaves as $leaf)
        <g transform="translate({{ 30 + $leaf['side'] }} {{ $leaf['y'] }}) scale({{ $leaf['side'] * $leaf['scale'] }} {{ $leaf['scale'] }}) rotate(-35)">
            <path d="M0 0C6-9 19-11 28-6 20 3 7 5 0 0Z" stroke="currentColor" stroke-width=".9" @if ($filled) fill="currentColor" fill-opacity=".25" @endif />
            <path d="M2-.5C9-3 17-4.5 24-5.5" stroke="currentColor" stroke-width=".5" />
        </g>
    @endforeach

    <path d="M30 8C27 3 31-1 33 2 34 5 32 7 30 8Z" stroke="currentColor" stroke-width=".9" @if ($filled) fill="currentColor" fill-opacity=".25" @endif />
</svg>

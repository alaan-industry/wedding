@props(['side' => 'left'])

@php
    $id = 'flowers-'.Str::random(8);

    $flowers = collect([[62, 70, .95, 0, 200], [38, 195, .6, 40, 330], [76, 295, .8, 15, 160], [42, 405, .55, 70, 300], [70, 505, .7, 25, 210]])
        ->map(fn (array $flower): array => ['x' => $flower[0], 'y' => $flower[1], 'scale' => $flower[2], 'rotate' => $flower[3], 'leafAngle' => $flower[4]]);

    $blossoms = collect([[24, 130, .85], [98, 160, .7], [26, 320, .75], [98, 400, .65], [36, 545, .6]])
        ->map(fn (array $blossom): array => ['x' => $blossom[0], 'y' => $blossom[1], 'scale' => $blossom[2]]);
@endphp

<svg {{ $attributes }} viewBox="0 0 120 580" fill="none" aria-hidden="true" x-data="sideFlowers">
    <defs>
        <radialGradient id="{{ $id }}-petal" cx="50%" cy="85%" r="80%">
            <stop offset="0%" stop-color="#c48f84" />
            <stop offset="55%" stop-color="#d8b1a8" />
            <stop offset="100%" stop-color="#f3e2dc" />
        </radialGradient>
        <radialGradient id="{{ $id }}-petal-light" cx="50%" cy="85%" r="80%">
            <stop offset="0%" stop-color="#d8b1a8" />
            <stop offset="100%" stop-color="#fbf1ec" />
        </radialGradient>
        <linearGradient id="{{ $id }}-leaf-fill" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stop-color="#6f7d62" />
            <stop offset="100%" stop-color="#a9b59a" />
        </linearGradient>

        <g id="{{ $id }}-flower">
            @foreach ([0, 60, 120, 180, 240, 300] as $angle)
                <circle cy="-22" r="17" transform="rotate({{ $angle }})" fill="url(#{{ $id }}-petal-light)" stroke="#c48f84" stroke-opacity=".35" stroke-width=".8" />
            @endforeach
            @foreach ([30, 102, 174, 246, 318] as $angle)
                <circle cy="-13" r="11" transform="rotate({{ $angle }})" fill="url(#{{ $id }}-petal)" stroke="#b07d72" stroke-opacity=".4" stroke-width="1" />
            @endforeach
            <circle r="9" fill="#c48f84" />
            @foreach ([0, 60, 120, 180, 240, 300] as $angle)
                <circle cy="-4.5" r="1.3" transform="rotate({{ $angle }})" fill="#f3e2dc" />
            @endforeach
            <circle r="1.6" fill="#f3e2dc" />
        </g>

        <g id="{{ $id }}-leaf">
            <path d="M0 0C10-13 32-14 46 0 32 14 10 13 0 0Z" fill="url(#{{ $id }}-leaf-fill)" fill-opacity=".85" />
            <path d="M2 0H42" stroke="#fcfdff" stroke-opacity=".55" stroke-width=".8" />
        </g>

        <g id="{{ $id }}-blossom">
            @foreach ([0, 72, 144, 216, 288] as $angle)
                <circle cy="-5.5" r="4" transform="rotate({{ $angle }})" fill="#fbf8f2" stroke="#ebe3d4" stroke-width=".8" />
            @endforeach
            <circle r="2.4" fill="#c9b18a" />
        </g>
    </defs>

    <g @if ($side === 'right') transform="translate(120 0) scale(-1 1)" @endif>
        @foreach ($flowers as $flower)
            <g transform="translate({{ $flower['x'] }} {{ $flower['y'] }}) scale({{ $flower['scale'] }})">
                <use href="#{{ $id }}-leaf" transform="rotate({{ $flower['leafAngle'] }}) scale(1.45)" />
                <use href="#{{ $id }}-leaf" transform="rotate({{ $flower['leafAngle'] + 50 }}) scale(1.2)" />
                <g class="side-flower" @click="tap($event)" data-test="side-flower">
                    <use href="#{{ $id }}-flower" transform="rotate({{ $flower['rotate'] }})" />
                </g>
            </g>
        @endforeach

        @foreach ($blossoms as $blossom)
            <use href="#{{ $id }}-blossom" transform="translate({{ $blossom['x'] }} {{ $blossom['y'] }}) scale({{ $blossom['scale'] }})" />
        @endforeach
    </g>
</svg>

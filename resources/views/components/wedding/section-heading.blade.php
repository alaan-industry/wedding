@props([
    'eyebrow' => null,
    'title',
])

<div {{ $attributes->merge(['class' => 'reveal flex flex-col items-center text-center']) }} x-data x-intersect.once="$el.classList.add('is-visible')">
    @if ($eyebrow)
        <p class="font-sans text-xs tracking-[0.2em] text-blush-dark">{{ $eyebrow }}</p>
    @endif

    <h2 class="mt-3 font-display text-4xl leading-tight text-moss-dark sm:text-5xl">{{ $title }}</h2>

    <div class="mt-4 flex items-center gap-1 text-gold">
        <span class="h-px w-14 bg-current"></span>
        <x-wedding.sprig class="h-16 w-6 rotate-90 text-moss" filled />
        <span class="h-px w-14 bg-current"></span>
    </div>

    @if ($slot->isNotEmpty())
        <p class="mt-5 max-w-xl text-lg leading-relaxed text-ink-soft">{{ $slot }}</p>
    @endif
</div>

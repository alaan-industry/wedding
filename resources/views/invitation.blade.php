@inject('wedding', 'App\Support\Wedding')

@php
    $ceremonyStartsAt = $wedding->ceremonyStartsAt();
    $receptionStartsAt = $wedding->receptionStartsAt();
    $photos = $wedding->photoUrls();

    $heroPanels = [
        ['eyebrow' => 'Save the date', 'title' => $ceremonyStartsAt->format('m · d · Y'), 'tone' => 'from-moss-light via-paper to-linen', 'offset' => 'mt-12'],
        ['eyebrow' => 'Forever begins', 'title' => $wedding->monogram(), 'tone' => 'from-blush/60 via-paper to-ivory', 'offset' => 'mt-0', 'script' => true],
        ['eyebrow' => 'Panabo City', 'title' => config('wedding.ceremony.name'), 'tone' => 'from-sage/45 via-paper to-linen', 'offset' => 'mt-20'],
    ];
@endphp

<x-layouts::invitation
    :title="$wedding->coupleNames().' — '.$ceremonyStartsAt->format('F j, Y')"
    :image="$wedding->ogImageUrl()"
    :description="'Together with our families, we invite you to celebrate the best day of our lives on '.$ceremonyStartsAt->format('l, F j, Y').' at '.config('wedding.ceremony.name').', Panabo City.'"
>
    {{-- Sealed envelope intro --}}
    <div
        x-data="{
            open: false,
            gone: sessionStorage.getItem('invitation-opened') === '1',
            openEnvelope() {
                if (this.open) return;
                this.open = true;
                sessionStorage.setItem('invitation-opened', '1');
                window.dispatchEvent(new CustomEvent('invitation-opened'));
                setTimeout(() => this.gone = true, 2300);
            },
        }"
        x-init="document.body.classList.toggle('overflow-hidden', !gone); $watch('gone', value => document.body.classList.toggle('overflow-hidden', !value))"
        x-show="!gone"
        x-transition:leave="transition duration-1000 ease-out"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="paper-texture fixed inset-0 z-50 flex flex-col items-center justify-center px-6"
        data-test="envelope"
    >
        <p class="mb-10 font-sans text-[0.7rem] uppercase tracking-[0.4em] text-moss">
            {{ $invitation ? 'A letter for' : 'You are cordially invited' }}
        </p>

        @if ($invitation)
            <p class="-mt-6 mb-10 text-center font-script text-4xl text-ink sm:text-5xl">{{ $invitation->name }}</p>
        @endif

        <button
            type="button"
            @click="openEnvelope()"
            class="envelope group relative aspect-[3/2] w-[min(88vw,26rem)] cursor-pointer [perspective:1200px] focus:outline-none"
            :class="{ 'is-open': open }"
            aria-label="Open the invitation"
        >
            {{-- Back of the envelope --}}
            <span class="absolute inset-0 rounded-md bg-linen shadow-[0_30px_60px_-25px_rgb(51_64_79/0.45)]"></span>

            {{-- The letter inside --}}
            <span class="envelope-letter absolute inset-x-[6%] top-[6%] bottom-[6%] flex flex-col items-center justify-center rounded-sm border border-moss-light/70 bg-ivory px-4 text-center" :class="open ? 'z-20' : 'z-10'">
                <span class="font-sans text-[0.55rem] uppercase tracking-[0.35em] text-moss">Save the date</span>
                <span class="mt-2 font-display text-2xl text-moss-dark">{{ $wedding->coupleNames() }}</span>
                <span class="mt-1 text-sm tracking-[0.2em] text-ink-soft">{{ $ceremonyStartsAt->format('m · d · Y') }}</span>
            </span>

            {{-- Front pocket --}}
            <span class="absolute inset-0 z-30 rounded-md bg-paper [clip-path:polygon(0_0,50%_52%,100%_0,100%_100%,0_100%)]"></span>
            <span class="absolute inset-0 z-30 rounded-md [clip-path:polygon(0_0,50%_52%,100%_0,100%_100%,0_100%)] bg-gradient-to-t from-linen/60 to-transparent"></span>

            {{-- Flap --}}
            <span class="envelope-flap absolute inset-x-0 top-0 h-[62%] bg-linen [clip-path:polygon(0_0,100%_0,50%_100%)]" :class="open ? 'z-0' : 'z-40'"></span>

            {{-- Wax seal --}}
            <span class="envelope-seal absolute top-[62%] left-1/2 z-50 flex size-20 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-[radial-gradient(circle_at_35%_30%,#e6c4b9,#a8776b_70%)] shadow-[0_6px_14px_rgb(0_0_0/0.25),inset_0_-3px_6px_rgb(0_0_0/0.25),inset_0_3px_6px_rgb(255_255_255/0.25)] transition group-hover:scale-105">
                <span class="absolute inset-1.5 rounded-full border border-ivory/40"></span>
                <span class="font-script text-2xl text-ivory">{{ str_replace(' ', '', $wedding->monogram()) }}</span>
            </span>
        </button>

        <p class="mt-12 animate-float font-sans text-xs uppercase tracking-[0.3em] text-ink-soft" x-show="!open">Tap the seal to open</p>
    </div>

    {{-- Navigation --}}
    <header
        x-data="{ scrolled: false }"
        x-init="scrolled = window.scrollY > 40"
        @scroll.window="scrolled = window.scrollY > 40"
        class="fixed inset-x-0 top-0 z-40 transition duration-500"
        :class="scrolled ? 'bg-ivory/90 shadow-[0_10px_30px_-20px_rgb(51_64_79/0.35)] backdrop-blur-md' : 'bg-transparent'"
    >
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-10 px-6 py-3 font-sans text-[0.7rem] tracking-[0.12em] text-ink-soft md:grid md:grid-cols-[1fr_auto_1fr]">
            <div class="hidden items-center justify-end gap-8 md:flex">
                <a href="#story" class="transition hover:text-blush-dark">Our Story</a>
                <a href="#details" class="transition hover:text-blush-dark">Details</a>
                <a href="#schedule" class="transition hover:text-blush-dark">Schedule</a>
            </div>

            <a href="#top" class="group relative flex h-14 w-28 flex-col items-center justify-center" aria-label="Back to top">
                <span class="absolute inset-0 rounded-[50%] border border-gold/80 bg-ivory/70 transition group-hover:bg-ivory"></span>
                <span class="absolute inset-1 rounded-[50%] border border-gold/40"></span>
                <x-wedding.posy class="absolute -top-2 -left-3 w-9 text-blush-dark/70" />
                <span class="relative font-script text-2xl leading-none text-moss-dark">{{ $wedding->monogram() }}</span>
                <span class="relative mt-0.5 text-[0.45rem] uppercase tracking-[0.35em] text-blush-dark">Wedding</span>
            </a>

            <div class="flex items-center justify-end gap-8 md:justify-start">
                <a href="#attire" class="hidden transition hover:text-blush-dark md:inline">Attire</a>
                <a href="#wishes" class="hidden transition hover:text-blush-dark md:inline">Wishes</a>
                <a href="#faq" class="hidden transition hover:text-blush-dark md:inline">FAQ</a>
                <a href="#rsvp" class="bg-moss-dark px-5 py-2.5 uppercase tracking-[0.25em] text-ivory transition hover:bg-ink md:ml-auto">RSVP</a>
            </div>
        </nav>
    </header>

    @if ($musicUrl = $wedding->musicUrl())
        <button
            type="button"
            x-data="music(@js($musicUrl))"
            @click="toggle()"
            class="fixed right-4 bottom-4 z-40 flex size-12 cursor-pointer items-center justify-center rounded-full border border-moss/30 bg-ivory/90 text-moss-dark shadow-[0_10px_30px_-12px_rgb(51_64_79/0.5)] backdrop-blur-md transition hover:scale-105 sm:right-6 sm:bottom-6"
            :aria-pressed="playing.toString()"
            :aria-label="(playing ? 'Pause ' : 'Play ') + @js(config('wedding.music.title'))"
            data-test="music-toggle"
        >
            <span x-show="!playing"><flux:icon.musical-note class="size-5" /></span>
            <span x-show="playing" x-cloak class="flex h-4 items-end gap-0.5" aria-hidden="true">
                @foreach ([0, -0.3, -0.6] as $delay)
                    <span class="music-bar w-1 rounded-full bg-moss-dark" style="animation-delay: {{ $delay }}s"></span>
                @endforeach
            </span>
        </button>
    @endif

    <main>
        {{-- Hero --}}
        <section id="top" class="relative overflow-hidden px-6 pt-28 pb-6 sm:pb-20 lg:flex lg:min-h-svh lg:items-center lg:pt-24">
            <x-wedding.sprig class="pointer-events-none absolute top-16 -left-10 h-[28rem] w-40 -rotate-[28deg] text-moss/15 sm:left-4" />
            <x-wedding.posy class="pointer-events-none absolute top-[55%] left-[38%] hidden w-48 text-moss/10 lg:block" />
            <div class="pointer-events-none absolute -top-40 -left-40 size-[36rem] rounded-full bg-blush/15 blur-3xl"></div>

            <div x-data="dove(@js(config('wedding.dove_notes')))" class="bird-sky pointer-events-none absolute inset-0 z-10">
                <div x-ref="flier" class="absolute top-0 left-0 will-change-transform" style="transform: translate(-200px, 0)">
                    <button
                        type="button"
                        @click="greet()"
                        class="block cursor-pointer focus:outline-none"
                        :class="leaving ? 'pointer-events-none' : 'pointer-events-auto bird-respawn'"
                        aria-label="Say hello to the eagle"
                        data-test="bird"
                    >
                        <span x-ref="pose" class="block">
                            <x-wedding.bird class="w-20 drop-shadow-[0_6px_8px_rgb(51_64_79/0.2)] sm:w-28" />
                        </span>
                    </button>
                </div>

                <template x-if="burst">
                    <span class="absolute" :style="`left: ${burst.x}px; top: ${burst.y}px`">
                        @foreach (range(0, 9) as $particle)
                            <span class="bird-particle absolute" style="--angle: {{ $particle * 36 + ($particle % 2) * 12 }}deg; --distance: {{ 44 + ($particle % 3) * 18 }}px;">
                                <svg viewBox="0 0 20 18" class="{{ $particle % 2 ? 'w-2.5' : 'w-3.5' }}" aria-hidden="true"><path d="M10 17C4 12 0 9 0 5A5 5 0 0 1 10 3a5 5 0 0 1 10 2c0 4-4 7-10 12Z" fill="{{ $particle % 3 ? '#dcb7ab' : '#6b84a0' }}" /></svg>
                            </span>
                        @endforeach
                    </span>
                </template>

                <template x-teleport="body">
                    <div aria-live="polite">
                        <template x-if="note">
                            <button
                                type="button"
                                @click="note = null"
                                class="dove-note absolute z-30 w-64 cursor-pointer border border-gold/60 bg-ivory/95 px-5 py-4 text-left shadow-[0_20px_40px_-20px_rgb(51_64_79/0.45)] backdrop-blur-sm"
                                :style="`left: ${note.x}px; top: ${note.y}px`"
                                data-test="dove-note"
                            >
                                <span class="block font-sans text-[0.55rem] uppercase tracking-[0.35em] text-blush-dark">A note from the eagle</span>
                                <span class="mt-2 block text-lg leading-snug italic text-ink" x-text="note.text"></span>
                            </button>
                        </template>
                    </div>
                </template>
            </div>

            <div class="relative mx-auto grid w-full max-w-6xl items-center gap-14 lg:grid-cols-[0.95fr_1.05fr] lg:gap-12">
                <div class="reveal flex flex-col items-center text-center lg:items-start lg:text-left" x-data x-intersect.once="$el.classList.add('is-visible')">
                    <p class="font-sans text-xs tracking-[0.2em] text-blush-dark">Together with our families</p>

                    @if ($invitation)
                        <p class="mt-5 text-2xl italic text-ink">Dear {{ $invitation->name }},</p>
                    @endif

                    <h1 class="mt-4 font-display text-5xl leading-[1.08] text-moss-dark sm:text-6xl xl:text-7xl">
                        {{ config('wedding.groom.first_name') }}
                        <span class="font-serif font-light italic text-blush-dark">&amp;</span>
                        <span class="block">{{ config('wedding.bride.first_name') }}</span>
                    </h1>

                    <p class="mt-6 max-w-md font-sans text-sm leading-7 text-ink-soft">
                        Of all the days we will ever share, this will be the best one, and it would
                        not be complete without you. We joyfully invite you to celebrate the
                        beginning of our forever.
                    </p>

                    <div class="mt-8 flex items-center gap-5">
                        <span class="font-display text-2xl text-ink sm:text-3xl">{{ $ceremonyStartsAt->format('F j, Y') }}</span>
                        <span class="h-8 w-px bg-gold"></span>
                        <span class="font-sans text-[0.7rem] leading-5 uppercase tracking-[0.25em] text-ink-soft">
                            {{ $ceremonyStartsAt->format('l') }}<br>{{ $ceremonyStartsAt->format('g:i A') }}
                        </span>
                    </div>

                    <p class="mt-3 text-lg italic text-ink-soft">{{ config('wedding.ceremony.name') }} · San Vicente, Panabo City</p>

                    @if ($invitation)
                        <p class="mt-5 font-sans text-[0.7rem] uppercase tracking-[0.3em] text-moss" data-test="reserved-seats">
                            {{ $invitation->seats }} {{ Str::plural('seat', $invitation->seats) }} reserved in your honor
                        </p>
                    @endif

                    <div class="mt-9 flex flex-wrap items-center justify-center gap-6 lg:justify-start">
                        <a href="#rsvp" class="bg-moss-dark px-7 py-3.5 font-sans text-[0.7rem] uppercase tracking-[0.25em] text-ivory shadow-[0_12px_25px_-12px_rgb(51_64_79/0.6)] transition hover:-translate-y-0.5 hover:bg-ink">
                            RSVP by {{ $wedding->rsvpDeadline()->format('F j') }}
                        </a>
                        <a href="#story" class="group inline-flex items-center gap-2 font-sans text-[0.7rem] uppercase tracking-[0.25em] text-moss-dark">
                            Our story
                            <flux:icon.arrow-right variant="micro" class="transition group-hover:translate-x-1" />
                        </a>
                    </div>
                </div>

                <div class="relative grid grid-cols-3 items-start gap-3 sm:gap-4">
                    @foreach ($heroPanels as $index => $panel)
                        <figure
                            class="reveal photo-frame aspect-[3/5] bg-linen shadow-[0_25px_50px_-30px_rgb(51_64_79/0.6)] {{ $panel['offset'] }}"
                            style="transition-delay: {{ 150 + $index * 150 }}ms"
                            x-data
                            x-intersect.once="$el.classList.add('is-visible')"
                        >
                            @if ($photos[$index] ?? null)
                                <img src="{{ $photos[$index] }}" alt="{{ $wedding->coupleNames() }}" class="size-full object-cover" loading="{{ $index === 0 ? 'eager' : 'lazy' }}" />
                            @else
                                <div class="flex size-full flex-col items-center justify-center bg-gradient-to-b px-3 text-center sm:px-4 {{ $panel['tone'] }}" data-test="photo-placeholder">
                                    <x-wedding.sprig class="h-14 w-5 text-moss/40 sm:h-32 sm:w-12" filled />
                                    <span class="mt-2 font-sans text-[0.5rem] uppercase tracking-[0.2em] text-blush-dark sm:mt-4 sm:text-[0.55rem] sm:tracking-[0.3em]">{{ $panel['eyebrow'] }}</span>
                                    <span class="mt-1 sm:mt-2 {{ ($panel['script'] ?? false) ? 'font-script text-xl whitespace-nowrap sm:text-4xl' : 'font-display text-[0.7rem] sm:text-lg' }} leading-tight text-moss-dark">{{ $panel['title'] }}</span>
                                </div>
                            @endif
                        </figure>
                    @endforeach

                    <x-wedding.posy class="pointer-events-none absolute top-6 -left-6 w-20 text-blush-dark/80 sm:w-28" />
                    <x-wedding.sprig class="pointer-events-none absolute -right-2 bottom-10 h-28 w-10 rotate-[200deg] text-moss/70 sm:h-36 sm:w-14" />
                    <x-wedding.posy class="pointer-events-none absolute top-[80%] left-[28%] w-16 -scale-x-100 text-gold sm:w-24" />
                </div>
            </div>
        </section>

        {{-- Countdown --}}
        <section class="relative px-6 pt-10 pb-20 sm:py-24">
            <div class="mx-auto grid max-w-5xl items-center gap-12 md:grid-cols-[auto_1fr] md:gap-16">
                <div class="reveal relative mx-auto size-60 sm:size-72" x-data x-intersect.once="$el.classList.add('is-visible')">
                    <div class="absolute inset-4 [clip-path:polygon(25%_5%,75%_5%,100%_50%,75%_95%,25%_95%,0%_50%)]">
                        @if ($photos[3] ?? null)
                            <img src="{{ $photos[3] }}" alt="{{ $wedding->coupleNames() }}" class="size-full object-cover" loading="lazy" />
                        @else
                            <div class="flex size-full flex-col items-center justify-center bg-gradient-to-br from-moss-light via-paper to-blush/50" data-test="photo-placeholder">
                                <span class="font-script text-5xl text-moss-dark">{{ $wedding->monogram() }}</span>
                                <span class="mt-2 font-sans text-[0.55rem] uppercase tracking-[0.35em] text-blush-dark">{{ $ceremonyStartsAt->format('m . d . Y') }}</span>
                            </div>
                        @endif
                    </div>
                    <svg class="pointer-events-none absolute inset-0 size-full text-gold" viewBox="0 0 100 100" preserveAspectRatio="none" fill="none" aria-hidden="true">
                        <polygon points="25,3 75,3 99,50 75,97 25,97 1,50" stroke="currentColor" stroke-width=".5" vector-effect="non-scaling-stroke" />
                        <polygon points="25,3 75,3 99,50 75,97 25,97 1,50" stroke="currentColor" stroke-width=".5" vector-effect="non-scaling-stroke" transform="rotate(10 50 50)" opacity=".6" />
                    </svg>
                    <x-wedding.posy class="pointer-events-none absolute -top-4 -left-6 w-24 text-blush-dark" />
                    <x-wedding.posy class="pointer-events-none absolute -right-6 -bottom-4 w-24 rotate-180 text-blush-dark" />
                    <x-wedding.sprig class="pointer-events-none absolute top-1/2 -left-8 h-24 w-9 -translate-y-1/2 -rotate-12 text-moss/70" />
                </div>

                <div class="reveal text-center md:text-left" x-data x-intersect.once="$el.classList.add('is-visible')">
                    <div class="flex items-center justify-center gap-5 md:justify-start">
                        <p class="font-display text-3xl text-moss-dark sm:text-4xl">{{ $wedding->coupleNames() }}</p>
                        <span class="hidden h-px w-16 bg-gold sm:block"></span>
                    </div>
                    <p class="mt-2 text-lg italic text-ink-soft">are counting down to forever</p>

                    <div
                        x-data="{
                            target: new Date(@js($ceremonyStartsAt->toIso8601String())).getTime(),
                            parts: { days: 0, hours: 0, minutes: 0, seconds: 0 },
                            tick() {
                                const distance = Math.max(0, this.target - Date.now());
                                this.parts = {
                                    days: Math.floor(distance / 86400000),
                                    hours: Math.floor(distance / 3600000) % 24,
                                    minutes: Math.floor(distance / 60000) % 60,
                                    seconds: Math.floor(distance / 1000) % 60,
                                };
                            },
                        }"
                        x-init="tick(); setInterval(() => tick(), 1000)"
                        class="mt-8 flex items-center justify-center gap-1.5 sm:gap-6 md:justify-start"
                    >
                        @foreach (['days' => 'Days', 'hours' => 'Hours', 'minutes' => 'Minutes', 'seconds' => 'Seconds'] as $unit => $label)
                            @unless ($loop->first)
                                <x-wedding.sprig class="h-10 w-3 shrink-0 {{ $loop->even ? 'rotate-12' : '-rotate-12' }} text-sage sm:h-16 sm:w-6" filled />
                            @endunless
                            <div class="flex min-w-12 flex-col items-center sm:min-w-16">
                                <span class="font-display text-3xl lining-nums tabular-nums text-blush-dark sm:text-5xl" x-text="String(parts.{{ $unit }}).padStart(2, '0')">00</span>
                                <span class="mt-2 font-sans text-[0.55rem] uppercase tracking-[0.12em] text-ink-soft sm:text-[0.6rem] sm:tracking-[0.2em]">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>

                    <a href="{{ route('calendar') }}" class="mt-10 inline-flex items-center gap-2 font-sans text-[0.7rem] uppercase tracking-[0.25em] text-moss-dark underline decoration-gold underline-offset-8 transition hover:decoration-moss-dark">
                        <flux:icon.calendar-days variant="micro" />
                        Add to my calendar
                    </a>
                </div>
            </div>
        </section>

        {{-- Our story --}}
        <section id="story" class="scroll-mt-16 px-6 py-24 sm:py-32">
            <x-wedding.section-heading eyebrow="How it all compiled" title="Our Story">
                He writes code; she takes care of people. Somewhere between his bug fixes and
                her school bells, we found each other, and realized that the best thing either
                of us will ever build is a life together.
            </x-wedding.section-heading>

            <div class="mx-auto mt-16 grid max-w-4xl gap-8 md:grid-cols-2">
                <figure class="reveal flex flex-col overflow-hidden bg-[#2b3746] shadow-[0_30px_60px_-30px_rgb(51_64_79/0.7)]" x-data x-intersect.once="$el.classList.add('is-visible')">
                    <div class="flex items-center gap-1.5 border-b border-white/10 px-4 py-3">
                        <span class="size-2.5 rounded-full bg-blush/70"></span>
                        <span class="size-2.5 rounded-full bg-moss-light/70"></span>
                        <span class="size-2.5 rounded-full bg-sage/70"></span>
                        <span class="ml-3 font-mono text-[0.65rem] text-ivory/40">benjie@life: ~/forever</span>
                    </div>
                    <pre class="flex-1 overflow-x-auto px-5 py-6 font-mono text-[0.8rem] leading-7 text-moss-light"><span class="text-sage">$</span> git checkout -b forever
<span class="text-sage">$</span> git merge monaliza --no-ff
<span class="text-ivory/60">  Merging two hearts... 100%</span>
<span class="text-ivory/60">  0 conflicts. 1 forever.</span>
<span class="text-sage">$</span> git tag {{ $ceremonyStartsAt->format('Y.m.d') }} -m "I do"
<span class="text-sage">$</span> <span class="animate-pulse">▍</span></pre>
                    <figcaption class="border-t border-white/10 px-5 py-4 font-sans text-[0.65rem] uppercase tracking-[0.3em] text-ivory/50">From the groom · Programmer</figcaption>
                </figure>

                <figure class="reveal relative flex flex-col border border-gold/60 bg-ivory p-8 shadow-[0_30px_60px_-30px_rgb(51_64_79/0.45)] [transition-delay:150ms]" x-data x-intersect.once="$el.classList.add('is-visible')">
                    <p class="font-sans text-[0.6rem] uppercase tracking-[0.35em] text-blush-dark">Office of the Heart · Memorandum</p>
                    <p class="mt-4 font-display text-2xl text-moss-dark">Notice of Lifetime Appointment</p>

                    <dl class="mt-5 grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-base">
                        <dt class="text-ink-soft italic">Position</dt>
                        <dd class="text-ink">Husband &amp; Best Friend</dd>
                        <dt class="text-ink-soft italic">Appointee</dt>
                        <dd class="text-ink">{{ config('wedding.groom.full_name') }}</dd>
                        <dt class="text-ink-soft italic">Effective</dt>
                        <dd class="text-ink">{{ $ceremonyStartsAt->format('F j, Y') }}</dd>
                        <dt class="text-ink-soft italic">Status</dt>
                        <dd class="text-ink">Permanent, no probation</dd>
                    </dl>

                    <p class="mt-auto pt-6 font-script text-3xl text-moss-dark">Approved, with all my heart</p>
                    <figcaption class="mt-3 border-t border-moss/20 pt-4 font-sans text-[0.65rem] uppercase tracking-[0.3em] text-ink-soft">From the bride · Human Resources</figcaption>
                </figure>
            </div>
        </section>

        {{-- Ceremony & reception --}}
        <section id="details" class="scroll-mt-16 bg-paper/70 px-6 py-24 sm:py-32">
            <x-wedding.section-heading eyebrow="Where & when" title="The Celebration" />

            <div class="mx-auto mt-16 grid max-w-4xl gap-8 md:grid-cols-2">
                @foreach ([
                    ['label' => 'The Ceremony', 'icon' => 'heart', 'venue' => config('wedding.ceremony'), 'time' => $ceremonyStartsAt],
                    ['label' => 'The Reception', 'icon' => 'sparkles', 'venue' => config('wedding.reception'), 'time' => $receptionStartsAt],
                ] as $event)
                    <article class="reveal relative flex flex-col items-center bg-ivory px-8 py-14 text-center shadow-[0_25px_50px_-30px_rgb(51_64_79/0.45)]" x-data x-intersect.once="$el.classList.add('is-visible')">
                        <span class="pointer-events-none absolute inset-3 border border-gold/50"></span>
                        <x-wedding.posy class="pointer-events-none absolute -top-5 -right-5 w-20 text-blush-dark/70" />
                        <span class="flex size-14 items-center justify-center rounded-full bg-moss-light/60 text-moss-dark">
                            <flux:icon :icon="$event['icon']" variant="outline" class="size-6" />
                        </span>
                        <p class="mt-6 font-sans text-[0.7rem] uppercase tracking-[0.35em] text-blush-dark">{{ $event['label'] }}</p>
                        <p class="mt-3 font-display text-4xl text-moss-dark">{{ $event['time']->format('g:i A') }}</p>
                        <p class="mt-1 italic text-ink-soft">{{ $event['time']->format('l, F j, Y') }}</p>
                        <x-wedding.ornament class="my-6 h-4 w-32 text-gold" />
                        <p class="font-display text-2xl text-ink">{{ $event['venue']['name'] }}</p>
                        <p class="mt-1 text-base text-ink-soft">{{ $event['venue']['address'] }}</p>
                        <a href="{{ $event['venue']['map_url'] }}" target="_blank" rel="noopener" class="relative mt-8 inline-flex items-center gap-2 bg-moss-dark px-6 py-3 font-sans text-[0.7rem] uppercase tracking-[0.25em] text-ivory transition hover:bg-ink">
                            <flux:icon.map-pin variant="micro" />
                            Get directions
                        </a>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Schedule --}}
        <section id="schedule" class="scroll-mt-16 px-6 py-24 sm:py-32">
            <x-wedding.section-heading eyebrow="Order of the day" title="Schedule" />

            <ol class="relative mx-auto mt-16 max-w-2xl">
                <span class="absolute top-2 bottom-2 left-[5.5rem] w-px bg-gold/70 sm:left-1/2"></span>

                @foreach (config('wedding.timeline') as $moment)
                    <li class="reveal relative grid grid-cols-[5rem_1fr] gap-8 pb-12 last:pb-0 sm:grid-cols-2 sm:gap-16" x-data x-intersect.once="$el.classList.add('is-visible')">
                        <span class="absolute top-2 left-[5.5rem] size-3 -translate-x-1/2 rotate-45 border border-blush-dark bg-ivory ring-4 ring-ivory sm:left-1/2"></span>
                        <p class="pt-0.5 text-right font-sans text-xs uppercase tracking-[0.2em] text-blush-dark">{{ $moment['time'] }}</p>
                        <div>
                            <p class="font-display text-2xl text-moss-dark">{{ $moment['title'] }}</p>
                            <p class="mt-1 text-base text-ink-soft">{{ $moment['description'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Attire --}}
        <section id="attire" class="scroll-mt-16 bg-paper/70 px-6 py-24 sm:py-32">
            <x-wedding.section-heading eyebrow="What to wear" :title="config('wedding.dress_code.title')">
                {{ config('wedding.dress_code.description') }}
            </x-wedding.section-heading>

            <div class="reveal mx-auto mt-12 flex max-w-xl flex-wrap justify-center gap-6" x-data x-intersect.once="$el.classList.add('is-visible')">
                @foreach (config('wedding.dress_code.palette') as $swatch)
                    <div class="flex flex-col items-center gap-3">
                        <span class="size-16 rounded-full border-4 border-ivory shadow-md ring-1 ring-gold" style="background-color: {{ $swatch['hex'] }}"></span>
                        <span class="font-sans text-[0.6rem] uppercase tracking-[0.25em] text-ink-soft">{{ $swatch['name'] }}</span>
                    </div>
                @endforeach
            </div>

            <p class="mt-10 text-center italic text-ink-soft">{{ config('wedding.dress_code.avoid') }}</p>
        </section>

        {{-- Gifts, unplugged ceremony, hashtag --}}
        <section class="px-6 py-24 sm:py-32">
            <div class="mx-auto grid max-w-5xl gap-8 md:grid-cols-3">
                @foreach ([
                    ['icon' => 'gift', 'title' => 'Gifts', 'body' => config('wedding.gift_note')],
                    ['icon' => 'device-phone-mobile', 'title' => 'Unplugged Ceremony', 'body' => 'We invite you to be fully present with us. Kindly keep phones and cameras away during the ceremony; our photographers have it covered.'],
                    ['icon' => 'camera', 'title' => 'Share the Love', 'body' => 'At the reception, capture every happy moment and share it with us using '.config('wedding.hashtag').'.'],
                ] as $note)
                    <article class="reveal flex flex-col items-center text-center" x-data x-intersect.once="$el.classList.add('is-visible')">
                        <span class="flex size-12 items-center justify-center rounded-full bg-moss-light/60 text-moss-dark">
                            <flux:icon :icon="$note['icon']" variant="outline" class="size-5" />
                        </span>
                        <p class="mt-5 font-display text-2xl text-moss-dark">{{ $note['title'] }}</p>
                        <p class="mt-3 leading-relaxed text-ink-soft">{{ $note['body'] }}</p>
                    </article>
                @endforeach
            </div>

            <p class="mt-16 text-center font-sans text-sm uppercase tracking-[0.35em] shimmer-text animate-shimmer">{{ config('wedding.hashtag') }}</p>
        </section>

        {{-- RSVP --}}
        <section id="rsvp" class="relative scroll-mt-16 overflow-hidden bg-linen/60 px-6 py-24 sm:py-32">
            <x-wedding.side-flowers side="left" class="pointer-events-none absolute top-1/2 left-4 hidden w-24 -translate-y-1/2 animate-sway lg:block xl:left-12 xl:w-32" />
            <x-wedding.side-flowers side="right" class="pointer-events-none absolute top-1/2 right-4 hidden w-24 -translate-y-1/2 animate-sway [animation-delay:-4.5s] lg:block xl:right-12 xl:w-32" />

            <x-wedding.section-heading eyebrow="Répondez s'il vous plaît" title="Will you celebrate with us?">
                Kindly let us know by {{ $wedding->rsvpDeadline()->format('F j, Y') }} so we can save
                a seat, a plate, and a dance for you.
            </x-wedding.section-heading>

            <div class="relative mx-auto mt-14 max-w-xl">
                @if ($invitation)
                    <livewire:rsvp :invitation="$invitation" />
                @else
                    <div class="relative border border-gold/60 bg-ivory px-6 py-10 text-center shadow-[0_30px_70px_-35px_rgb(51_64_79/0.45)] sm:px-12 sm:py-14" data-test="personal-link-note">
                        <div class="pointer-events-none absolute inset-2 border border-gold/30"></div>

                        <div class="relative">
                            <p class="font-display text-4xl text-moss-dark">RSVP is by personal invitation</p>
                            <p class="mt-4 text-lg leading-relaxed text-ink-soft">Please use the link we sent you.</p>
                            <a href="#wishes" class="mt-6 inline-flex items-center gap-2 font-sans text-xs uppercase tracking-[0.3em] text-moss-dark transition hover:text-moss">
                                Leave us a wish instead <flux:icon.arrow-down variant="micro" />
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- Wishes --}}
        <section id="wishes" class="scroll-mt-16 px-6 py-24 sm:py-32">
            <x-wedding.section-heading eyebrow="From the people we love" title="Wishes & Blessings" />

            <div class="mx-auto mt-14 max-w-5xl">
                <livewire:wishes :accepts-wishes="$invitation === null" />
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="scroll-mt-16 bg-paper/70 px-6 py-24 sm:py-32">
            <x-wedding.section-heading eyebrow="Good to know" title="Questions" />

            <div class="mx-auto mt-14 max-w-2xl divide-y divide-gold/50 border-y border-gold/50">
                @foreach (config('wedding.faqs') as $faq)
                    <details class="group py-6" name="faq">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-6 font-display text-lg text-moss-dark">
                            {{ $faq['question'] }}
                            <flux:icon.plus class="size-4 shrink-0 text-blush-dark transition group-open:rotate-45" />
                        </summary>
                        <p class="mt-4 leading-relaxed text-ink-soft">{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="relative px-6 pt-24 pb-16 text-center">
        <div class="flex items-center justify-center gap-6">
            <x-wedding.sprig class="h-20 w-7 -rotate-[30deg] text-sage" filled />
            <p class="font-display text-4xl text-moss-dark sm:text-5xl">{{ $wedding->coupleNames() }}</p>
            <x-wedding.sprig class="h-20 w-7 rotate-[30deg] text-sage" filled />
        </div>
        <p class="mt-4 font-sans text-xs uppercase tracking-[0.4em] text-blush-dark">{{ $ceremonyStartsAt->format('m . d . Y') }} · Panabo City</p>
        <p class="mt-12 text-sm italic text-ink-soft">Handcrafted with love (and a little code) by the groom.</p>
    </footer>
</x-layouts::invitation>

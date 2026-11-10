<?php

use App\Enums\RsvpStatus;
use App\Models\Invitation;
use App\Support\Wedding;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public Invitation $invitation;

    public string $step = 'choice';

    public ?bool $attending = null;

    public int $attendingCount = 1;

    /** @var list<string> */
    public array $guestNames = [''];

    public string $dietaryNotes = '';

    public string $songRequest = '';

    public string $message = '';

    public bool $shareMessage = true;

    /**
     * Mount the component.
     */
    public function mount(Invitation $invitation): void
    {
        $this->invitation = $invitation;

        if ($this->invitation->hasResponded()) {
            $this->fillFromInvitation();
            $this->step = 'done';

            return;
        }

        if (! app(Wedding::class)->isRsvpOpen()) {
            $this->step = 'closed';

            return;
        }

        $this->attendingCount = $this->invitation->seats;
        $this->guestNames = $this->invitation->seats === 1
            ? [$this->invitation->name]
            : array_fill(0, $this->invitation->seats, '');
    }

    /**
     * The most seats this guest may reserve.
     */
    #[Computed]
    public function maxSeats(): int
    {
        return $this->invitation->seats;
    }

    #[Computed]
    public function guestName(): string
    {
        return $this->invitation->name;
    }

    #[Computed]
    public function isRsvpOpen(): bool
    {
        return app(Wedding::class)->isRsvpOpen();
    }

    /**
     * Record whether the guest will attend.
     */
    public function choose(bool $attending): void
    {
        $this->attending = $attending;
        $this->step = $attending ? 'party' : 'note';
    }

    /**
     * Add or remove a seat from the party.
     */
    public function changeAttendingCount(int $difference): void
    {
        $this->attendingCount = max(1, min($this->maxSeats, $this->attendingCount + $difference));

        $this->guestNames = array_pad(array_slice($this->guestNames, 0, $this->attendingCount), $this->attendingCount, '');
    }

    /**
     * Confirm the attending party.
     */
    public function confirmParty(): void
    {
        $this->validate($this->partyRules());

        $this->step = 'note';
    }

    /**
     * Go back to the previous step.
     */
    public function back(): void
    {
        $this->step = match ($this->step) {
            'party' => 'choice',
            'note' => $this->attending ? 'party' : 'choice',
            default => $this->step,
        };
    }

    /**
     * Allow the guest to change a reply they already sent.
     */
    public function changeReply(): void
    {
        if (! $this->isRsvpOpen) {
            return;
        }

        $this->step = 'choice';
    }

    /**
     * Save the guest's reply.
     */
    public function submit(): void
    {
        if (! $this->isRsvpOpen) {
            $this->step = 'closed';

            return;
        }

        $this->validate([
            'attending' => ['required', 'boolean'],
            ...($this->attending ? $this->partyRules() : []),
            'dietaryNotes' => ['nullable', 'string', 'max:255'],
            'songRequest' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
            'shareMessage' => ['boolean'],
        ]);

        $this->invitation->update([
            'status' => $this->attending ? RsvpStatus::Attending : RsvpStatus::Declined,
            'attending_count' => $this->attending ? $this->attendingCount : 0,
            'guest_names' => $this->attending ? array_values(array_map('trim', $this->guestNames)) : null,
            'dietary_notes' => $this->attending ? (trim($this->dietaryNotes) ?: null) : null,
            'song_request' => $this->attending ? (trim($this->songRequest) ?: null) : null,
            'responded_at' => now(),
        ]);

        $this->saveWish();

        $this->step = 'done';

        $this->dispatch('rsvp-submitted');

        if ($this->attending) {
            $this->dispatch('rsvp-celebrate');
        }
    }

    /**
     * Keep the guest's wish in step with the note they left, removing it when they cleared the note.
     */
    protected function saveWish(): void
    {
        $message = trim($this->message);

        if ($message === '') {
            $this->invitation->wish()->delete();

            return;
        }

        $this->invitation->wish()->updateOrCreate([], [
            'name' => $this->invitation->name,
            'message' => $message,
            'is_shared' => $this->shareMessage,
        ]);
    }

    protected function fillFromInvitation(): void
    {
        $this->attending = $this->invitation->status === RsvpStatus::Attending;
        $this->attendingCount = max(1, $this->invitation->attending_count);
        $this->guestNames = $this->invitation->guest_names ?: [$this->invitation->name];
        $this->dietaryNotes = (string) $this->invitation->dietary_notes;
        $this->songRequest = (string) $this->invitation->song_request;
        $this->message = (string) $this->invitation->wish?->message;
        $this->shareMessage = $this->invitation->wish->is_shared ?? true;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function partyRules(): array
    {
        return [
            'attendingCount' => ['required', 'integer', 'min:1', 'max:'.$this->maxSeats],
            'guestNames' => ['required', 'array', 'size:'.$this->attendingCount],
            'guestNames.*' => ['required', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'attendingCount' => 'number of guests',
            'guestNames.*' => 'guest name',
        ];
    }
};
?>

@php
    $inputClasses = 'w-full border-0 border-b border-gold bg-transparent px-0 py-3 text-xl text-ink placeholder:text-ink-soft/50 focus:border-moss-dark focus:outline-none focus:ring-0';
    $labelClasses = 'font-sans text-[0.65rem] uppercase tracking-[0.3em] text-blush-dark';
    $primaryButton = 'inline-flex items-center justify-center gap-2 bg-moss-dark px-8 py-3 font-sans text-xs uppercase tracking-[0.3em] text-ivory transition hover:bg-ink disabled:opacity-60';
    $ghostButton = 'inline-flex items-center gap-2 font-sans text-xs uppercase tracking-[0.3em] text-ink-soft transition hover:text-moss';
    $wedding = app(\App\Support\Wedding::class);
@endphp

<div class="relative border border-gold/60 bg-ivory px-6 py-10 shadow-[0_30px_70px_-35px_rgb(51_64_79/0.45)] sm:px-12 sm:py-14">
    <div class="pointer-events-none absolute inset-2 border border-gold/30"></div>

    @if ($step === 'choice')
        <div wire:key="step-choice" class="relative text-center">
            <p class="text-2xl italic text-ink">{{ $this->guestName }},</p>
            <p class="mt-2 text-xl text-ink-soft">will you be there to share the best day of our lives?</p>

            <div class="mt-10 grid gap-4 sm:grid-cols-2">
                <button type="button" wire:click="choose(true)" class="group flex flex-col items-center rounded-md border border-moss/40 bg-paper/50 px-6 py-8 transition hover:-translate-y-1 hover:border-moss hover:bg-ivory hover:shadow-lg" data-test="accept-button">
                    <flux:icon.heart variant="solid" class="size-7 text-blush transition group-hover:scale-110 group-hover:text-rose-400" />
                    <span class="mt-4 font-display text-2xl whitespace-nowrap text-ink">Joyfully accepts</span>
                    <span class="mt-2 font-sans text-[0.6rem] uppercase tracking-[0.3em] text-ink-soft">I wouldn't miss it</span>
                </button>

                <button type="button" wire:click="choose(false)" class="group flex flex-col items-center rounded-md border border-moss/20 px-6 py-8 transition hover:-translate-y-1 hover:border-moss/50 hover:shadow-lg" data-test="decline-button">
                    <flux:icon.envelope-open variant="outline" class="size-7 text-ink-soft/70" />
                    <span class="mt-4 font-display text-2xl whitespace-nowrap text-ink-soft">Regretfully declines</span>
                    <span class="mt-2 font-sans text-[0.6rem] uppercase tracking-[0.3em] text-ink-soft">Celebrating in spirit</span>
                </button>
            </div>
        </div>
    @elseif ($step === 'party')
        <form wire:key="step-party" wire:submit="confirmParty" class="relative space-y-8">
            <div class="text-center">
                <p class="text-2xl italic text-ink">Wonderful! Who is coming with you?</p>
                <p class="mt-2 text-ink-soft">
                    @if ($this->maxSeats > 1)
                        Up to {{ $this->maxSeats }} {{ Str::plural('seat', $this->maxSeats) }} are reserved for you.
                    @else
                        One seat is reserved especially for you.
                    @endif
                </p>
            </div>

            @if ($this->maxSeats > 1)
                <div class="flex items-center justify-center gap-6">
                    <button type="button" wire:click="changeAttendingCount(-1)" @disabled($attendingCount <= 1) class="flex size-11 items-center justify-center rounded-full border border-moss/50 text-moss transition hover:bg-moss hover:text-ivory disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-moss" aria-label="Remove a guest">
                        <flux:icon.minus variant="micro" />
                    </button>
                    <div class="text-center">
                        <span class="block text-5xl font-light lining-nums tabular-nums text-ink" data-test="attending-count">{{ $attendingCount }}</span>
                        <span class="font-sans text-[0.6rem] uppercase tracking-[0.3em] text-moss">{{ Str::plural('guest', $attendingCount) }}</span>
                    </div>
                    <button type="button" wire:click="changeAttendingCount(1)" @disabled($attendingCount >= $this->maxSeats) class="flex size-11 items-center justify-center rounded-full border border-moss/50 text-moss transition hover:bg-moss hover:text-ivory disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-moss" aria-label="Add a guest">
                        <flux:icon.plus variant="micro" />
                    </button>
                </div>
            @endif

            <div class="space-y-5">
                @foreach ($guestNames as $index => $guestName)
                    <label class="block" wire:key="guest-name-{{ $index }}">
                        <span class="{{ $labelClasses }}">Guest {{ $index + 1 }}</span>
                        <input type="text" wire:model="guestNames.{{ $index }}" class="{{ $inputClasses }}" placeholder="Full name" required />
                        @error('guestNames.'.$index) <span class="mt-2 block text-sm text-rose-700">{{ $message }}</span> @enderror
                    </label>
                @endforeach
            </div>

            <label class="block">
                <span class="{{ $labelClasses }}">Food allergies or dietary needs <span class="normal-case tracking-normal text-ink-soft">(optional)</span></span>
                <input type="text" wire:model="dietaryNotes" class="{{ $inputClasses }}" placeholder="e.g. no seafood, vegetarian" />
                @error('dietaryNotes') <span class="mt-2 block text-sm text-rose-700">{{ $message }}</span> @enderror
            </label>

            <div class="flex items-center justify-between pt-2">
                <button type="button" wire:click="back" class="{{ $ghostButton }}"><flux:icon.arrow-left variant="micro" /> Back</button>
                <button type="submit" class="{{ $primaryButton }}">Continue</button>
            </div>
        </form>
    @elseif ($step === 'note')
        <form wire:key="step-note" wire:submit="submit" class="relative space-y-8">
            <p class="text-center text-2xl italic text-ink">
                {{ $attending ? 'One last thing, a little love note for us?' : 'We will truly miss you. Would you like to leave us a message?' }}
            </p>

            <label class="block">
                <span class="{{ $labelClasses }}">Your wish for the couple <span class="normal-case tracking-normal text-ink-soft">(optional)</span></span>
                <textarea wire:model="message" rows="4" class="{{ $inputClasses }} resize-none" placeholder="Words of love, advice, or a prayer for our marriage..."></textarea>
                @error('message') <span class="mt-2 block text-sm text-rose-700">{{ $message }}</span> @enderror
            </label>

            @if ($attending)
                <label class="block">
                    <span class="{{ $labelClasses }}">What song will get you on the dance floor? <span class="normal-case tracking-normal text-ink-soft">(optional)</span></span>
                    <input type="text" wire:model="songRequest" class="{{ $inputClasses }}" placeholder="Song title and artist" />
                    @error('songRequest') <span class="mt-2 block text-sm text-rose-700">{{ $message }}</span> @enderror
                </label>
            @endif

            <label class="flex cursor-pointer items-center gap-3 text-ink-soft">
                <input type="checkbox" wire:model="shareMessage" class="size-4 rounded-sm border-moss/50 text-moss accent-[#3d4e63] focus:ring-moss" />
                <span>Share my wish on the wall of wishes</span>
            </label>

            <div class="flex items-center justify-between pt-2">
                <button type="button" wire:click="back" class="{{ $ghostButton }}"><flux:icon.arrow-left variant="micro" /> Back</button>
                <button type="submit" class="{{ $primaryButton }}" wire:loading.attr="disabled" wire:target="submit" data-test="send-reply-button">
                    <span wire:loading.remove wire:target="submit">Send my reply</span>
                    <span wire:loading wire:target="submit">Sealing it with love...</span>
                </button>
            </div>
        </form>
    @elseif ($step === 'done')
        <div wire:key="step-done" class="relative text-center">
            @if ($attending)
                <div data-test="admission-pass">
                    <p class="font-display text-4xl text-moss-dark">See you there!</p>
                    <p class="mt-3 text-lg text-ink-soft">Your seat is saved, {{ $this->guestName }}. Here is your pass to the best day of our lives.</p>

                    <div class="relative mx-auto mt-10 max-w-sm overflow-hidden rounded-md bg-ink text-left text-ivory shadow-2xl">
                        <div class="px-7 pt-7 pb-6">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-sans text-[0.55rem] uppercase tracking-[0.35em] text-moss-light">Admission Pass</p>
                                    <p class="mt-1 font-display text-2xl">{{ $wedding->coupleNames() }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-sans text-[0.55rem] uppercase tracking-[0.35em] text-moss-light">Admit</p>
                                    <p class="text-4xl font-light lining-nums">{{ $attendingCount }}</p>
                                </div>
                            </div>

                            <ul class="mt-5 space-y-1 text-lg">
                                @foreach ($guestNames as $index => $guestName)
                                    <li wire:key="pass-guest-{{ $index }}" class="flex items-center gap-2">
                                        <span class="size-1 rounded-full bg-moss-light"></span> {{ $guestName }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="relative flex items-center">
                            <span class="absolute -left-3 size-6 rounded-full bg-ivory"></span>
                            <span class="mx-5 w-full border-t border-dashed border-ivory/25"></span>
                            <span class="absolute -right-3 size-6 rounded-full bg-ivory"></span>
                        </div>

                        <dl class="grid grid-cols-2 gap-4 px-7 pt-5 pb-7 text-sm">
                            <div>
                                <dt class="font-sans text-[0.55rem] uppercase tracking-[0.3em] text-moss-light">Date</dt>
                                <dd class="mt-1">{{ $wedding->ceremonyStartsAt()->format('M j, Y') }}</dd>
                            </div>
                            <div>
                                <dt class="font-sans text-[0.55rem] uppercase tracking-[0.3em] text-moss-light">Ceremony</dt>
                                <dd class="mt-1">{{ $wedding->ceremonyStartsAt()->format('g:i A') }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="font-sans text-[0.55rem] uppercase tracking-[0.3em] text-moss-light">Venues</dt>
                                <dd class="mt-1">{{ config('wedding.ceremony.name') }} &rarr; {{ config('wedding.reception.name') }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="font-sans text-[0.55rem] uppercase tracking-[0.3em] text-moss-light">Invitation no.</dt>
                                <dd class="mt-1 font-mono tracking-[0.3em]">{{ strtoupper($invitation->code) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @else
                <div data-test="declined-note">
                    <p class="font-display text-4xl text-moss-dark">You will be missed</p>
                    <p class="mt-4 text-lg leading-relaxed text-ink-soft">
                        Thank you for letting us know, {{ $this->guestName }}. We will be holding you in our hearts
                        as we celebrate, and we hope to share a meal with you very soon.
                    </p>
                </div>
            @endif

            <div class="mt-10 flex flex-col items-center gap-5">
                @if ($attending)
                    <a href="{{ route('calendar') }}" class="{{ $primaryButton }}">
                        <flux:icon.calendar-days variant="micro" /> Add to my calendar
                    </a>
                @endif

                @if ($this->isRsvpOpen)
                    <button type="button" wire:click="changeReply" class="{{ $ghostButton }}">Change my reply</button>
                @endif
            </div>
        </div>
    @else
        <div wire:key="step-closed" class="relative text-center">
            <p class="font-display text-4xl text-moss-dark">Our guest list is complete</p>
            <p class="mt-4 text-lg leading-relaxed text-ink-soft">
                Replies closed on {{ $wedding->rsvpDeadline()->format('F j, Y') }}. If you have any questions,
                please message {{ config('wedding.groom.first_name') }} or {{ config('wedding.bride.first_name') }} directly.
            </p>
        </div>
    @endif
</div>

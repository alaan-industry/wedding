<?php

use App\Models\Wish;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    /**
     * The session key holding the wishes this visitor has hearted.
     */
    protected const string HEARTED_SESSION_KEY = 'hearted_wishes';

    /**
     * How many wishes one browser session may send in an hour.
     */
    protected const int WISHES_PER_HOUR_PER_SESSION = 3;

    /**
     * How many wishes one IP address may send in an hour, looser because many visitors share a mobile network or Wi-Fi.
     */
    protected const int WISHES_PER_HOUR_PER_IP = 30;

    /**
     * Whether visitors may write a wish directly on the wall, for those without a personal invitation.
     */
    #[Locked]
    public bool $acceptsWishes = false;

    public string $name = '';

    public string $message = '';

    public bool $wishSent = false;

    public function mount(bool $acceptsWishes = false): void
    {
        $this->acceptsWishes = $acceptsWishes;
    }

    /**
     * The latest wishes shown on the wall.
     *
     * @return Collection<int, Wish>
     */
    #[Computed]
    public function wishes(): Collection
    {
        return Wish::query()
            ->shared()
            ->latest()
            ->limit(30)
            ->get(['id', 'name', 'message', 'hearts_count']);
    }

    /**
     * Post a wish from a visitor without a personal invitation, kept off the wall until the couple approves it.
     */
    public function sendWish(): void
    {
        if (! $this->acceptsWishes) {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $limits = [
            'wishes:session:'.session()->getId() => self::WISHES_PER_HOUR_PER_SESSION,
            'wishes:ip:'.request()->ip() => self::WISHES_PER_HOUR_PER_IP,
        ];

        foreach ($limits as $key => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $this->addError('message', 'We have received several wishes from this device. Please try again in a little while.');

                return;
            }
        }

        foreach (array_keys($limits) as $key) {
            RateLimiter::hit($key, 3600);
        }

        Wish::create([
            'name' => trim($this->name),
            'message' => trim($this->message),
            'is_hidden' => true,
        ]);

        $this->reset('name', 'message');
        $this->wishSent = true;
    }

    /**
     * The ids of the wishes this visitor has hearted.
     *
     * @return list<int>
     */
    #[Computed]
    public function heartedWishIds(): array
    {
        return session()->get(self::HEARTED_SESSION_KEY, []);
    }

    /**
     * Heart a wish, or take the heart back if this visitor already gave one.
     */
    public function toggleHeart(int $wishId): void
    {
        $key = 'wish-hearts:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            return;
        }

        RateLimiter::hit($key, 60);

        $wish = Wish::query()->shared()->findOrFail($wishId);
        $heartedWishIds = $this->heartedWishIds;

        if (in_array($wish->id, $heartedWishIds, true)) {
            Wish::query()->whereKey($wish->id)->where('hearts_count', '>', 0)->decrement('hearts_count');

            $heartedWishIds = array_values(array_diff($heartedWishIds, [$wish->id]));
        } else {
            $wish->increment('hearts_count');
            $heartedWishIds[] = $wish->id;
        }

        session()->put(self::HEARTED_SESSION_KEY, $heartedWishIds);

        unset($this->wishes, $this->heartedWishIds);
    }

    /**
     * Refresh the wall when a guest sends a reply.
     */
    #[On('rsvp-submitted')]
    public function refreshWishes(): void
    {
        unset($this->wishes);
    }
};
?>

@php
    $inputClasses = 'w-full border-0 border-b border-gold bg-transparent px-0 py-3 text-xl text-ink placeholder:text-ink-soft/50 focus:border-moss-dark focus:outline-none focus:ring-0';
    $labelClasses = 'font-sans text-[0.65rem] uppercase tracking-[0.3em] text-blush-dark';
@endphp

<div>
    @if ($acceptsWishes)
        <div class="relative mx-auto mb-14 max-w-xl border border-gold/60 bg-ivory px-6 py-10 shadow-[0_30px_70px_-35px_rgb(51_64_79/0.45)] sm:px-12">
            <div class="pointer-events-none absolute inset-2 border border-gold/30"></div>

            @if ($wishSent)
                <div wire:key="wish-sent" class="relative text-center" data-test="wish-sent">
                    <p class="font-display text-3xl text-moss-dark">Thank you for your wish!</p>
                    <p class="mt-3 text-lg text-ink-soft">It will appear on our wall once {{ app(\App\Support\Wedding::class)->coupleNames() }} have read it. We will treasure it.</p>
                    <button type="button" wire:click="$set('wishSent', false)" class="mt-6 font-sans text-xs uppercase tracking-[0.3em] text-ink-soft transition hover:text-moss">Write another wish</button>
                </div>
            @else
                <form wire:key="wish-form" wire:submit="sendWish" class="relative space-y-6">
                    <p class="text-center text-2xl italic text-ink">Leave us a wish</p>

                    <label class="block">
                        <span class="{{ $labelClasses }}">Your name</span>
                        <input type="text" wire:model="name" class="{{ $inputClasses }}" placeholder="Juan Dela Cruz" autocomplete="name" required />
                        @error('name') <span class="mt-2 block text-sm text-rose-700">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="{{ $labelClasses }}">Your wish for the couple</span>
                        <textarea wire:model="message" rows="3" class="{{ $inputClasses }} resize-none" placeholder="Words of love, advice, or a prayer for our marriage..." required></textarea>
                        @error('message') <span class="mt-2 block text-sm text-rose-700">{{ $message }}</span> @enderror
                    </label>

                    <div class="flex justify-center pt-2">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 bg-moss-dark px-8 py-3 font-sans text-xs uppercase tracking-[0.3em] text-ivory transition hover:bg-ink disabled:opacity-60" wire:loading.attr="disabled" wire:target="sendWish" data-test="send-wish-button">
                            Send my wish
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif

    @if ($this->wishes->isEmpty())
        <p class="text-center text-lg italic text-ink-soft">{{ $acceptsWishes ? 'Be the first to leave us a wish.' : 'Be the first to leave us a wish when you RSVP.' }}</p>
    @else
        <div class="columns-1 gap-6 sm:columns-2 lg:columns-3">
            @foreach ($this->wishes as $wish)
                @php($isHearted = in_array($wish->id, $this->heartedWishIds, true))

                <figure wire:key="wish-{{ $wish->id }}" class="mb-6 break-inside-avoid border border-gold/50 bg-ivory px-6 py-7 shadow-[0_15px_40px_-30px_rgb(51_64_79/0.5)]">
                    <span class="font-display text-5xl leading-none text-blush">&ldquo;</span>
                    <blockquote class="-mt-4 text-lg leading-relaxed text-ink">{{ $wish->message }}</blockquote>
                    <div class="mt-4 flex items-center justify-between gap-4">
                        <figcaption class="font-sans text-[0.65rem] uppercase tracking-[0.3em] text-blush-dark">{{ $wish->name }}</figcaption>

                        <button
                            type="button"
                            wire:click="toggleHeart({{ $wish->id }})"
                            x-data
                            @click="$el.classList.remove('heart-pop'); void $el.offsetWidth; $el.classList.add('heart-pop')"
                            class="group flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full px-2 py-1 font-sans text-xs tabular-nums transition {{ $isHearted ? 'text-blush-dark' : 'text-ink-soft hover:text-blush-dark' }}"
                            aria-pressed="{{ $isHearted ? 'true' : 'false' }}"
                            aria-label="{{ $isHearted ? 'Remove your heart from' : 'Send a heart to' }} {{ $wish->name }}'s wish"
                            data-test="wish-heart"
                        >
                            <svg viewBox="-1 -1 22 20" class="w-4" aria-hidden="true">
                                <path d="M10 17C4 12 0 9 0 5A5 5 0 0 1 10 3a5 5 0 0 1 10 2c0 4-4 7-10 12Z" fill="{{ $isHearted ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                            @if ($wish->hearts_count > 0)
                                <span>{{ $wish->hearts_count }}</span>
                            @endif
                        </button>
                    </div>
                </figure>
            @endforeach
        </div>
    @endif
</div>

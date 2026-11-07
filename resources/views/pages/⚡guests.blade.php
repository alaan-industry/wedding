<?php

use App\Enums\RsvpStatus;
use App\Models\Invitation;
use App\Models\Wish;
use App\Support\Wedding;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Guest list')] class extends Component {
    use WithPagination;

    public string $name = '';

    public int $seats = 2;

    public string $phone = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    /**
     * Add a household to the guest list.
     */
    public function addInvitation(): void
    {
        Gate::authorize('manage-invitations');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'seats' => ['required', 'integer', 'min:1', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        Invitation::create([
            ...$validated,
            'phone' => $validated['phone'] ?: null,
        ]);

        $this->reset('name', 'phone');
        $this->seats = 2;
        unset($this->stats);

        Flux::toast(variant: 'success', text: __('Invitation created.'));
    }

    /**
     * Remove a household from the guest list.
     */
    public function deleteInvitation(int $invitationId): void
    {
        Gate::authorize('manage-invitations');

        Invitation::query()->findOrFail($invitationId)->delete();

        unset($this->stats);

        Flux::toast(text: __('Invitation removed.'));
    }

    /**
     * Hide a wish from the public wall of wishes, or show it again.
     *
     * Only the couple's own flag changes, so a guest editing their reply cannot bring back a wish the couple hid.
     */
    public function toggleWish(int $wishId): void
    {
        Gate::authorize('manage-invitations');

        $wish = Wish::query()->findOrFail($wishId);
        $wish->update(['is_hidden' => ! $wish->is_hidden]);

        unset($this->visitorWishesAwaitingApproval);

        Flux::toast(text: $wish->is_hidden ? __('Wish hidden from the wall.') : __('Wish shown on the wall.'));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->validate(['status' => ['nullable', Rule::enum(RsvpStatus::class)]]);

        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Invitation>
     */
    #[Computed]
    public function invitations(): LengthAwarePaginator
    {
        $status = RsvpStatus::tryFrom($this->status);

        return Invitation::query()
            ->with('wish')
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%')
                    ->orWhere('code', $this->search);
            }))
            ->when($status, fn ($query) => $query->withStatus($status))
            ->latest()
            ->paginate(25);
    }

    /**
     * Wishes left by visitors without an invitation, those not on the wall yet first.
     *
     * @return LengthAwarePaginator<int, Wish>
     */
    #[Computed]
    public function visitorWishes(): LengthAwarePaginator
    {
        return Wish::query()
            ->fromVisitors()
            ->orderByDesc('is_hidden')
            ->latest()
            ->paginate(10, pageName: 'wishesPage');
    }

    /**
     * How many visitor wishes are not on the wall yet.
     */
    #[Computed]
    public function visitorWishesAwaitingApproval(): int
    {
        return Wish::query()->fromVisitors()->where('is_hidden', true)->count();
    }

    /**
     * @return array{households: int, seats: int, attending: int, declined: int, pending: int, opened: int}
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'households' => Invitation::query()->count(),
            'seats' => (int) Invitation::query()->sum('seats'),
            'attending' => (int) Invitation::query()->withStatus(RsvpStatus::Attending)->sum('attending_count'),
            'declined' => Invitation::query()->withStatus(RsvpStatus::Declined)->count(),
            'pending' => Invitation::query()->withStatus(RsvpStatus::Pending)->count(),
            'opened' => Invitation::query()->whereNotNull('opened_at')->count(),
        ];
    }

    /**
     * Build the ready-to-send invitation message for a household.
     */
    public function inviteMessage(Invitation $invitation): string
    {
        $wedding = app(Wedding::class);

        return "Hi {$invitation->name}!\n\n"
            ."{$wedding->coupleNames()} are getting married on {$wedding->ceremonyStartsAt()->format('l, F j, Y')}, "
            .'and it would mean the world to us to celebrate the best day of our lives with you. '
            ."Please open your personal invitation and let us know if you can come by {$wedding->rsvpDeadline()->format('F j')}:\n\n"
            .$invitation->url();
    }
}; ?>

<section class="flex w-full flex-col gap-8">
    <div>
        <flux:heading size="xl" level="1">{{ __('Guest list') }}</flux:heading>
        <flux:subheading>{{ __('Invite households, share their personal links, and follow their replies.') }}</flux:subheading>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        @foreach ([
            ['label' => __('Households'), 'value' => $this->stats['households']],
            ['label' => __('Seats reserved'), 'value' => $this->stats['seats']],
            ['label' => __('Confirmed guests'), 'value' => $this->stats['attending']],
            ['label' => __('Declined'), 'value' => $this->stats['declined']],
            ['label' => __('Awaiting reply'), 'value' => $this->stats['pending']],
            ['label' => __('Opened invite'), 'value' => $this->stats['opened']],
        ] as $stat)
            <flux:card wire:key="stat-{{ $loop->index }}" class="space-y-1">
                <flux:text>{{ $stat['label'] }}</flux:text>
                <flux:heading size="xl">{{ $stat['value'] }}</flux:heading>
            </flux:card>
        @endforeach
    </div>

    <flux:card>
        <form wire:submit="addInvitation" class="grid items-end gap-4 md:grid-cols-[1fr_8rem_12rem_auto]">
            <flux:input wire:model="name" :label="__('Household or guest name')" :placeholder="__('e.g. The Dela Cruz Family')" required />
            <flux:input wire:model="seats" type="number" min="1" max="20" :label="__('Seats')" required />
            <flux:input wire:model="phone" :label="__('Mobile (optional)')" />
            <flux:button type="submit" variant="primary" icon="plus" data-test="add-invitation-button">{{ __('Invite') }}</flux:button>
        </form>
    </flux:card>

    <div class="flex flex-col gap-4 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search name, phone or code')" class="sm:max-w-xs" />
        <flux:select wire:model.live="status" class="sm:max-w-48">
            <flux:select.option value="">{{ __('All replies') }}</flux:select.option>
            @foreach (RsvpStatus::cases() as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$this->invitations">
        <flux:table.columns>
            <flux:table.column>{{ __('Guest') }}</flux:table.column>
            <flux:table.column>{{ __('Reply') }}</flux:table.column>
            <flux:table.column>{{ __('Party') }}</flux:table.column>
            <flux:table.column>{{ __('Notes') }}</flux:table.column>
            <flux:table.column>{{ __('Opened') }}</flux:table.column>
            <flux:table.column align="end" />
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->invitations as $invitation)
                <flux:table.row :key="$invitation->id">
                    <flux:table.cell>
                        <div class="font-medium text-zinc-800 dark:text-white">{{ $invitation->name }}</div>
                        <div class="text-xs text-zinc-500">
                            {{ strtoupper($invitation->code) }}
                            @if ($invitation->phone) · {{ $invitation->phone }} @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" :color="$invitation->status->color()" inset="top bottom">{{ $invitation->status->label() }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-normal">
                        <div>{{ $invitation->attending_count }} / {{ $invitation->seats }}</div>
                        @if ($invitation->guest_names)
                            <div class="max-w-56 text-xs text-zinc-500">{{ implode(', ', $invitation->guest_names) }}</div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-normal">
                        <div class="max-w-72 space-y-1 text-xs text-zinc-500">
                            @if ($invitation->dietary_notes)
                                <div><span class="font-medium">{{ __('Diet') }}:</span> {{ $invitation->dietary_notes }}</div>
                            @endif
                            @if ($invitation->song_request)
                                <div><span class="font-medium">{{ __('Song') }}:</span> {{ $invitation->song_request }}</div>
                            @endif
                            @if ($invitation->wish)
                                <div class="italic">&ldquo;{{ Str::limit($invitation->wish->message, 120) }}&rdquo;</div>
                                @if ($invitation->wish->is_shared)
                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        :icon="$invitation->wish->is_hidden ? 'eye' : 'eye-slash'"
                                        wire:click="toggleWish({{ $invitation->wish->id }})"
                                        data-test="toggle-wish-button"
                                    >
                                        {{ $invitation->wish->is_hidden ? __('Show on wall') : __('Hide from wall') }}
                                    </flux:button>
                                @else
                                    <flux:badge size="sm" color="zinc">{{ __('Kept private by guest') }}</flux:badge>
                                @endif
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell class="text-xs text-zinc-500">
                        {{ $invitation->opened_at?->diffForHumans() ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1" x-data="{ copied: false }">
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="clipboard-document"
                                x-on:click="navigator.clipboard.writeText({{ Js::from($this->inviteMessage($invitation)) }}); copied = true; setTimeout(() => copied = false, 2000)"
                                :tooltip="__('Copy invitation message')"
                            >
                                <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy invite') }}'"></span>
                            </flux:button>
                            <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="$invitation->url()" target="_blank" :tooltip="__('Preview invitation')" />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                wire:click="deleteInvitation({{ $invitation->id }})"
                                wire:confirm="{{ __('Remove :name from the guest list?', ['name' => $invitation->name]) }}"
                                :tooltip="__('Remove')"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-10 text-center text-zinc-500">
                        {{ __('No invitations yet. Add your first household above.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div>
        <flux:heading size="lg" level="2">{{ __('Wishes from visitors') }}</flux:heading>
        <flux:subheading>
            {{ __('Left by people without a personal invitation, such as neighbors and friends. They only appear on the wall once you approve them.') }}
            @if ($this->visitorWishesAwaitingApproval > 0)
                <flux:badge size="sm" color="amber" class="ms-1">{{ __(':count not on the wall yet', ['count' => $this->visitorWishesAwaitingApproval]) }}</flux:badge>
            @endif
        </flux:subheading>
    </div>

    <flux:table :paginate="$this->visitorWishes">
        <flux:table.columns>
            <flux:table.column>{{ __('From') }}</flux:table.column>
            <flux:table.column>{{ __('Wish') }}</flux:table.column>
            <flux:table.column>{{ __('Sent') }}</flux:table.column>
            <flux:table.column align="end" />
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->visitorWishes as $wish)
                <flux:table.row :key="'wish-'.$wish->id">
                    <flux:table.cell class="font-medium text-zinc-800 dark:text-white">{{ $wish->name }}</flux:table.cell>
                    <flux:table.cell class="whitespace-normal">
                        <div class="max-w-md text-sm italic text-zinc-500">&ldquo;{{ Str::limit($wish->message, 200) }}&rdquo;</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-xs text-zinc-500">{{ $wish->created_at?->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button
                            size="xs"
                            :variant="$wish->is_hidden ? 'primary' : 'ghost'"
                            :icon="$wish->is_hidden ? 'check' : 'eye-slash'"
                            wire:click="toggleWish({{ $wish->id }})"
                            data-test="toggle-visitor-wish-button"
                        >
                            {{ $wish->is_hidden ? __('Approve') : __('Hide from wall') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="py-10 text-center text-zinc-500">
                        {{ __('No wishes from visitors yet.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>

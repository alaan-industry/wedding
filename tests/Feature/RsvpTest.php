<?php

use App\Enums\RsvpStatus;
use App\Models\Invitation;
use App\Models\Wish;
use Livewire\Livewire;

beforeEach(function () {
    config(['wedding.rsvp_deadline' => now()->addMonth()->format('Y-m-d H:i')]);
});

test('guestAcceptsTheirPersonalInvitationWithTheirParty', function () {
    $this->freezeSecond();
    $invitation = Invitation::factory()->create(['seats' => 3]);

    Livewire::test('rsvp', ['invitation' => $invitation])
        ->assertSet('step', 'choice')
        ->call('choose', true)
        ->assertSet('step', 'party')
        ->call('changeAttendingCount', -1)
        ->set('guestNames', ['Juan Dela Cruz', 'Maria Dela Cruz'])
        ->set('dietaryNotes', 'No seafood')
        ->call('confirmParty')
        ->assertHasNoErrors()
        ->assertSet('step', 'note')
        ->set('message', 'Congratulations to you both!')
        ->set('songRequest', 'Perfect - Ed Sheeran')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('step', 'done')
        ->assertDispatched('rsvp-submitted')
        ->assertDispatched('rsvp-celebrate')
        ->assertSee('See you there!')
        ->assertSee(strtoupper($invitation->code));

    $invitation->refresh();

    expect($invitation->status)->toBe(RsvpStatus::Attending)
        ->and($invitation->attending_count)->toBe(2)
        ->and($invitation->guest_names)->toBe(['Juan Dela Cruz', 'Maria Dela Cruz'])
        ->and($invitation->dietary_notes)->toBe('No seafood')
        ->and($invitation->song_request)->toBe('Perfect - Ed Sheeran')
        ->and($invitation->seats)->toBe(3)
        ->and($invitation->responded_at->toDateTimeString())->toBe(now()->toDateTimeString());

    expect($invitation->wish->name)->toBe($invitation->name)
        ->and($invitation->wish->message)->toBe('Congratulations to you both!')
        ->and($invitation->wish->is_shared)->toBeTrue();
});

test('guestDeclinesTheirPersonalInvitation', function () {
    $invitation = Invitation::factory()->create(['seats' => 2]);

    Livewire::test('rsvp', ['invitation' => $invitation])
        ->call('choose', false)
        ->assertSet('step', 'note')
        ->set('message', 'Sorry we cannot make it!')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('rsvp-submitted')
        ->assertNotDispatched('rsvp-celebrate')
        ->assertSee('You will be missed');

    $invitation->refresh();

    expect($invitation->status)->toBe(RsvpStatus::Declined)
        ->and($invitation->attending_count)->toBe(0)
        ->and($invitation->guest_names)->toBeNull()
        ->and($invitation->wish->message)->toBe('Sorry we cannot make it!');
});

test('guestWhoLeavesNoNoteHasNoWish', function () {
    $invitation = Invitation::factory()->create();

    Livewire::test('rsvp', ['invitation' => $invitation])
        ->call('choose', false)
        ->set('message', '   ')
        ->call('submit')
        ->assertHasNoErrors();

    expect($invitation->wish()->exists())->toBeFalse();
});

test('guestChangingTheirReplyUpdatesTheirWishAndKeepsItsHearts', function () {
    $invitation = Invitation::factory()->declined()->create();
    $wish = Wish::factory()->for($invitation)->create(['message' => 'First note', 'hearts_count' => 4]);

    Livewire::test('rsvp', ['invitation' => $invitation])
        ->assertSet('message', 'First note')
        ->call('changeReply')
        ->call('choose', false)
        ->set('message', 'Second note')
        ->set('shareMessage', false)
        ->call('submit')
        ->assertHasNoErrors();

    $wish->refresh();

    expect(Wish::query()->count())->toBe(1)
        ->and($wish->message)->toBe('Second note')
        ->and($wish->is_shared)->toBeFalse()
        ->and($wish->hearts_count)->toBe(4);
});

test('guestChangingTheirReplyCannotBringBackAWishTheCoupleHid', function () {
    $invitation = Invitation::factory()->declined()->create();
    $wish = Wish::factory()->for($invitation)->hiddenByCouple()->create(['message' => 'First note']);

    Livewire::test('rsvp', ['invitation' => $invitation])
        ->call('changeReply')
        ->call('choose', false)
        ->set('message', 'Second note')
        ->set('shareMessage', true)
        ->call('submit')
        ->assertHasNoErrors();

    expect($wish->refresh()->is_hidden)->toBeTrue()
        ->and(Wish::query()->shared()->exists())->toBeFalse();
});

test('guestWhoClearsTheirNoteRemovesTheirWish', function () {
    $invitation = Invitation::factory()->declined()->create();
    $wish = Wish::factory()->for($invitation)->create();

    Livewire::test('rsvp', ['invitation' => $invitation])
        ->call('changeReply')
        ->call('choose', false)
        ->set('message', '')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertModelMissing($wish);
});

test('guestCannotAddMoreGuestsThanTheirReservedSeats', function () {
    $invitation = Invitation::factory()->create(['seats' => 2]);

    Livewire::test('rsvp', ['invitation' => $invitation])
        ->call('choose', true)
        ->call('changeAttendingCount', 5)
        ->assertSet('attendingCount', 2)
        ->set('attendingCount', 3)
        ->set('guestNames', ['A', 'B', 'C'])
        ->call('confirmParty')
        ->assertHasErrors(['attendingCount' => 'max']);
});

test('everyAttendingGuestNeedsAName', function () {
    $invitation = Invitation::factory()->create(['seats' => 2]);

    Livewire::test('rsvp', ['invitation' => $invitation])
        ->call('choose', true)
        ->set('guestNames', ['Juan Dela Cruz', ''])
        ->call('confirmParty')
        ->assertHasErrors(['guestNames.1' => 'required'])
        ->assertSee('The guest name field is required.')
        ->assertSet('step', 'party');
});

test('guestWhoAlreadyRepliedSeesTheirReplyAndCanChangeIt', function () {
    $invitation = Invitation::factory()->attending()->create(['seats' => 2]);

    Livewire::test('rsvp', ['invitation' => $invitation])
        ->assertSet('step', 'done')
        ->assertSet('attendingCount', 2)
        ->assertSee($invitation->guest_names[0])
        ->call('changeReply')
        ->assertSet('step', 'choice')
        ->call('choose', false)
        ->call('submit')
        ->assertHasNoErrors();

    expect($invitation->refresh()->status)->toBe(RsvpStatus::Declined);
});

test('guestWhoDeclinedSeesTheirNameWhenChangingToAttend', function () {
    $invitation = Invitation::factory()->declined()->create(['name' => 'Lola Nena', 'seats' => 1]);

    Livewire::test('rsvp', ['invitation' => $invitation])
        ->call('changeReply')
        ->call('choose', true)
        ->assertSet('guestNames', ['Lola Nena']);
});

test('repliesAreClosedAfterTheDeadline', function () {
    config(['wedding.rsvp_deadline' => now()->subDay()->format('Y-m-d H:i')]);
    $invitation = Invitation::factory()->create();

    Livewire::test('rsvp', ['invitation' => $invitation])
        ->assertSet('step', 'closed')
        ->assertSee('Our guest list is complete')
        ->set('attending', false)
        ->call('submit')
        ->assertSet('step', 'closed');

    expect($invitation->refresh()->status)->toBe(RsvpStatus::Pending);
});

test('repliesStayOpenThroughoutTheDeadlineMinute', function () {
    $this->travelTo(now()->setSecond(30));
    config(['wedding.rsvp_deadline' => now()->setTimezone(config('wedding.timezone'))->format('Y-m-d H:i')]);

    Livewire::test('rsvp', ['invitation' => Invitation::factory()->create()])
        ->assertSet('step', 'choice');
});

test('guestWhoRepliedCannotChangeTheirReplyAfterTheDeadline', function () {
    config(['wedding.rsvp_deadline' => now()->subDay()->format('Y-m-d H:i')]);
    $invitation = Invitation::factory()->attending()->create();

    Livewire::test('rsvp', ['invitation' => $invitation])
        ->assertSet('step', 'done')
        ->assertDontSee('Change my reply')
        ->call('changeReply')
        ->assertSet('step', 'done');
});

test('wishesWallShowsOnlyWishesGuestsAgreedToShare', function () {
    Wish::factory()->for(Invitation::factory()->attending())->create(['name' => 'Tita Baby', 'message' => 'May your love grow every day!']);
    Wish::factory()->for(Invitation::factory()->attending())->hidden()->create(['name' => 'Tito Boy', 'message' => 'A private note']);

    Livewire::test('wishes')
        ->assertSee('May your love grow every day!')
        ->assertSee('Tita Baby')
        ->assertDontSee('A private note');
});

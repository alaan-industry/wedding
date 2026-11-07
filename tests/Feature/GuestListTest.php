<?php

use App\Enums\RsvpStatus;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Wish;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create();
    config(['wedding.admin_emails' => [strtolower($this->admin->email)]]);
});

test('guestsAreRedirectedToTheLoginPage', function () {
    $this->get(route('guests.index'))->assertRedirect(route('login'));
});

test('usersWhoAreNotTheCoupleCannotSeeTheGuestList', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('guests.index'))
        ->assertForbidden();
});

test('coupleCanSeeTheGuestListWithReplies', function () {
    Invitation::factory()->attending()->create(['name' => 'The Santos Family', 'seats' => 3]);
    Invitation::factory()->create(['name' => 'Lola Nena', 'seats' => 1]);

    $this->actingAs($this->admin)
        ->get(route('guests.index'))
        ->assertOk()
        ->assertSee('The Santos Family')
        ->assertSee('Lola Nena');
});

test('guestListSummarisesTheReplies', function () {
    Invitation::factory()->attending()->create(['seats' => 3]);
    Invitation::factory()->declined()->create(['seats' => 2]);
    Invitation::factory()->create(['seats' => 1, 'opened_at' => null]);

    $this->actingAs($this->admin);

    Livewire::test('pages::guests')
        ->assertSet('stats', [
            'households' => 3,
            'seats' => 6,
            'attending' => 3,
            'declined' => 1,
            'pending' => 1,
            'opened' => 2,
        ]);
});

test('coupleCanInviteAHousehold', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::guests')
        ->set('name', 'The Reyes Family')
        ->set('seats', 4)
        ->set('phone', '09181234567')
        ->call('addInvitation')
        ->assertHasNoErrors()
        ->assertSet('name', '');

    $invitation = Invitation::query()->sole();

    expect($invitation->name)->toBe('The Reyes Family')
        ->and($invitation->seats)->toBe(4)
        ->and($invitation->phone)->toBe('09181234567')
        ->and($invitation->status)->toBe(RsvpStatus::Pending)
        ->and($invitation->code)->toHaveLength(8);
});

test('invitingAHouseholdRequiresANameAndAtLeastOneSeat', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::guests')
        ->set('name', '')
        ->set('seats', 0)
        ->call('addInvitation')
        ->assertHasErrors(['name' => 'required', 'seats' => 'min']);

    expect(Invitation::query()->count())->toBe(0);
});

test('coupleCanRemoveAHousehold', function () {
    $invitation = Invitation::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test('pages::guests')
        ->call('deleteInvitation', $invitation->id)
        ->assertHasNoErrors();

    $this->assertModelMissing($invitation);
});

test('coupleCanHideAWishFromTheWall', function () {
    $wish = Wish::factory()->for(Invitation::factory()->attending())->create();

    $this->actingAs($this->admin);

    Livewire::test('pages::guests')
        ->call('toggleWish', $wish->id)
        ->assertHasNoErrors();

    expect($wish->refresh()->is_hidden)->toBeTrue();
});

test('coupleSeesAndCanHideWishesFromVisitors', function () {
    $wish = Wish::factory()->create(['name' => 'Aling Rosa', 'message' => 'Congratulations from your neighbors!']);

    $this->actingAs($this->admin);

    Livewire::test('pages::guests')
        ->assertSee('Aling Rosa')
        ->assertSee('Congratulations from your neighbors!')
        ->call('toggleWish', $wish->id)
        ->assertHasNoErrors();

    expect($wish->refresh()->is_hidden)->toBeTrue();
});

test('coupleApprovesAVisitorWishOntoTheWall', function () {
    $wish = Wish::factory()->hiddenByCouple()->create(['message' => 'Congratulations from your neighbors!']);

    Livewire::test('wishes')->assertDontSee('Congratulations from your neighbors!');

    $this->actingAs($this->admin);

    Livewire::test('pages::guests')
        ->assertSee('1 not on the wall yet')
        ->assertSee('Approve')
        ->call('toggleWish', $wish->id)
        ->assertHasNoErrors()
        ->assertDontSee('not on the wall yet');

    Livewire::test('wishes')->assertSee('Congratulations from your neighbors!');
});

test('usersWhoAreNotTheCoupleCannotHideWishes', function () {
    $wish = Wish::factory()->create();

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::guests')
        ->call('toggleWish', $wish->id)
        ->assertForbidden();

    expect($wish->refresh()->is_hidden)->toBeFalse();
});

test('guestListCanBeFilteredByReply', function () {
    Invitation::factory()->attending()->create(['name' => 'Coming Family']);
    Invitation::factory()->declined()->create(['name' => 'Absent Family']);

    $this->actingAs($this->admin);

    Livewire::test('pages::guests')
        ->set('status', RsvpStatus::Declined->value)
        ->assertSee('Absent Family')
        ->assertDontSee('Coming Family');
});

test('inviteMessageContainsThePersonalLink', function () {
    $invitation = Invitation::factory()->create(['name' => 'Ninang Carmen']);

    $this->actingAs($this->admin);

    $message = Livewire::test('pages::guests')->instance()->inviteMessage($invitation);

    expect($message)
        ->toStartWith('Hi Ninang Carmen!')
        ->toEndWith(route('invitation.show', $invitation));
});

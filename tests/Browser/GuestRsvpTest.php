<?php

use App\Enums\RsvpStatus;
use App\Models\Invitation;
use App\Models\User;

beforeEach(function () {
    config(['wedding.rsvp_deadline' => now()->addMonth()->format('Y-m-d H:i')]);
});

test('guestAcceptsForPartOfTheirPartyAndTheCoupleSeesTheReply', function () {
    $invitation = Invitation::factory()->create(['name' => 'The Reyes Family', 'seats' => 3]);

    $page = visit(route('invitation.show', $invitation, absolute: false));

    openEnvelope($page)->click('@accept-button');

    $page->click('button[aria-label="Remove a guest"]');

    waitUntil($page, "document.querySelector('[data-test=\"attending-count\"]').textContent.trim() === '2'", 'the party to shrink to 2');

    $page->fill('input[wire\:model="guestNames.0"]', 'Mario Reyes')
        ->fill('input[wire\:model="guestNames.1"]', 'Liza Reyes')
        ->fill('input[wire\:model="dietaryNotes"]', 'No seafood')
        ->click('Continue')
        ->fill('textarea[wire\:model="message"]', 'So happy for you both!')
        ->fill('input[wire\:model="songRequest"]', 'Sway by Michael Buble')
        ->click('@send-reply-button');

    waitForVisibleText($page, 'See you there!');

    $page->assertSeeIn('@admission-pass', 'Mario Reyes')
        ->assertSeeIn('@admission-pass', 'Liza Reyes')
        ->assertSeeIn('@admission-pass', strtoupper($invitation->code));

    waitForVisibleText($page, 'So happy for you both!');

    $page->assertNoJavaScriptErrors();

    $invitation->refresh();

    expect($invitation->status)->toBe(RsvpStatus::Attending)
        ->and($invitation->attending_count)->toBe(2)
        ->and($invitation->guest_names)->toBe(['Mario Reyes', 'Liza Reyes'])
        ->and($invitation->dietary_notes)->toBe('No seafood')
        ->and($invitation->song_request)->toBe('Sway by Michael Buble')
        ->and($invitation->opened_at)->not->toBeNull()
        ->and($invitation->wish->is_shared)->toBeTrue();

    $admin = User::factory()->create();
    config(['wedding.admin_emails' => [strtolower($admin->email)]]);
    $this->actingAs($admin);

    visit(route('guests.index', absolute: false))
        ->assertSeeIn('tr:has-text("The Reyes Family")', 'Attending')
        ->assertSeeIn('tr:has-text("The Reyes Family")', '2 / 3')
        ->assertSee('Mario Reyes, Liza Reyes')
        ->assertSee('No seafood')
        ->assertSee('Sway by Michael Buble')
        ->assertSee('So happy for you both!')
        ->assertSee('Hide from wall')
        ->assertNoJavaScriptErrors();
});

test('guestDeclinesWithANoteTheyKeepOffTheWall', function () {
    $invitation = Invitation::factory()->create(['name' => 'Tita Carmen', 'seats' => 1]);

    $page = visit(route('invitation.show', $invitation, absolute: false));

    openEnvelope($page)
        ->click('@decline-button')
        ->fill('textarea[wire\:model="message"]', 'Praying for you from Cebu.')
        ->uncheck('input[wire\:model="shareMessage"]')
        ->click('@send-reply-button');

    waitForVisibleText($page, 'You will be missed');

    $page->assertDontSee('Praying for you from Cebu.')
        ->assertNoJavaScriptErrors();

    $invitation->refresh();

    expect($invitation->status)->toBe(RsvpStatus::Declined)
        ->and($invitation->attending_count)->toBe(0)
        ->and($invitation->wish->message)->toBe('Praying for you from Cebu.')
        ->and($invitation->wish->is_shared)->toBeFalse();
});

test('guestChangesAnAcceptedReplyToADecline', function () {
    $invitation = Invitation::factory()->attending()->create(['name' => 'The Lim Family', 'seats' => 2]);

    $page = visit(route('invitation.show', $invitation, absolute: false));

    openEnvelope($page);

    $page->assertSee('See you there!')
        ->click('Change my reply')
        ->click('@decline-button')
        ->click('@send-reply-button');

    waitForVisibleText($page, 'You will be missed');

    $page->assertNoJavaScriptErrors();

    $invitation->refresh();

    expect($invitation->status)->toBe(RsvpStatus::Declined)
        ->and($invitation->attending_count)->toBe(0)
        ->and($invitation->guest_names)->toBeNull();
});

test('personalInvitationOpensWithTheGuestNameAndPoppableBubbles', function () {
    $invitation = Invitation::factory()->create(['name' => 'The Bautista Family']);

    $page = visit(route('invitation.show', $invitation, absolute: false))
        ->assertSeeIn('@envelope', 'The Bautista Family');

    openEnvelope($page)
        ->assertNotPresent('@send-wish-button');

    $page->script("() => document.querySelector('[data-test=\"bubble\"]').click()");

    waitUntil($page, "[...document.querySelectorAll('[data-test=\"bubble-note\"]')].some(note => note.offsetParent !== null)", 'a bubble note to appear');

    $page->assertNoJavaScriptErrors();
});

<?php

use App\Enums\RsvpStatus;
use App\Models\Invitation;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    config(['wedding.admin_emails' => [strtolower($this->admin->email)]]);
});

test('coupleSignsInAndInvitesAHouseholdFromTheGuestList', function () {
    $page = visit(route('login', absolute: false))
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->click('@login-button');

    waitUntil($page, "location.pathname === '/dashboard'", 'the sign in to finish');

    $page->navigate(route('guests.index', absolute: false))
        ->fill('name', 'The Dela Cruz Family')
        ->fill('seats', '4')
        ->fill('phone', '09171234567')
        ->click('@add-invitation-button');

    waitForVisibleText($page, 'Invitation created.');

    $invitation = Invitation::query()->sole();

    expect($invitation->name)->toBe('The Dela Cruz Family')
        ->and($invitation->seats)->toBe(4)
        ->and($invitation->phone)->toBe('09171234567')
        ->and($invitation->status)->toBe(RsvpStatus::Pending);

    $page->assertSee('The Dela Cruz Family')
        ->assertSee(strtoupper($invitation->code).' · 09171234567')
        ->assertValue('name', '')
        ->assertNoJavaScriptErrors();
});

test('coupleNarrowsTheGuestListBySearchAndReply', function () {
    Invitation::factory()->attending()->create(['name' => 'The Santos Family']);
    Invitation::factory()->declined()->create(['name' => 'Tito Boy']);
    Invitation::factory()->create(['name' => 'Lola Nena']);

    $this->actingAs($this->admin);

    $page = visit(route('guests.index', absolute: false))
        ->fill('search', 'Santos');

    waitForVisibleTextToDisappear($page, 'Lola Nena');

    $page->assertSee('The Santos Family')
        ->assertDontSee('Tito Boy')
        ->clear('search');

    waitForVisibleText($page, 'Lola Nena');

    $page->select('status', RsvpStatus::Declined->value);

    waitForVisibleTextToDisappear($page, 'The Santos Family');

    $page->assertSee('Tito Boy')
        ->assertDontSee('Lola Nena')
        ->assertNoJavaScriptErrors();
});

test('coupleRemovesAHouseholdAfterConfirming', function () {
    $invitation = Invitation::factory()->create(['name' => 'The Garcia Family']);

    $this->actingAs($this->admin);

    $page = visit(route('guests.index', absolute: false));

    $page->script('() => { window.confirm = () => true; }');
    $page->click('button[wire\:click^="deleteInvitation"]');

    waitForVisibleText($page, 'Invitation removed.');

    $page->assertSee('No invitations yet.')
        ->assertNoJavaScriptErrors();

    $this->assertModelMissing($invitation);
});

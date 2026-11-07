<?php

use App\Models\Invitation;
use App\Models\User;

test('generalInvitationRendersTheCoupleAndVenues', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Benjie')
        ->assertSee('Monaliza')
        ->assertSee(config('wedding.ceremony.name'))
        ->assertSee(config('wedding.reception.name'))
        ->assertDontSee('reserved in your honor');
});

test('personalInvitationGreetsTheHouseholdWithTheirReservedSeats', function () {
    $invitation = Invitation::factory()->create(['name' => 'The Dela Cruz Family', 'seats' => 3]);

    $this->get(route('invitation.show', $invitation))
        ->assertOk()
        ->assertSee('Dear The Dela Cruz Family,')
        ->assertSee('3 seats reserved in your honor');
});

test('generalInvitationAsksGuestsToUseTheirPersonalLink', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('RSVP is by personal invitation')
        ->assertSee('Please use the link we sent you.')
        ->assertDontSeeLivewire('rsvp');
});

test('personalInvitationShowsTheRsvpForm', function () {
    $invitation = Invitation::factory()->create();

    $this->get(route('invitation.show', $invitation))
        ->assertOk()
        ->assertSeeLivewire('rsvp')
        ->assertDontSee('data-test="personal-link-note"', false);
});

test('generalInvitationLetsVisitorsLeaveAWish', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-test="send-wish-button"', false);
});

test('personalInvitationCollectsWishesThroughTheRsvpInstead', function () {
    $invitation = Invitation::factory()->create();

    $this->get(route('invitation.show', $invitation))
        ->assertOk()
        ->assertDontSee('data-test="send-wish-button"', false);
});

test('personalInvitationRecordsWhenTheGuestFirstOpensIt', function () {
    $this->freezeSecond();

    $invitation = Invitation::factory()->create();

    $this->get(route('invitation.show', $invitation))->assertOk();

    expect($invitation->refresh()->opened_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('personalInvitationKeepsTheFirstOpenedTime', function () {
    $openedAt = now()->subWeek()->startOfSecond();
    $invitation = Invitation::factory()->create(['opened_at' => $openedAt]);

    $this->get(route('invitation.show', $invitation))->assertOk();

    expect($invitation->refresh()->opened_at->equalTo($openedAt))->toBeTrue();
});

test('adminPreviewDoesNotMarkTheInvitationAsOpened', function () {
    $admin = User::factory()->create();
    config(['wedding.admin_emails' => [$admin->email]]);
    $invitation = Invitation::factory()->create();

    $this->actingAs($admin)->get(route('invitation.show', $invitation))->assertOk();

    expect($invitation->refresh()->opened_at)->toBeNull();
});

test('chatLinkPreviewsDoNotMarkTheInvitationAsOpened', function (string $userAgent) {
    $invitation = Invitation::factory()->create();

    $this->withHeader('User-Agent', $userAgent)
        ->get(route('invitation.show', $invitation))
        ->assertOk();

    expect($invitation->refresh()->opened_at)->toBeNull();
})->with([
    'messenger' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'whatsapp' => 'WhatsApp/2.23.20.0 A',
    'viber' => 'Viber/20.3.0 CFNetwork/1485 Darwin/23.1.0',
]);

test('doveCarriesTheConfiguredNotes', function () {
    config(['wedding.dove_notes' => ['Thank you for celebrating with us']]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Thank you for celebrating with us');
});

test('musicButtonIsHiddenUntilASongIsAdded', function () {
    config(['wedding.music.path' => 'audio/missing-song.mp3']);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('data-test="music-toggle"', false);
});

test('musicButtonPlaysTheSongOnceItIsAdded', function () {
    config(['wedding.music.path' => 'robots.txt']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-test="music-toggle"', false)
        ->assertSee('robots.txt', false);
});

test('heroShowsOnlyThePhotosThatWereAdded', function () {
    config(['wedding.photos' => ['robots.txt', 'images/missing-photo.jpg']]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('robots.txt', false)
        ->assertDontSee('missing-photo.jpg', false)
        ->assertSee('data-test="photo-placeholder"', false);
});

test('unknownInvitationCodeReturnsNotFound', function () {
    $this->get(route('invitation.show', 'not-a-code'))->assertNotFound();
});

test('calendarDownloadContainsTheCeremonyAndReception', function () {
    config([
        'wedding.ceremony_starts_at' => '2027-01-30 14:00',
        'wedding.reception_starts_at' => '2027-01-30 17:00',
        'wedding.reception_ends_at' => '2027-01-30 22:00',
    ]);

    $response = $this->get(route('calendar'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertDownload('benjie-monaliza-wedding.ics');

    $calendar = $response->getContent();
    $longestLineLength = max(array_map(strlen(...), explode("\r\n", $calendar)));

    expect($longestLineLength)->toBeLessThanOrEqual(75)
        ->and(str_replace("\r\n ", '', $calendar))
        ->toContain('SUMMARY:Benjie & Monaliza — Wedding Ceremony')
        ->toContain('DTSTART:20270130T060000Z')
        ->toContain('DTEND:20270130T090000Z')
        ->toContain('LOCATION:San Pedro Parish\, Brgy. San Vicente\, Panabo City\, Davao del Norte')
        ->toContain('SUMMARY:Benjie & Monaliza — Wedding Reception')
        ->toContain('DTEND:20270130T140000Z');
});

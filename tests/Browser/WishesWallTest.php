<?php

use App\Models\Invitation;
use App\Models\User;
use App\Models\Wish;

beforeEach(function () {
    $this->admin = User::factory()->create();
    config(['wedding.admin_emails' => [strtolower($this->admin->email)]]);
});

test('visitorWishReachesTheWallOnlyAfterTheCoupleApprovesIt', function () {
    $page = visit(route('home', absolute: false));

    openEnvelope($page)
        ->fill('input[wire\:model="name"]', 'Aling Rosa')
        ->fill('textarea[wire\:model="message"]', 'Congratulations from your neighbors!')
        ->click('@send-wish-button');

    waitForVisibleText($page, 'Thank you for your wish!');

    $page->assertDontSee('Congratulations from your neighbors!')
        ->assertNoJavaScriptErrors();

    $wish = Wish::query()->sole();

    expect($wish->is_hidden)->toBeTrue();

    $this->actingAs($this->admin);

    $guestList = visit(route('guests.index', absolute: false))
        ->assertSee('1 not on the wall yet')
        ->click('@toggle-visitor-wish-button');

    waitForVisibleText($guestList, 'Wish shown on the wall.');

    $guestList->assertNoJavaScriptErrors();

    expect($wish->refresh()->is_hidden)->toBeFalse();

    openEnvelope(visit(route('home', absolute: false)))
        ->assertSee('Congratulations from your neighbors!')
        ->assertSee('Aling Rosa')
        ->assertNoJavaScriptErrors();
});

test('coupleHidesAGuestWishFromTheWall', function () {
    $invitation = Invitation::factory()->attending()->create(['name' => 'The Cruz Family']);
    $wish = Wish::factory()->for($invitation)->create(['name' => 'The Cruz Family', 'message' => 'Cheers to forever!']);

    $this->actingAs($this->admin);

    $guestList = visit(route('guests.index', absolute: false))
        ->click('@toggle-wish-button');

    waitForVisibleText($guestList, 'Wish hidden from the wall.');

    $guestList->assertSee('Show on wall')
        ->assertNoJavaScriptErrors();

    expect($wish->refresh()->is_hidden)->toBeTrue();

    openEnvelope(visit(route('home', absolute: false)))
        ->assertDontSee('Cheers to forever!')
        ->assertNoJavaScriptErrors();
});

test('visitorSendsAHeartToAWishAndTakesItBack', function () {
    $wish = Wish::factory()->create(['name' => 'Tito Ben', 'hearts_count' => 2]);

    $page = visit(route('home', absolute: false));

    openEnvelope($page)->click('@wish-heart');

    waitUntil($page, "document.querySelector('[data-test=\"wish-heart\"]').getAttribute('aria-pressed') === 'true'", 'the heart to be sent');

    $page->assertSeeIn('@wish-heart', '3');

    expect($wish->refresh()->hearts_count)->toBe(3);

    $page->click('@wish-heart');

    waitUntil($page, "document.querySelector('[data-test=\"wish-heart\"]').getAttribute('aria-pressed') === 'false'", 'the heart to be taken back');

    $page->assertSeeIn('@wish-heart', '2')
        ->assertNoJavaScriptErrors();

    expect($wish->refresh()->hearts_count)->toBe(2);
});

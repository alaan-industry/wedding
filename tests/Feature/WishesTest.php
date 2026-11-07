<?php

use App\Models\Invitation;
use App\Models\Wish;
use Livewire\Livewire;

test('guestSendsAHeartToASharedWish', function () {
    $wish = Wish::factory()->create(['hearts_count' => 2]);

    Livewire::test('wishes')
        ->call('toggleHeart', $wish->id)
        ->assertSeeHtml('aria-pressed="true"');

    expect($wish->refresh()->hearts_count)->toBe(3);
});

test('guestTakesTheirHeartBackWhenTappingAgain', function () {
    $wish = Wish::factory()->create(['hearts_count' => 2]);

    Livewire::test('wishes')
        ->call('toggleHeart', $wish->id)
        ->call('toggleHeart', $wish->id)
        ->assertSeeHtml('aria-pressed="false"');

    expect($wish->refresh()->hearts_count)->toBe(2);
});

test('guestCannotHeartAWishThatIsNotShared', function () {
    $privateWish = Wish::factory()->hidden()->create();

    Livewire::test('wishes')
        ->call('toggleHeart', $privateWish->id)
        ->assertNotFound();

    expect($privateWish->refresh()->hearts_count)->toBe(0);
});

test('visitorWithoutAnInvitationLeavesAWishThatWaitsForApproval', function () {
    Livewire::test('wishes', ['acceptsWishes' => true])
        ->set('name', '  Aling Rosa  ')
        ->set('message', 'Congratulations from your neighbors!')
        ->call('sendWish')
        ->assertHasNoErrors()
        ->assertSet('wishSent', true)
        ->assertSet('name', '')
        ->assertSee('Thank you for your wish!')
        ->assertDontSee('Congratulations from your neighbors!');

    $wish = Wish::query()->sole();

    expect($wish->name)->toBe('Aling Rosa')
        ->and($wish->invitation_id)->toBeNull()
        ->and($wish->is_hidden)->toBeTrue()
        ->and(Invitation::query()->count())->toBe(0);
});

test('visitorWishNeedsANameAndAMessage', function () {
    Livewire::test('wishes', ['acceptsWishes' => true])
        ->call('sendWish')
        ->assertHasErrors(['name' => 'required', 'message' => 'required'])
        ->assertSee('The name field is required.')
        ->assertSee('The message field is required.');

    expect(Wish::query()->count())->toBe(0);
});

test('wishFormIsOnlyOfferedWhereVisitorsMayPost', function () {
    Livewire::test('wishes')
        ->assertDontSee('Leave us a wish')
        ->set('name', 'Aling Rosa')
        ->set('message', 'Sneaky wish')
        ->call('sendWish');

    expect(Wish::query()->count())->toBe(0);
});

test('visitorWishesAreRateLimitedPerDevice', function () {
    foreach (range(1, 4) as $attempt) {
        $component = Livewire::test('wishes', ['acceptsWishes' => true])
            ->set('name', "Visitor {$attempt}")
            ->set('message', 'Best wishes!')
            ->call('sendWish');
    }

    $component->assertHasErrors('message')
        ->assertSee('We have received several wishes from this device.');

    expect(Wish::query()->count())->toBe(3);
});

<?php

use App\Models\Invitation;

beforeEach(function () {
    config(['wedding.rsvp_deadline' => now()->addMonth()->format('Y-m-d H:i')]);
});

test('invitationFitsTheScreenWidthOfASmallPhone', function () {
    $invitation = Invitation::factory()->create();

    $page = visit(route('invitation.show', $invitation, absolute: false))->on()->iPhoneSE()->assertNoJavaScriptErrors();

    openEnvelope($page)->assertVisible('@decline-button');

    $overflowingWidth = $page->script('() => document.documentElement.scrollWidth - document.documentElement.clientWidth');

    expect($overflowingWidth)->toBeLessThanOrEqual(0);
});

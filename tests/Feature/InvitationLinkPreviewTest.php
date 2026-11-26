<?php

use App\Jobs\RefreshInvitationLinkPreview;
use App\Models\Invitation;
use App\Support\FacebookLinkPreview;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    config()->set('services.facebook.app_id', '123');
    config()->set('services.facebook.app_secret', 'secret');
});

test('creatingAnInvitationQueuesALinkPreviewRefresh', function () {
    Bus::fake();

    $invitation = Invitation::factory()->create();

    Bus::assertDispatched(RefreshInvitationLinkPreview::class, fn (RefreshInvitationLinkPreview $job): bool => $job->invitation->is($invitation));
});

test('creatingAnInvitationSkipsTheRefreshWhenFacebookIsNotConfigured', function () {
    config()->set('services.facebook.app_secret', null);
    Bus::fake();

    Invitation::factory()->create();

    Bus::assertNotDispatched(RefreshInvitationLinkPreview::class);
});

test('refreshAsksFacebookToScrapeThePersonalInvitationLink', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'ok'])]);
    Bus::fake();
    $invitation = Invitation::factory()->create();

    (new RefreshInvitationLinkPreview($invitation))->handle(app(FacebookLinkPreview::class));

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && str_starts_with($request->url(), 'https://graph.facebook.com/')
        && $request['id'] === route('invitation.show', $invitation)
        && $request['scrape'] === 'true'
        && $request['access_token'] === '123|secret');
});

test('refreshReturnsFalseInsteadOfThrowingWhenFacebookFails', function () {
    Sleep::fake();
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'boom'], 500)]);

    expect(app(FacebookLinkPreview::class)->refresh('https://example.com/i/abc'))->toBeFalse();
});

test('refreshDoesNotRetryARequestFacebookRejects', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'Invalid OAuth access token'], 400)]);

    expect(app(FacebookLinkPreview::class)->refresh('https://example.com/i/abc'))->toBeFalse();

    Http::assertSentCount(1);
});

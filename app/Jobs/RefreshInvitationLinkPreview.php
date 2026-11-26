<?php

namespace App\Jobs;

use App\Models\Invitation;
use App\Support\FacebookLinkPreview;
use Illuminate\Foundation\Bus\Dispatchable;

class RefreshInvitationLinkPreview
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * Dispatched after the response rather than queued, since production runs no queue worker.
     */
    public function __construct(public Invitation $invitation) {}

    /**
     * Ask Facebook to fetch the personal invitation link before it is shared.
     */
    public function handle(FacebookLinkPreview $linkPreview): void
    {
        $linkPreview->refresh($this->invitation->url());
    }
}

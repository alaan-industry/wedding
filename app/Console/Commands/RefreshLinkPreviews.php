<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use App\Support\FacebookLinkPreview;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:refresh-link-previews')]
#[Description('Ask Facebook to fetch the home page and every invitation link again, so shared previews pick up a new picture')]
class RefreshLinkPreviews extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(FacebookLinkPreview $linkPreview): int
    {
        if (! $linkPreview->isConfigured()) {
            $this->error('Set FACEBOOK_APP_ID and FACEBOOK_APP_SECRET before refreshing link previews.');

            return self::FAILURE;
        }

        $failed = $linkPreview->refresh(route('home')) ? 0 : 1;

        Invitation::query()->lazyById()->each(function (Invitation $invitation) use ($linkPreview, &$failed): void {
            if (! $linkPreview->refresh($invitation->url())) {
                $failed++;
            }
        });

        if ($failed > 0) {
            $this->warn("Facebook could not refresh {$failed} link(s). See the log for details.");

            return self::FAILURE;
        }

        $this->info('Facebook refreshed every link preview.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController extends Controller
{
    /**
     * User agent fragments of the bots that fetch a link to show its preview in a chat.
     *
     * @var list<string>
     */
    protected const array LINK_PREVIEW_CRAWLERS = [
        'facebookexternalhit',
        'facebookcatalog',
        'meta-externalagent',
        'WhatsApp',
        'Viber',
        'TelegramBot',
        'Twitterbot',
        'Slackbot',
        'Discordbot',
        'LinkedInBot',
        'SkypeUriPreview',
        'Google-PageRenderer',
    ];

    /**
     * Show the general invitation shared with everyone.
     */
    public function index(): View
    {
        return view('invitation', ['invitation' => null]);
    }

    /**
     * Show a household's personal invitation.
     */
    public function show(Request $request, Invitation $invitation): View
    {
        $isAdminPreview = (bool) $request->user()?->can('manage-invitations');

        if (! $isAdminPreview && ! $this->isLinkPreviewCrawler($request)) {
            $invitation->markAsOpened();
        }

        return view('invitation', ['invitation' => $invitation]);
    }

    /**
     * Determine if the request comes from a chat app building a link preview rather than the guest.
     */
    protected function isLinkPreviewCrawler(Request $request): bool
    {
        return Str::contains((string) $request->userAgent(), self::LINK_PREVIEW_CRAWLERS, ignoreCase: true);
    }
}

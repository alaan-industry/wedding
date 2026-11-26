<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FacebookLinkPreview
{
    /**
     * Determine if a Facebook app is configured to refresh link previews.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.facebook.app_id')) && filled(config('services.facebook.app_secret'));
    }

    /**
     * Ask Facebook to fetch the URL now, so Messenger shows its preview the first time the link is shared.
     *
     * Failures are logged instead of thrown, so a Facebook outage never gets in the way of the guest list.
     */
    public function refresh(string $url): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            Http::asForm()
                ->timeout(10)
                ->retry(2, 500, fn (Throwable $exception): bool => $this->isWorthRetrying($exception))
                ->post('https://graph.facebook.com/'.config('services.facebook.graph_version'), [
                    'id' => $url,
                    'scrape' => 'true',
                    'access_token' => config('services.facebook.app_id').'|'.config('services.facebook.app_secret'),
                ]);
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Facebook could not refresh the link preview.', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Retry only failures that may pass on their own, not rejected requests such as a wrong app secret.
     */
    protected function isWorthRetrying(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            || ($exception instanceof RequestException && $exception->response->serverError());
    }
}

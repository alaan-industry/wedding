<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class Wedding
{
    /**
     * Get the couple's display names, e.g. "Benjie & Monaliza".
     */
    public function coupleNames(): string
    {
        return config('wedding.groom.first_name').' & '.config('wedding.bride.first_name');
    }

    /**
     * Get the monogram initials, e.g. "B & M".
     */
    public function monogram(): string
    {
        return mb_substr(config('wedding.groom.first_name'), 0, 1).' & '.mb_substr(config('wedding.bride.first_name'), 0, 1);
    }

    /**
     * Get when the wedding ceremony starts.
     */
    public function ceremonyStartsAt(): CarbonImmutable
    {
        return $this->parse(config('wedding.ceremony_starts_at'));
    }

    /**
     * Get when the reception starts.
     */
    public function receptionStartsAt(): CarbonImmutable
    {
        return $this->parse(config('wedding.reception_starts_at'));
    }

    /**
     * Get when the reception ends.
     */
    public function receptionEndsAt(): CarbonImmutable
    {
        return $this->parse(config('wedding.reception_ends_at'));
    }

    /**
     * Get the last moment guests may reply.
     */
    public function rsvpDeadline(): CarbonImmutable
    {
        return $this->parse(config('wedding.rsvp_deadline'));
    }

    /**
     * Determine if guests may still send or change their reply.
     */
    public function isRsvpOpen(): bool
    {
        return now()->lessThanOrEqualTo($this->rsvpDeadline()->endOfMinute());
    }

    /**
     * Get the URL of the background song, or null when no song was added.
     */
    public function musicUrl(): ?string
    {
        return $this->publicAssetUrl(config('wedding.music.path'));
    }

    /**
     * Get the URL of each configured photo, or null for photos not added yet.
     *
     * @return list<?string>
     */
    public function photoUrls(): array
    {
        return array_map($this->publicAssetUrl(...), array_values(config('wedding.photos', [])));
    }

    /**
     * Get the URL of the link preview picture, falling back to the first photo, or null when neither exists.
     */
    public function ogImageUrl(): ?string
    {
        return $this->publicAssetUrl(config('wedding.og_image')) ?? ($this->photoUrls()[0] ?? null);
    }

    /**
     * Get the URL of a file in the public folder, or null when it does not exist.
     */
    protected function publicAssetUrl(?string $path): ?string
    {
        if (blank($path) || ! file_exists(public_path($path))) {
            return null;
        }

        return asset($path);
    }

    /**
     * Parse a configured local date and time in the wedding's timezone.
     *
     * @throws InvalidArgumentException when the configured value is empty.
     */
    protected function parse(?string $dateTime): CarbonImmutable
    {
        if (blank($dateTime)) {
            throw new InvalidArgumentException('A wedding date in config/wedding.php is empty. Check the WEDDING_* date variables in your .env file.');
        }

        return CarbonImmutable::parse($dateTime, config('wedding.timezone'));
    }
}

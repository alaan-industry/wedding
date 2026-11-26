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
     * Get the "Our Adventures" photos that exist and have a date, oldest first.
     *
     * @return list<array{url: string, caption: ?string, taken_on: CarbonImmutable, featured: bool}>
     */
    public function adventurePhotos(): array
    {
        $photos = [];

        foreach (config('wedding.adventures', []) as $photo) {
            $url = $this->publicAssetUrl($photo['path'] ?? null);

            if ($url === null || blank($photo['taken_on'] ?? null)) {
                continue;
            }

            $photos[] = [
                'url' => $url,
                'caption' => $photo['caption'] ?? null,
                'taken_on' => $this->parse($photo['taken_on']),
                'featured' => (bool) ($photo['featured'] ?? false),
            ];
        }

        usort($photos, fn (array $first, array $second): int => $first['taken_on'] <=> $second['taken_on']);

        return $photos;
    }

    /**
     * Get the position of the featured "Our Adventures" photo the carousel opens on, or 0 when none is featured.
     *
     * @param  list<array{url: string, caption: ?string, taken_on: CarbonImmutable, featured: bool}>  $photos
     */
    public function featuredAdventureIndex(array $photos): int
    {
        foreach ($photos as $index => $photo) {
            if ($photo['featured']) {
                return $index;
            }
        }

        return 0;
    }

    /**
     * Get the link preview picture with its size and type, falling back to the first photo, or null when neither exists.
     *
     * Chat apps such as Messenger only show the picture on the first share of a link when its size is given.
     *
     * @return array{url: string, width: int, height: int, type: string}|null
     */
    public function ogImage(): ?array
    {
        $photos = array_values(config('wedding.photos', []));

        foreach ([config('wedding.og_image'), $photos[0] ?? null] as $path) {
            $url = $this->publicAssetUrl($path);
            $size = $url === null ? false : getimagesize(public_path($path));

            if ($size !== false) {
                return ['url' => $url, 'width' => $size[0], 'height' => $size[1], 'type' => $size['mime']];
            }
        }

        return null;
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

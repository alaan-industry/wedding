<?php

namespace App\Http\Controllers;

use App\Support\Wedding;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CalendarController extends Controller
{
    /**
     * Download the wedding day as an iCalendar file guests can add to their calendar.
     */
    public function __invoke(Wedding $wedding): Response
    {
        $couple = $wedding->coupleNames();

        $events = [
            $this->event(
                uid: 'ceremony',
                summary: "{$couple} — Wedding Ceremony",
                startsAt: $wedding->ceremonyStartsAt(),
                endsAt: $wedding->receptionStartsAt(),
                location: config('wedding.ceremony.name').', '.config('wedding.ceremony.address'),
            ),
            $this->event(
                uid: 'reception',
                summary: "{$couple} — Wedding Reception",
                startsAt: $wedding->receptionStartsAt(),
                endsAt: $wedding->receptionEndsAt(),
                location: config('wedding.reception.name').', '.config('wedding.reception.address'),
            ),
        ];

        $calendar = implode("\r\n", array_map($this->fold(...), [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.$this->escape($couple).'//Wedding//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            ...array_merge(...$events),
            'END:VCALENDAR',
        ]))."\r\n";

        return response($calendar, Response::HTTP_OK, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($couple).'-wedding.ics"',
        ]);
    }

    /**
     * Build the lines of a single calendar event.
     *
     * @return list<string>
     */
    protected function event(string $uid, string $summary, CarbonImmutable $startsAt, CarbonImmutable $endsAt, string $location): array
    {
        $format = 'Ymd\THis\Z';

        return [
            'BEGIN:VEVENT',
            'UID:'.$uid.'-'.$startsAt->format('Ymd').'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.now()->utc()->format($format),
            'DTSTART:'.$startsAt->utc()->format($format),
            'DTEND:'.$endsAt->utc()->format($format),
            'SUMMARY:'.$this->escape($summary),
            'LOCATION:'.$this->escape($location),
            'DESCRIPTION:'.$this->escape('We cannot wait to celebrate with you! '.route('home')),
            'END:VEVENT',
        ];
    }

    /**
     * Escape text values according to RFC 5545.
     */
    protected function escape(string $value): string
    {
        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $value);
    }

    /**
     * Fold a content line into lines of at most 75 octets, as RFC 5545 requires, without splitting a multibyte character.
     */
    protected function fold(string $line): string
    {
        $folded = [];
        $current = '';
        $limit = 75;

        foreach (mb_str_split($line) as $character) {
            if (strlen($current) + strlen($character) > $limit) {
                $folded[] = $current;
                $current = '';
                $limit = 74;
            }

            $current .= $character;
        }

        $folded[] = $current;

        return implode("\r\n ", $folded);
    }
}

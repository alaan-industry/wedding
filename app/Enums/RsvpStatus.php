<?php

namespace App\Enums;

enum RsvpStatus: string
{
    case Pending = 'pending';
    case Attending = 'attending';
    case Declined = 'declined';

    /**
     * Get the human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting reply',
            self::Attending => 'Attending',
            self::Declined => 'Declined',
        };
    }

    /**
     * Get the Flux badge colour for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'zinc',
            self::Attending => 'green',
            self::Declined => 'rose',
        };
    }
}

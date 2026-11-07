<?php

namespace App\Models;

use App\Enums\RsvpStatus;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $phone
 * @property int $seats
 * @property RsvpStatus $status
 * @property int $attending_count
 * @property list<string>|null $guest_names
 * @property string|null $dietary_notes
 * @property string|null $song_request
 * @property Carbon|null $opened_at
 * @property Carbon|null $responded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'code',
    'name',
    'phone',
    'seats',
    'status',
    'attending_count',
    'guest_names',
    'dietary_notes',
    'song_request',
    'opened_at',
    'responded_at',
])]
class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory;

    /**
     * Characters used for invitation codes, without look-alikes such as 0/O and 1/l.
     */
    protected const string CODE_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'attending_count' => 0,
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::creating(function (Invitation $invitation): void {
            $invitation->code ??= static::generateUniqueCode();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RsvpStatus::class,
            'seats' => 'integer',
            'attending_count' => 'integer',
            'guest_names' => 'array',
            'opened_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * The wish the guest left with their reply.
     *
     * @return HasOne<Wish, $this>
     */
    public function wish(): HasOne
    {
        return $this->hasOne(Wish::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * Generate a short, unique and easy to read invitation code.
     */
    public static function generateUniqueCode(int $length = 8): string
    {
        do {
            $code = '';

            for ($i = 0; $i < $length; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Get the personal invitation link.
     */
    public function url(): string
    {
        return route('invitation.show', $this);
    }

    /**
     * Determine if the guest has already replied.
     */
    public function hasResponded(): bool
    {
        return $this->status !== RsvpStatus::Pending;
    }

    /**
     * Record the first time the guest opened their invitation.
     */
    public function markAsOpened(): void
    {
        if ($this->opened_at === null) {
            $this->forceFill(['opened_at' => now()])->save();
        }
    }

    /**
     * Scope a query to invitations with the given RSVP status.
     *
     * @param  Builder<Invitation>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, RsvpStatus $status): void
    {
        $query->where('status', $status);
    }
}

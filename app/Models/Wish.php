<?php

namespace App\Models;

use Database\Factories\WishFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $invitation_id
 * @property string $name
 * @property string $message
 * @property bool $is_shared
 * @property bool $is_hidden
 * @property int $hearts_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'message',
    'is_shared',
    'is_hidden',
])]
class Wish extends Model
{
    /** @use HasFactory<WishFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_shared' => true,
        'is_hidden' => false,
        'hearts_count' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
            'is_hidden' => 'boolean',
            'hearts_count' => 'integer',
        ];
    }

    /**
     * The invitation of the guest who left this wish, if they had one.
     *
     * @return BelongsTo<Invitation, $this>
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * Scope a query to wishes shown on the public wall: shared by the guest and not hidden by the couple.
     *
     * @param  Builder<Wish>  $query
     */
    #[Scope]
    protected function shared(Builder $query): void
    {
        $query->where('is_shared', true)->where('is_hidden', false);
    }

    /**
     * Scope a query to wishes left by visitors without an invitation.
     *
     * @param  Builder<Wish>  $query
     */
    #[Scope]
    protected function fromVisitors(Builder $query): void
    {
        $query->whereNull('invitation_id');
    }
}

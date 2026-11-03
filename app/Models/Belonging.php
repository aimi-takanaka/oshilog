<?php

namespace App\Models;

use App\EventType;
use App\Models\Concerns\BelongsToCurrentUser;
use Database\Factories\BelongingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property EventType $event_type
 * @property string $name
 * @property string|null $memo
 * @property bool $is_packed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Belonging extends Model
{
    /** @use HasFactory<BelongingFactory> */
    use BelongsToCurrentUser, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'event_type',
        'name',
        'memo',
        'is_packed',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'is_packed' => 'boolean',
        ];
    }

    /**
     * Scope a query by event type.
     *
     * @param  Builder<Belonging>  $query
     */
    public function scopeOfType(Builder $query, EventType $type): void
    {
        $query->where('event_type', $type);
    }
}

<?php

namespace App\Models;

use App\EventTimeOfDay;
use App\EventType;
use App\EventVisibility;
use App\Models\Concerns\BelongsToCurrentUser;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $venue_id
 * @property string $title
 * @property EventType $event_type
 * @property Carbon $event_date
 * @property EventTimeOfDay $time_of_day
 * @property string|null $weather
 * @property float|null $temperature_max
 * @property float|null $temperature_min
 * @property int|null $precipitation_probability
 * @property string|null $impression
 * @property EventVisibility $visibility
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use BelongsToCurrentUser, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'venue_id',
        'title',
        'event_type',
        'event_date',
        'time_of_day',
        'weather',
        'temperature_max',
        'temperature_min',
        'precipitation_probability',
        'impression',
        'visibility',
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
            'event_date' => 'date',
            'time_of_day' => EventTimeOfDay::class,
            'temperature_max' => 'decimal:1',
            'temperature_min' => 'decimal:1',
            'precipitation_probability' => 'integer',
            'visibility' => EventVisibility::class,
        ];
    }

    /**
     * The venue where this event took place.
     *
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * The companions who attended this event.
     *
     * @return BelongsToMany<Companion, $this>
     */
    public function companions(): BelongsToMany
    {
        return $this->belongsToMany(Companion::class, 'event_companion');
    }

    /**
     * Photos attached to this event.
     *
     * @return HasMany<Photo, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * Scope a query by event type.
     *
     * @param  Builder<Event>  $query
     */
    public function scopeOfType(Builder $query, EventType $type): void
    {
        $query->where('event_type', $type);
    }
}

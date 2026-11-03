<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentUser;
use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $address
 * @property float $latitude
 * @property float $longitude
 * @property string $nearest_station
 * @property string|null $access_note
 * @property string|null $entry_caution
 * @property string|null $seat_view
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use BelongsToCurrentUser, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'nearest_station',
        'access_note',
        'entry_caution',
        'seat_view',
    ];

    /**
     * Events held at this venue.
     *
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Spots near this venue.
     *
     * @return HasMany<Spot, $this>
     */
    public function spots(): HasMany
    {
        return $this->hasMany(Spot::class);
    }
}

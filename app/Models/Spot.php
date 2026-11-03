<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentUser;
use App\SpotCategory;
use App\SpotStatus;
use Database\Factories\SpotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $venue_id
 * @property string $name
 * @property SpotCategory $category
 * @property SpotStatus $status
 * @property string|null $memo
 * @property string|null $map_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Spot extends Model
{
    /** @use HasFactory<SpotFactory> */
    use BelongsToCurrentUser, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'venue_id',
        'name',
        'category',
        'status',
        'memo',
        'map_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => SpotCategory::class,
            'status' => SpotStatus::class,
        ];
    }

    /**
     * The venue this spot belongs to.
     *
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}

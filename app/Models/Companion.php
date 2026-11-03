<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentUser;
use Database\Factories\CompanionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property int|null $companion_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Companion extends Model
{
    /** @use HasFactory<CompanionFactory> */
    use BelongsToCurrentUser, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'companion_user_id',
    ];

    /**
     * Events this companion attended.
     *
     * @return BelongsToMany<Event, $this>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_companion');
    }
}

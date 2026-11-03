<?php

namespace App\Models;

use App\PhotoCategory;
use Database\Factories\PhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $event_id
 * @property PhotoCategory $category
 * @property string $image_path
 * @property string|null $caption
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Photo extends Model
{
    /** @use HasFactory<PhotoFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'category',
        'image_path',
        'caption',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => PhotoCategory::class,
        ];
    }

    /**
     * The event this photo belongs to.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Public URL for this photo, or null when the file is missing.
     */
    public function publicUrl(): ?string
    {
        if (! Storage::disk('public')->exists($this->image_path)) {
            return null;
        }

        return asset('storage/'.$this->image_path);
    }
}

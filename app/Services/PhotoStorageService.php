<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoStorageService
{
    /**
     * Store an uploaded photo for an event.
     */
    public function store(UploadedFile $file, int $userId, int $eventId): string
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $directory = "users/{$userId}/events/{$eventId}";

        $file->storeAs($directory, $filename, 'public');

        return "{$directory}/{$filename}";
    }

    /**
     * Delete a stored photo file.
     */
    public function delete(string $path): void
    {
        Storage::disk('public')->delete($path);
    }

    /**
     * Get the public URL for a stored photo path.
     */
    public function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }
}

<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Photo;
use App\PhotoCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'category' => fake()->randomElement(PhotoCategory::cases()),
            'image_path' => 'users/photos/'.fake()->uuid().'.jpg',
            'caption' => fake()->optional()->sentence(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Spot;
use App\Models\User;
use App\Models\Venue;
use App\SpotCategory;
use App\SpotStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Spot>
 */
class SpotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'user_id' => function (array $attributes) {
                return Venue::query()->whereKey($attributes['venue_id'] ?? null)->value('user_id')
                    ?? auth()->id()
                    ?? User::factory();
            },
            'name' => fake()->company(),
            'category' => fake()->randomElement(SpotCategory::cases()),
            'status' => fake()->randomElement(SpotStatus::cases()),
            'memo' => fake()->optional()->sentence(),
            'map_url' => fake()->optional()->url(),
        ];
    }

    /**
     * Indicate that the spot is on the wishlist.
     */
    public function wishlist(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SpotStatus::Wishlist,
        ]);
    }

    /**
     * Indicate that the spot has been visited.
     */
    public function visited(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SpotStatus::Visited,
        ]);
    }
}

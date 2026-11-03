<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fn () => auth()->id() ?? User::factory(),
            'name' => fake()->company().'ホール',
            'address' => fake()->address(),
            'latitude' => fake()->latitude(24, 46),
            'longitude' => fake()->longitude(123, 146),
            'nearest_station' => fake()->city().'駅',
            'access_note' => fake()->sentence(),
            'entry_caution' => fake()->optional()->sentence(),
            'seat_view' => fake()->optional()->sentence(),
        ];
    }
}

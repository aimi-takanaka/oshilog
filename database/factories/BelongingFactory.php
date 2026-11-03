<?php

namespace Database\Factories;

use App\EventType;
use App\Models\Belonging;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Belonging>
 */
class BelongingFactory extends Factory
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
            'event_type' => fake()->randomElement(EventType::cases()),
            'name' => fake()->word(),
            'memo' => fake()->optional()->sentence(),
            'is_packed' => false,
        ];
    }

    /**
     * Indicate that the item is for a live show.
     */
    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::Live,
        ]);
    }

    /**
     * Indicate that the item is for a stage performance.
     */
    public function stage(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::Stage,
        ]);
    }

    /**
     * Indicate that the item is for a musical.
     */
    public function musical(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::Musical,
        ]);
    }

    /**
     * Indicate that the item is for a general event.
     */
    public function event(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::Event,
        ]);
    }

    /**
     * Indicate that the item is for a filming location visit.
     */
    public function filmingLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::FilmingLocation,
        ]);
    }

    /**
     * Indicate that the item has been packed.
     */
    public function packed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_packed' => true,
        ]);
    }
}

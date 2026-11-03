<?php

namespace Database\Factories;

use App\EventTimeOfDay;
use App\EventType;
use App\EventVisibility;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
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
            'title' => fake()->sentence(3),
            'event_type' => fake()->randomElement(EventType::cases()),
            'event_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'time_of_day' => fake()->randomElement(EventTimeOfDay::cases()),
            'impression' => fake()->optional()->paragraph(),
            'visibility' => EventVisibility::Private,
        ];
    }

    /**
     * Indicate that the event is a live show.
     */
    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::Live,
        ]);
    }

    /**
     * Indicate that the event is a stage performance.
     */
    public function stage(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::Stage,
        ]);
    }

    /**
     * Indicate that the event is a musical.
     */
    public function musical(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::Musical,
        ]);
    }

    /**
     * Indicate that the event is a general event.
     */
    public function event(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::Event,
        ]);
    }
}

<?php

namespace App\Support;

class EventFormReturn
{
    public const KEY = 'event_form_return';

    /**
     * @param  array<string, mixed>  $draft
     */
    public static function remember(array $draft, string $route, ?int $eventId = null): void
    {
        if (! in_array($route, ['events.create', 'events.edit'], true)) {
            return;
        }

        session()->put(self::KEY, [
            'draft' => $draft,
            'route' => $route,
            'event_id' => $eventId,
            'venue_id' => null,
        ]);
    }

    public static function attachVenue(int $venueId): void
    {
        $payload = self::peek();

        if ($payload === null) {
            return;
        }

        $payload['venue_id'] = $venueId;
        session()->put(self::KEY, $payload);
    }

    /**
     * @return array{draft: array<string, mixed>, route: string, event_id: ?int, venue_id: ?int}|null
     */
    public static function peek(): ?array
    {
        $payload = session(self::KEY);

        if (! is_array($payload) || ! in_array($payload['route'] ?? null, ['events.create', 'events.edit'], true)) {
            return null;
        }

        return $payload;
    }

    /**
     * @return array{draft: array<string, mixed>, route: string, event_id: ?int, venue_id: ?int}|null
     */
    public static function pull(): ?array
    {
        $payload = self::peek();
        session()->forget(self::KEY);

        return $payload;
    }

    public static function url(): ?string
    {
        $payload = self::peek();

        if ($payload === null) {
            return null;
        }

        if ($payload['route'] === 'events.edit' && ! empty($payload['event_id'])) {
            return route('events.edit', $payload['event_id']);
        }

        if ($payload['route'] === 'events.create') {
            return route('events.create');
        }

        return null;
    }
}

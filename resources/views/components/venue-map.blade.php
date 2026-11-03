@props([
    'latitude',
    'longitude',
    'name',
    'id' => 'venue-map',
])

<div
    wire:ignore
    id="{{ $id }}"
    class="h-64 w-full rounded-lg border border-zinc-200 dark:border-zinc-700"
    data-lat="{{ $latitude }}"
    data-lng="{{ $longitude }}"
    data-name="{{ $name }}"
    data-marker-icon="{{ asset('img/map/pin.svg') }}"
    data-venue-map
></div>

<?php

namespace App\Services;

use App\Support\PresetVenues;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    /**
     * Geocode a venue using its name and address.
     *
     * Known venue names are usually indexed in OpenStreetMap, while Japanese
     * street addresses alone often return no results from Nominatim.
     *
     * @return array{latitude: float, longitude: float, address?: string}|null
     */
    public function geocodeVenue(string $name, string $address): ?array
    {
        $name = trim($name);
        $address = trim($address);

        $preset = PresetVenues::findByName($name);

        if ($preset !== null) {
            return [
                'latitude' => $preset['latitude'],
                'longitude' => $preset['longitude'],
                'address' => $preset['address'],
            ];
        }

        foreach (array_filter([$name, $address]) as $query) {
            $result = $this->geocode($query);

            if ($result === null) {
                continue;
            }

            if (($result['address'] ?? '') === '' && $address === '') {
                $result['address'] = $this->reverseGeocode($result['latitude'], $result['longitude']) ?? '';
            }

            return $result;
        }

        return null;
    }

    /**
     * Geocode a free-text query using OpenStreetMap Nominatim.
     *
     * @return array{latitude: float, longitude: float, address?: string}|null
     */
    public function geocode(string $query): ?array
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        try {
            $response = $this->client()->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'json',
                'limit' => 1,
                'countrycodes' => 'jp',
                'addressdetails' => 1,
                'accept-language' => 'ja',
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        /** @var array<int, array{lat?: string, lon?: string, address?: array<string, mixed>}>|null $results */
        $results = $response->json();

        if (empty($results[0]['lat']) || empty($results[0]['lon'])) {
            return null;
        }

        $formatted = $this->formatJapaneseAddress($results[0]['address'] ?? []);

        return array_filter([
            'latitude' => (float) $results[0]['lat'],
            'longitude' => (float) $results[0]['lon'],
            'address' => $formatted !== '' ? $formatted : null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * Resolve a Japanese address from map coordinates.
     */
    public function reverseGeocode(float $latitude, float $longitude): ?string
    {
        try {
            $response = $this->client()->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'jsonv2',
                'addressdetails' => 1,
                'zoom' => 18,
                'accept-language' => 'ja',
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        /** @var array{address?: array<string, mixed>}|null $data */
        $data = $response->json();

        $formatted = $this->formatJapaneseAddress($data['address'] ?? []);

        return $formatted !== '' ? $formatted : null;
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function formatJapaneseAddress(array $address): string
    {
        $prefecture = $this->addressPart($address, ['state', 'province']);
        $city = $this->addressPart($address, ['city', 'town', 'village', 'municipality', 'county']);

        if ($city !== '' && $prefecture !== '' && ($city === $prefecture || str_starts_with($city, $prefecture))) {
            $city = '';
        }

        $ward = $this->addressPart($address, ['city_district', 'suburb', 'borough']);

        if ($ward !== '' && ($ward === $city || str_contains($city, $ward))) {
            $ward = '';
        }

        $locality = $this->addressPart($address, ['neighbourhood', 'quarter', 'hamlet']);

        if ($locality !== '' && in_array($locality, [$ward, $city], true)) {
            $locality = '';
        }

        $street = $this->addressPart($address, ['road']).$this->addressPart($address, ['house_number']);

        return implode('', array_filter(
            [$prefecture, $city, $ward, $locality, $street],
            fn (string $part): bool => $part !== '',
        ));
    }

    /**
     * @param  array<string, mixed>  $address
     * @param  list<string>  $keys
     */
    private function addressPart(array $address, array $keys): string
    {
        foreach ($keys as $key) {
            $value = $address[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    private function client(): PendingRequest
    {
        $client = Http::withHeaders([
            'User-Agent' => config('services.nominatim.user_agent'),
        ])->timeout(5);

        if (! config('services.nominatim.verify_ssl')) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }
}

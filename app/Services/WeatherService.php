<?php

namespace App\Services;

use App\WeatherCode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    /**
     * Fetch daily weather for a location and date using Open-Meteo.
     *
     * @return array{
     *     weather: string,
     *     temperature_max: float,
     *     temperature_min: float,
     *     precipitation_probability: int|null
     * }|null
     */
    public function fetchForDate(float $latitude, float $longitude, string $date): ?array
    {
        $date = Carbon::parse($date)->toDateString();

        if ($date >= Carbon::today()->toDateString()) {
            return $this->fetchForecast($latitude, $longitude, $date);
        }

        return $this->fetchHistorical($latitude, $longitude, $date);
    }

    /**
     * @return array{
     *     weather: string,
     *     temperature_max: float,
     *     temperature_min: float,
     *     precipitation_probability: int|null
     * }|null
     */
    private function fetchForecast(float $latitude, float $longitude, string $date): ?array
    {
        try {
            $response = $this->client()->get(config('services.open_meteo.forecast_url'), [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                'timezone' => config('services.open_meteo.timezone'),
                'start_date' => $date,
                'end_date' => $date,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        /** @var array{daily?: array<string, mixed>}|null $payload */
        $payload = $response->json();

        return $this->parseDailyResponse($payload, includePrecipitationProbability: true);
    }

    /**
     * @return array{
     *     weather: string,
     *     temperature_max: float,
     *     temperature_min: float,
     *     precipitation_probability: int|null
     * }|null
     */
    private function fetchHistorical(float $latitude, float $longitude, string $date): ?array
    {
        try {
            $response = $this->client()->get(config('services.open_meteo.archive_url'), [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
                'timezone' => config('services.open_meteo.timezone'),
                'start_date' => $date,
                'end_date' => $date,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        /** @var array{daily?: array<string, mixed>}|null $payload */
        $payload = $response->json();

        return $this->parseDailyResponse($payload, includePrecipitationProbability: false);
    }

    /**
     * @param  array{daily?: array<string, mixed>}|null  $payload
     * @return array{
     *     weather: string,
     *     temperature_max: float,
     *     temperature_min: float,
     *     precipitation_probability: int|null
     * }|null
     */
    private function parseDailyResponse(?array $payload, bool $includePrecipitationProbability): ?array
    {
        $daily = $payload['daily'] ?? null;

        if (! is_array($daily)) {
            return null;
        }

        $weatherCode = $daily['weather_code'][0] ?? null;
        $temperatureMax = $daily['temperature_2m_max'][0] ?? null;
        $temperatureMin = $daily['temperature_2m_min'][0] ?? null;

        if ($weatherCode === null || $temperatureMax === null || $temperatureMin === null) {
            return null;
        }

        $precipitationProbability = null;

        if ($includePrecipitationProbability) {
            $probability = $daily['precipitation_probability_max'][0] ?? null;
            $precipitationProbability = $probability === null ? null : (int) $probability;
        }

        return [
            'weather' => WeatherCode::label((int) $weatherCode),
            'temperature_max' => (float) $temperatureMax,
            'temperature_min' => (float) $temperatureMin,
            'precipitation_probability' => $precipitationProbability,
        ];
    }

    private function client(): PendingRequest
    {
        $client = Http::timeout((int) config('services.open_meteo.timeout', 5));

        if (! config('services.open_meteo.verify_ssl')) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }
}

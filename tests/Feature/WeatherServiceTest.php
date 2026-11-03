<?php

namespace Tests\Feature;

use App\Services\WeatherService;
use App\WeatherCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_weather_code_labels_are_japanese(): void
    {
        $this->assertSame('晴れ', WeatherCode::label(0));
        $this->assertSame('雨', WeatherCode::label(61));
        $this->assertSame('雷雨', WeatherCode::label(95));
    }

    public function test_fetches_forecast_weather(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'daily' => [
                    'time' => ['2026-08-30'],
                    'weather_code' => [0],
                    'temperature_2m_max' => [28.5],
                    'temperature_2m_min' => [22.1],
                    'precipitation_probability_max' => [10],
                ],
            ]),
        ]);

        $result = app(WeatherService::class)->fetchForDate(35.6812, 139.7671, '2026-08-30');

        $this->assertSame('晴れ', $result['weather']);
        $this->assertSame(28.5, $result['temperature_max']);
        $this->assertSame(22.1, $result['temperature_min']);
        $this->assertSame(10, $result['precipitation_probability']);
    }

    public function test_fetches_historical_weather_without_precipitation_probability(): void
    {
        Http::fake([
            'archive-api.open-meteo.com/*' => Http::response([
                'daily' => [
                    'time' => ['2024-01-15'],
                    'weather_code' => [61],
                    'temperature_2m_max' => [8.2],
                    'temperature_2m_min' => [1.5],
                ],
            ]),
        ]);

        $result = app(WeatherService::class)->fetchForDate(35.6812, 139.7671, '2024-01-15');

        $this->assertSame('雨', $result['weather']);
        $this->assertSame(8.2, $result['temperature_max']);
        $this->assertSame(1.5, $result['temperature_min']);
        $this->assertNull($result['precipitation_probability']);
    }

    public function test_returns_null_on_connection_failure(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection failed');
        });

        $result = app(WeatherService::class)->fetchForDate(35.6812, 139.7671, '2026-08-30');

        $this->assertNull($result);
    }
}

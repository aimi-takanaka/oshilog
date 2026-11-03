<?php

namespace Tests\Feature;

use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_geocodes_japanese_address(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '35.6938397',
                    'lon' => '139.7499097',
                ],
            ]),
        ]);

        $result = app(GeocodingService::class)->geocode('東京都千代田区北の丸公園2-3');

        $this->assertSame(35.6938397, $result['latitude']);
        $this->assertSame(139.7499097, $result['longitude']);
    }

    public function test_geocode_venue_uses_preset_without_http(): void
    {
        Http::fake();

        $result = app(GeocodingService::class)->geocodeVenue(
            '横浜アリーナ',
            '',
        );

        $this->assertSame(35.5122725, $result['latitude']);
        $this->assertSame(139.6201921, $result['longitude']);
        $this->assertSame('神奈川県横浜市港北区新横浜3-10', $result['address']);
        Http::assertNothingSent();
    }

    public function test_geocode_venue_prefers_venue_name(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => function ($request) {
                $query = $request->data()['q'] ?? '';

                if ($query === 'テストホール') {
                    return Http::response([
                        ['lat' => '35.5122725', 'lon' => '139.6201921'],
                    ]);
                }

                return Http::response([]);
            },
        ]);

        $result = app(GeocodingService::class)->geocodeVenue(
            'テストホール',
            '神奈川県横浜市西区新宿2-8-12',
        );

        $this->assertSame(35.5122725, $result['latitude']);
        $this->assertSame(139.6201921, $result['longitude']);
    }

    public function test_geocode_venue_falls_back_to_address(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => function ($request) {
                $query = $request->data()['q'] ?? '';

                if ($query === '東京都千代田区北の丸公園2-3') {
                    return Http::response([
                        ['lat' => '35.6938397', 'lon' => '139.7499097'],
                    ]);
                }

                return Http::response([]);
            },
        ]);

        $result = app(GeocodingService::class)->geocodeVenue(
            '存在しない会場名',
            '東京都千代田区北の丸公園2-3',
        );

        $this->assertSame(35.6938397, $result['latitude']);
        $this->assertSame(139.7499097, $result['longitude']);
    }

    public function test_geocode_venue_fills_address_from_map_coordinates(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, '/reverse')) {
                return Http::response([
                    'address' => [
                        'state' => '東京都',
                        'city_district' => '千代田区',
                        'neighbourhood' => '北の丸公園',
                        'house_number' => '2-3',
                    ],
                ]);
            }

            return Http::response([
                [
                    'lat' => '35.6938397',
                    'lon' => '139.7499097',
                ],
            ]);
        });

        $result = app(GeocodingService::class)->geocodeVenue('日本武道館テスト', '');

        $this->assertSame(35.6938397, $result['latitude']);
        $this->assertSame(139.7499097, $result['longitude']);
        $this->assertSame('東京都千代田区北の丸公園2-3', $result['address']);
    }

    public function test_reverse_geocode_formats_a_japanese_address(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'address' => [
                    'state' => '神奈川県',
                    'city' => '横浜市',
                    'suburb' => '港北区',
                    'neighbourhood' => '新横浜',
                    'house_number' => '3-10',
                ],
            ]),
        ]);

        $result = app(GeocodingService::class)->reverseGeocode(35.5122725, 139.6201921);

        $this->assertSame('神奈川県横浜市港北区新横浜3-10', $result);
    }

    public function test_returns_null_for_empty_address(): void
    {
        Http::fake();

        $result = app(GeocodingService::class)->geocode('   ');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_returns_null_when_no_results(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $result = app(GeocodingService::class)->geocode('存在しない住所12345');

        $this->assertNull($result);
    }

    public function test_returns_null_on_connection_failure(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 60: SSL peer certificate or SSH remote key was not OK');
        });

        $result = app(GeocodingService::class)->geocode('東京都千代田区');

        $this->assertNull($result);
    }
}

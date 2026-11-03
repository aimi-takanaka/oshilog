<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use App\Models\Spot;
use App\Models\User;
use App\Models\Venue;
use App\PhotoCategory;
use App\Services\GeocodingService;
use App\Support\EventFormReturn;
use App\Support\PresetVenues;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class VenueManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_venues_index_to_login(): void
    {
        $this->get(route('venues.index'))
            ->assertRedirect(route('login'));
    }

    public function test_can_create_venue(): void
    {
        $user = $this->signIn();

        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocodeVenue')->andReturn(null);
        });

        Volt::test('venues.form')
            ->set('name', '日本武道館')
            ->set('address', '東京都千代田区北の丸公園2-3')
            ->set('latitude', '35.6938')
            ->set('longitude', '139.7499')
            ->set('nearest_station', '九段下駅')
            ->set('access_note', '徒歩5分')
            ->set('entry_caution', '荷物制限あり')
            ->set('seat_view', '2階前方はステージ全体が見やすい')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('venues.show', Venue::query()->first()));

        $venue = Venue::query()->first();

        $this->assertNotNull($venue);
        $this->assertSame('日本武道館', $venue->name);
        $this->assertSame('荷物制限あり', $venue->entry_caution);
        $this->assertSame('2階前方はステージ全体が見やすい', $venue->seat_view);
        $this->assertSame($user->id, $venue->user_id);
    }

    public function test_creating_venue_from_event_form_returns_to_event_form(): void
    {
        $this->signIn();

        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocodeVenue')->andReturn(null);
        });

        EventFormReturn::remember(
            [
                'title' => '途中のライブ',
                'event_date' => '2026-05-01',
            ],
            'events.create',
        );

        Volt::test('venues.form')
            ->assertSet('returnUrl', route('events.create'))
            ->set('name', '日本武道館')
            ->set('address', '東京都千代田区北の丸公園2-3')
            ->set('latitude', '35.6938')
            ->set('longitude', '139.7499')
            ->set('nearest_station', '九段下駅')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('events.create'));

        $venue = Venue::query()->first();

        $this->assertNotNull($venue);
        $this->assertSame($venue->id, session(EventFormReturn::KEY)['venue_id']);
    }

    public function test_can_create_venue_without_typing_coordinates(): void
    {
        $this->signIn();

        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocodeVenue')->andReturn([
                'latitude' => 35.6938,
                'longitude' => 139.7499,
            ]);
        });

        Volt::test('venues.form')
            ->set('name', '日本武道館')
            ->set('address', '東京都千代田区北の丸公園2-3')
            ->set('nearest_station', '九段下駅')
            ->call('save')
            ->assertHasNoErrors();

        $venue = Venue::query()->first();

        $this->assertNotNull($venue);
        $this->assertEqualsWithDelta(35.6938, (float) $venue->latitude, 0.0001);
        $this->assertEqualsWithDelta(139.7499, (float) $venue->longitude, 0.0001);
    }

    public function test_venue_form_does_not_show_coordinate_inputs(): void
    {
        $this->signIn();

        $this->get(route('venues.create'))
            ->assertOk()
            ->assertDontSee('>緯度<', false)
            ->assertDontSee('>経度<', false);
    }

    public function test_guests_are_redirected_from_preset_venues_to_login(): void
    {
        $this->get(route('venues.presets'))
            ->assertRedirect(route('login'));
    }

    public function test_preset_list_shows_famous_venues_to_choose(): void
    {
        $this->signIn();

        $this->get(route('venues.presets'))
            ->assertOk()
            ->assertSee('定番会場')
            ->assertSee('横浜アリーナ')
            ->assertSee('東京ドーム')
            ->assertSee('Kanadevia Hall')
            ->assertSee('カナデビアホール')
            ->assertSee('東京都文京区後楽1-3-61 ミーツポート1F')
            ->assertSee('明治座')
            ->assertSee('東京都中央区日本橋浜町2丁目31番1号')
            ->assertSee('選択した会場を追加');
    }

    public function test_user_can_add_selected_preset_venues(): void
    {
        $user = $this->signIn();

        Volt::test('venues.presets')
            ->set('selected', ['横浜アリーナ', '東京ドーム'])
            ->call('addSelected')
            ->assertHasNoErrors()
            ->assertSet('selected', [])
            ->assertSet('status', '2件の定番会場を追加しました。');

        $this->assertSame(2, Venue::query()->where('user_id', $user->id)->count());
        $this->assertTrue(Venue::query()->where('user_id', $user->id)->where('name', '横浜アリーナ')->exists());
        $this->assertTrue(Venue::query()->where('user_id', $user->id)->where('name', '東京ドーム')->exists());
        $this->assertFalse(Venue::query()->where('user_id', $user->id)->where('name', '帝国劇場')->exists());

        $yokohama = Venue::query()->where('user_id', $user->id)->where('name', '横浜アリーナ')->first();

        $this->assertNotNull($yokohama);
        $this->assertSame('神奈川県横浜市港北区新横浜3-10', $yokohama->address);
        $this->assertEqualsWithDelta(35.5122725, (float) $yokohama->latitude, 0.0001);
        $this->assertEqualsWithDelta(139.6201921, (float) $yokohama->longitude, 0.0001);

        $this->get(route('venues.index'))
            ->assertOk()
            ->assertSee('横浜アリーナ')
            ->assertSee('東京ドーム')
            ->assertDontSee('帝国劇場');

        $this->get(route('venues.presets'))
            ->assertOk()
            ->assertSee('追加済み');
    }

    public function test_preset_add_requires_a_selection(): void
    {
        $this->signIn();

        Volt::test('venues.presets')
            ->set('selected', [])
            ->call('addSelected')
            ->assertHasErrors(['selected']);
    }

    public function test_venues_index_links_to_preset_list(): void
    {
        $this->signIn();

        $this->get(route('venues.index'))
            ->assertOk()
            ->assertSee('定番会場から選ぶ')
            ->assertDontSee('定番をまとめて追加');
    }

    public function test_preset_aliases_are_treated_as_the_same_venue(): void
    {
        $user = $this->signIn();

        $english = PresetVenues::findByName('Kanadevia Hall');
        $japanese = PresetVenues::findByName('カナデビアホール');

        $this->assertNotNull($english);
        $this->assertNotNull($japanese);
        $this->assertSame('Kanadevia Hall', $english['name']);
        $this->assertSame($english['name'], $japanese['name']);
        $this->assertSame($english['address'], $japanese['address']);

        $this->assertTrue(PresetVenues::addForUser($user->id, 'カナデビアホール'));
        $this->assertFalse(PresetVenues::addForUser($user->id, 'Kanadevia Hall'));
        $this->assertSame(1, Venue::query()->where('user_id', $user->id)->count());
        $this->assertTrue(Venue::query()->where('user_id', $user->id)->where('name', 'Kanadevia Hall')->exists());
    }

    public function test_venue_form_fills_address_from_japanese_alias(): void
    {
        $this->signIn();

        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocodeVenue')
                ->once()
                ->with('カナデビアホール', '')
                ->andReturn([
                    'latitude' => 35.7044,
                    'longitude' => 139.7536,
                ]);
        });

        Volt::test('venues.form')
            ->set('name', 'カナデビアホール')
            ->assertSet('address', '東京都文京区後楽1-3-61 ミーツポート1F')
            ->assertSet('nearest_station', '水道橋駅')
            ->assertSet('latitude', '35.7044')
            ->assertSet('longitude', '139.7536');
    }

    public function test_venue_form_fills_address_from_map_when_name_is_unknown(): void
    {
        $this->signIn();

        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocodeVenue')
                ->once()
                ->with('テストライブハウス', '')
                ->andReturn([
                    'latitude' => 35.6611,
                    'longitude' => 139.6982,
                    'address' => '東京都渋谷区道玄坂2-3',
                ]);
        });

        Volt::test('venues.form')
            ->set('name', 'テストライブハウス')
            ->assertSet('address', '東京都渋谷区道玄坂2-3')
            ->assertSet('latitude', '35.6611')
            ->assertSet('longitude', '139.6982');
    }

    public function test_venue_form_fills_address_from_preset_name(): void
    {
        $this->signIn();

        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocodeVenue')
                ->once()
                ->with('横浜アリーナ', '')
                ->andReturn([
                    'latitude' => 35.5122725,
                    'longitude' => 139.6201921,
                ]);
        });

        Volt::test('venues.form')
            ->set('name', '横浜アリーナ')
            ->assertSet('address', '神奈川県横浜市港北区新横浜3-10')
            ->assertSet('nearest_station', '新横浜駅')
            ->assertSet('latitude', '35.5122725')
            ->assertSet('longitude', '139.6201921');
    }

    public function test_venue_geocoding_updates_coordinates_from_name(): void
    {
        $this->signIn();

        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocodeVenue')
                ->once()
                ->with('横浜アリーナ', '')
                ->andReturn([
                    'latitude' => 35.5122725,
                    'longitude' => 139.6201921,
                ]);
        });

        Volt::test('venues.form')
            ->set('name', '横浜アリーナ')
            ->assertSet('latitude', '35.5122725')
            ->assertSet('longitude', '139.6201921')
            ->assertSet('geocodeError', '');
    }

    public function test_venue_geocoding_updates_coordinates_from_address(): void
    {
        $this->signIn();

        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocodeVenue')
                ->once()
                ->with('', '東京都千代田区北の丸公園2-3')
                ->andReturn([
                    'latitude' => 35.6938397,
                    'longitude' => 139.7499097,
                ]);
        });

        Volt::test('venues.form')
            ->set('address', '東京都千代田区北の丸公園2-3')
            ->assertSet('latitude', '35.6938397')
            ->assertSet('longitude', '139.7499097')
            ->assertSet('geocodeError', '');
    }

    public function test_venue_geocoding_shows_error_when_not_found(): void
    {
        $this->signIn();

        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocodeVenue')
                ->once()
                ->with('', '存在しない住所')
                ->andReturn(null);
        });

        Volt::test('venues.form')
            ->set('address', '存在しない住所')
            ->assertSet('geocodeError', '会場名・住所から位置を特定できませんでした。正式名称か住所を確認してください。');
    }

    public function test_can_update_venue(): void
    {
        $this->signIn();

        $venue = Venue::factory()->create(['name' => '旧会場名']);

        Volt::test('venues.form', ['venue' => $venue])
            ->set('name', '新会場名')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('venues.show', $venue));

        $this->assertSame('新会場名', $venue->fresh()->name);
    }

    public function test_cannot_access_other_users_venue(): void
    {
        $this->signIn();
        $other = User::factory()->create();

        $venue = Venue::factory()->create(['user_id' => $other->id]);

        $this->get(route('venues.show', $venue))->assertNotFound();
    }

    public function test_venue_map_uses_custom_pin_image(): void
    {
        $this->signIn();

        $venue = Venue::factory()->create();

        $this->get(route('venues.show', $venue))
            ->assertOk()
            ->assertSee(asset('img/map/pin.svg'), false);
    }

    public function test_can_delete_venue(): void
    {
        $this->signIn();

        Storage::fake('public');

        $venue = Venue::factory()->create(['name' => '削除する会場']);
        $event = Event::factory()->for($venue)->create(['title' => '削除する公演']);
        $spot = Spot::factory()->for($venue)->create(['name' => '削除するスポット']);
        $path = "users/{$event->user_id}/events/{$event->id}/sample.jpg";

        Storage::disk('public')->put($path, 'fake-image');

        Photo::factory()->for($event)->create([
            'category' => PhotoCategory::Outfit,
            'image_path' => $path,
        ]);

        Volt::test('venues.show', ['venue' => $venue])
            ->call('deleteVenue')
            ->assertHasNoErrors()
            ->assertRedirect(route('venues.index'));

        $this->assertDatabaseMissing('venues', ['id' => $venue->id]);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        $this->assertDatabaseMissing('spots', ['id' => $spot->id]);
        $this->assertDatabaseMissing('photos', ['event_id' => $event->id]);
        Storage::disk('public')->assertMissing($path);
    }
}

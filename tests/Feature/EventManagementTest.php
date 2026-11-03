<?php

namespace Tests\Feature;

use App\EventTimeOfDay;
use App\EventType;
use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use App\Models\Venue;
use App\PhotoCategory;
use App\Services\WeatherService;
use App\Support\EventFormReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_events_index_to_login(): void
    {
        $this->get(route('events.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_events_index(): void
    {
        $this->signIn();

        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee('公演一覧')
            ->assertSee('ミュージカル')
            ->assertSee('イベント')
            ->assertSee('公演を記録')
            ->assertSee('会場を記録')
            ->assertSee('flex-col', false)
            ->assertSee('w-full sm:w-auto', false);
    }

    public function test_home_is_the_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('推し活の思い出を、まるごと記録。')
            ->assertSee('行った場所')
            ->assertSee('その日の天気')
            ->assertSee('洋服の記録')
            ->assertSee('ネイルの記録')
            ->assertSee('感想・メモ')
            ->assertSee('メンカラにチェンジ！')
            ->assertSee('使い方')
            ->assertSee('ログイン');
    }

    public function test_event_form_can_leave_for_venue_create_and_restore_draft(): void
    {
        $this->signIn();

        $this->mock(WeatherService::class, function ($mock): void {
            $mock->shouldReceive('fetchForDate')->andReturn(null);
        });

        Volt::test('events.form')
            ->set('title', '途中のライブ')
            ->set('event_type', EventType::Musical->value)
            ->set('event_date', '2026-05-01')
            ->set('time_of_day', EventTimeOfDay::Night->value)
            ->set('impression', '会場を追加してから記録する')
            ->set('companionNames', ['ゆい'])
            ->call('goToVenueCreate')
            ->assertRedirect(route('venues.create'));

        $this->assertSame('途中のライブ', session(EventFormReturn::KEY)['draft']['title']);

        $venue = Venue::factory()->create();

        EventFormReturn::attachVenue($venue->id);

        Volt::test('events.form')
            ->assertSet('title', '途中のライブ')
            ->assertSet('event_type', EventType::Musical->value)
            ->assertSet('event_date', '2026-05-01')
            ->assertSet('time_of_day', EventTimeOfDay::Night->value)
            ->assertSet('impression', '会場を追加してから記録する')
            ->assertSet('companionNames', ['ゆい'])
            ->assertSet('venue_id', (string) $venue->id);

        $this->assertNull(session(EventFormReturn::KEY));
    }

    public function test_can_create_event_with_companions(): void
    {
        $this->signIn();

        $this->mock(WeatherService::class, function ($mock): void {
            $mock->shouldReceive('fetchForDate')->andReturn(null);
        });

        $venue = Venue::factory()->create();

        Volt::test('events.form')
            ->set('title', '推し活ライブ')
            ->set('event_type', EventType::Live->value)
            ->set('event_date', '2026-04-01')
            ->set('time_of_day', EventTimeOfDay::Night->value)
            ->set('venue_id', (string) $venue->id)
            ->set('impression', '最高でした')
            ->set('companionNames', ['ゆい', 'あい'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('events.show', Event::query()->first()));

        $event = Event::query()->first();

        $this->assertNotNull($event);
        $this->assertSame('推し活ライブ', $event->title);
        $this->assertSame(EventType::Live, $event->event_type);
        $this->assertSame(EventTimeOfDay::Night, $event->time_of_day);
        $this->assertSame(auth()->id(), $event->user_id);
        $this->assertCount(2, $event->companions);
    }

    public function test_weather_auto_fill_updates_form_fields(): void
    {
        $this->signIn();

        $venue = Venue::factory()->create([
            'latitude' => 35.6812,
            'longitude' => 139.7671,
        ]);

        $this->mock(WeatherService::class, function ($mock): void {
            $mock->shouldReceive('fetchForDate')
                ->once()
                ->with(35.6812, 139.7671, '2026-08-30')
                ->andReturn([
                    'weather' => '晴れ',
                    'temperature_max' => 28.5,
                    'temperature_min' => 22.1,
                    'precipitation_probability' => 10,
                ]);
        });

        Volt::test('events.form')
            ->set('event_date', '2026-08-30')
            ->set('venue_id', (string) $venue->id)
            ->assertSet('weather', '晴れ')
            ->assertSet('temperature_max', '28.5')
            ->assertSet('temperature_min', '22.1')
            ->assertSet('precipitation_probability', '10');
    }

    public function test_can_save_manual_weather_fields(): void
    {
        $this->signIn();

        $this->mock(WeatherService::class, function ($mock): void {
            $mock->shouldReceive('fetchForDate')->andReturn(null);
        });

        $venue = Venue::factory()->create();

        Volt::test('events.form')
            ->set('title', '手入力天気テスト')
            ->set('event_type', EventType::Live->value)
            ->set('event_date', '2024-01-15')
            ->set('time_of_day', EventTimeOfDay::Day->value)
            ->set('venue_id', (string) $venue->id)
            ->set('weather', 'くもり')
            ->set('temperature_max', '12.5')
            ->set('temperature_min', '4.0')
            ->set('precipitation_probability', '30')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('events.show', Event::query()->first()));

        $event = Event::query()->first();

        $this->assertSame('くもり', $event->weather);
        $this->assertSame(12.5, (float) $event->temperature_max);
        $this->assertSame(4.0, (float) $event->temperature_min);
        $this->assertSame(30, $event->precipitation_probability);
    }

    public function test_events_index_filters_by_type_and_venue(): void
    {
        $this->signIn();

        $venueA = Venue::factory()->create(['name' => '会場A']);
        $venueB = Venue::factory()->create(['name' => '会場B']);

        Event::factory()->for($venueA)->live()->create(['title' => 'ライブA']);
        Event::factory()->for($venueB)->stage()->create(['title' => '舞台B']);

        Volt::test('events.index')
            ->set('eventType', EventType::Live->value)
            ->assertSee('ライブA')
            ->assertDontSee('舞台B');

        Volt::test('events.index')
            ->set('venueId', (string) $venueB->id)
            ->assertSee('舞台B')
            ->assertDontSee('ライブA');
    }

    public function test_event_show_venue_tab_renders_map(): void
    {
        $this->signIn();

        $venue = Venue::factory()->create(['name' => '明治座']);
        $event = Event::factory()->for($venue)->create();

        Volt::test('events.show', ['event' => $event])
            ->assertDontSee('event-venue-map-'.$event->id, false)
            ->set('activeTab', 'venue')
            ->assertSee('event-venue-map-'.$event->id, false)
            ->assertSee('data-venue-map', false)
            ->assertSee('明治座');
    }

    public function test_cannot_access_other_users_event(): void
    {
        $this->signIn();
        $other = User::factory()->create();

        $venue = Venue::factory()->create(['user_id' => $other->id]);
        $event = Event::factory()->for($venue)->create(['user_id' => $other->id]);

        $this->get(route('events.show', $event))->assertNotFound();
    }

    public function test_can_delete_event(): void
    {
        $this->signIn();

        Storage::fake('public');

        $event = Event::factory()->for(Venue::factory())->create(['title' => '削除テスト公演']);
        $path = "users/{$event->user_id}/events/{$event->id}/sample.jpg";

        Storage::disk('public')->put($path, 'fake-image');

        Photo::factory()->for($event)->create([
            'category' => PhotoCategory::Outfit,
            'image_path' => $path,
        ]);

        Volt::test('events.show', ['event' => $event])
            ->call('deleteEvent')
            ->assertHasNoErrors()
            ->assertRedirect(route('events.index'));

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        $this->assertDatabaseMissing('photos', ['event_id' => $event->id]);
        Storage::disk('public')->assertMissing($path);
    }
}

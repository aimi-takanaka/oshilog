<?php

namespace Tests\Feature;

use App\EventTimeOfDay;
use App\EventType;
use App\EventVisibility;
use App\Models\Belonging;
use App\Models\Companion;
use App\Models\Event;
use App\Models\Photo;
use App\Models\Spot;
use App\Models\User;
use App\Models\Venue;
use App\PhotoCategory;
use App\SpotCategory;
use App\SpotStatus;
use App\Support\PresetVenues;
use Database\Seeders\OshiLogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OshiLogMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_oshilog_tables(): void
    {
        $this->assertTrue(Schema::hasTable('venues'));
        $this->assertTrue(Schema::hasColumn('venues', 'seat_view'));
        $this->assertTrue(Schema::hasTable('events'));
        $this->assertTrue(Schema::hasTable('companions'));
        $this->assertTrue(Schema::hasTable('event_companion'));
        $this->assertTrue(Schema::hasTable('spots'));
        $this->assertTrue(Schema::hasTable('photos'));
        $this->assertTrue(Schema::hasTable('belongings'));
    }

    public function test_persists_events_with_related_records(): void
    {
        $venue = Venue::factory()->create();
        $companions = Companion::factory()->count(2)->create();

        $event = Event::factory()
            ->for($venue)
            ->live()
            ->create([
                'title' => 'テスト公演',
                'event_date' => '2026-05-01',
                'time_of_day' => EventTimeOfDay::Day,
            ]);

        $event->companions()->attach($companions->pluck('id'));

        Photo::factory()->for($event)->create([
            'category' => PhotoCategory::Uchiwa,
            'image_path' => 'users/1/events/'.$event->id.'/uchiwa.jpg',
        ]);

        Spot::factory()->for($venue)->wishlist()->create([
            'category' => SpotCategory::Cafe,
            'name' => '遠征前カフェ',
        ]);

        $event->refresh()->load(['venue.spots', 'companions', 'photos']);

        $this->assertSame('テスト公演', $event->title);
        $this->assertSame(EventType::Live, $event->event_type);
        $this->assertSame(EventTimeOfDay::Day, $event->time_of_day);
        $this->assertSame(EventVisibility::Private, $event->visibility);
        $this->assertSame($venue->user_id, $event->user_id);
        $this->assertNotEmpty($event->venue->name);
        $this->assertCount(2, $event->companions);
        $this->assertCount(1, $event->photos);
        $this->assertCount(1, $event->venue->spots);
        $this->assertSame(SpotStatus::Wishlist, $event->venue->spots->first()->status);
    }

    public function test_seeds_demo_data(): void
    {
        $this->seed(OshiLogSeeder::class);

        $this->assertSame(count(PresetVenues::all()), Venue::count());
        $this->assertSame(2, Event::count());
        $this->assertSame(2, Companion::count());
        $this->assertSame(2, Spot::count());
        $this->assertSame(1, Photo::count());
        $this->assertSame(4, Belonging::count());
    }

    public function test_seeder_can_run_multiple_times_without_error(): void
    {
        $this->seed(OshiLogSeeder::class);
        $this->seed(OshiLogSeeder::class);

        $this->assertSame(1, User::query()->whereKey(config('oshilog.default_user_id'))->count());
        $this->assertSame(count(PresetVenues::all()), Venue::count());
        $this->assertSame(2, Event::count());
        $this->assertSame(4, Belonging::count());
    }
}

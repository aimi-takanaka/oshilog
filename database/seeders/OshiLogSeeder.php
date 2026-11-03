<?php

namespace Database\Seeders;

use App\EventTimeOfDay;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OshiLogSeeder extends Seeder
{
    /**
     * Seed demo data for phase-1 development.
     */
    public function run(): void
    {
        $userId = config('oshilog.default_user_id');

        User::query()->updateOrCreate(
            ['id' => $userId],
            [
                'name' => '推し活ユーザー',
                'email' => 'oshi@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        if (! Belonging::query()->forCurrentUser($userId)->exists()) {
            Belonging::factory()->live()->create(['user_id' => $userId, 'name' => 'ペンライト', 'memo' => '予備電池も入れる']);
            Belonging::factory()->live()->create(['user_id' => $userId, 'name' => 'うちわ']);
            Belonging::factory()->stage()->create(['user_id' => $userId, 'name' => 'パンフレット代']);
            Belonging::factory()->filmingLocation()->create(['user_id' => $userId, 'name' => 'カメラ', 'memo' => '予備バッテリー']);
        }

        PresetVenues::seedForUser($userId);

        if (Event::query()->forCurrentUser($userId)->exists()) {
            return;
        }

        $venue = Venue::query()->forCurrentUser($userId)->where('name', '東京ドーム')->firstOrFail();
        $venue->update([
            'access_note' => 'JR水道橋駅西口から徒歩5分',
            'entry_caution' => '大きな荷物は事前にコインロッカーを確認',
        ]);

        Spot::factory()->for($venue)->create([
            'user_id' => $userId,
            'name' => 'スターバックス 水道橋店',
            'category' => SpotCategory::Cafe,
            'status' => SpotStatus::Visited,
            'memo' => '開演前にゆっくりできる',
        ]);

        Spot::factory()->for($venue)->wishlist()->create([
            'user_id' => $userId,
            'name' => '東京ドームシティ',
            'category' => SpotCategory::Sightseeing,
            'memo' => '次回はアトラクションも回りたい',
        ]);

        $companions = Companion::factory()->count(2)->create(['user_id' => $userId]);

        $event = Event::factory()->for($venue)->live()->create([
            'user_id' => $userId,
            'title' => '推し活ライブ 2026',
            'event_date' => '2026-03-15',
            'time_of_day' => EventTimeOfDay::Night,
            'impression' => '最高の一日だった！',
        ]);

        $event->companions()->attach($companions->pluck('id'));

        Photo::factory()->for($event)->create([
            'category' => PhotoCategory::Outfit,
            'image_path' => "users/{$userId}/events/{$event->id}/outfit-sample.jpg",
            'caption' => '参戦服',
        ]);

        Event::factory()->for($venue)->stage()->create([
            'user_id' => $userId,
            'title' => '春の舞台公演',
            'event_date' => '2026-01-20',
            'time_of_day' => EventTimeOfDay::Day,
        ]);
    }
}

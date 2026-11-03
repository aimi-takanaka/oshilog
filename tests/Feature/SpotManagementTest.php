<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Venue;
use App\SpotCategory;
use App\SpotStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SpotManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_spot_to_venue(): void
    {
        $this->signIn();

        $venue = Venue::factory()->create();

        Volt::test('spots.manager', ['venue' => $venue])
            ->set('newName', '遠征カフェ')
            ->set('newCategory', SpotCategory::Cafe->value)
            ->set('newStatus', SpotStatus::Wishlist->value)
            ->set('newMemo', '開演前に寄りたい')
            ->call('addSpot')
            ->assertHasNoErrors();

        $spot = Spot::query()->first();

        $this->assertNotNull($spot);
        $this->assertSame('遠征カフェ', $spot->name);
        $this->assertSame(SpotStatus::Wishlist, $spot->status);
    }

    public function test_can_mark_wishlist_spot_as_visited_with_memo(): void
    {
        $this->signIn();

        $venue = Venue::factory()->create();
        $spot = Spot::factory()->for($venue)->wishlist()->create([
            'name' => 'お土産屋',
            'memo' => '遠征前メモ',
        ]);

        Volt::test('spots.manager', ['venue' => $venue])
            ->call('openVisitModal', $spot->id)
            ->set('visitMemo', 'お土産買えた')
            ->call('markVisited')
            ->assertHasNoErrors();

        $spot->refresh();

        $this->assertSame(SpotStatus::Visited, $spot->status);
        $this->assertStringContainsString('遠征前メモ', $spot->memo);
        $this->assertStringContainsString('お土産買えた', $spot->memo);
    }

    public function test_can_delete_spot(): void
    {
        $this->signIn();

        $venue = Venue::factory()->create();
        $spot = Spot::factory()->for($venue)->create();

        Volt::test('spots.manager', ['venue' => $venue])
            ->call('deleteSpot', $spot->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('spots', ['id' => $spot->id]);
    }
}

<?php

namespace Tests\Feature;

use App\EventType;
use App\Models\Belonging;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BelongingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_belongings_index_to_login(): void
    {
        $this->get(route('belongings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_can_add_belonging_for_each_event_type(): void
    {
        $user = $this->signIn();

        foreach ([
            EventType::Live->value => 'ペンライト',
            EventType::Stage->value => 'パンフレット代',
            EventType::Musical->value => 'プログラム',
            EventType::Event->value => '特典',
            EventType::FilmingLocation->value => 'カメラ',
        ] as $eventType => $name) {
            Volt::test('belongings.index')
                ->set('eventType', $eventType)
                ->set('newName', $name)
                ->set('newMemo', '忘れないように')
                ->call('addItem')
                ->assertHasNoErrors();
        }

        $this->assertSame(5, Belonging::query()->count());
        $this->assertSame('ペンライト', Belonging::query()->where('event_type', EventType::Live)->value('name'));
        $this->assertSame('パンフレット代', Belonging::query()->where('event_type', EventType::Stage)->value('name'));
        $this->assertSame('プログラム', Belonging::query()->where('event_type', EventType::Musical)->value('name'));
        $this->assertSame('特典', Belonging::query()->where('event_type', EventType::Event)->value('name'));
        $this->assertSame('カメラ', Belonging::query()->where('event_type', EventType::FilmingLocation)->value('name'));
        $this->assertSame($user->id, Belonging::query()->first()?->user_id);
    }

    public function test_can_toggle_and_reset_packed_status(): void
    {
        $this->signIn();

        $item = Belonging::factory()->live()->create(['name' => 'うちわ']);

        Volt::test('belongings.index')
            ->set('eventType', EventType::Live->value)
            ->call('togglePacked', $item->id)
            ->assertHasNoErrors();

        $this->assertTrue($item->fresh()->is_packed);

        Volt::test('belongings.index')
            ->set('eventType', EventType::Live->value)
            ->call('resetPacked')
            ->assertHasNoErrors();

        $this->assertFalse($item->fresh()->is_packed);
    }

    public function test_can_delete_belonging(): void
    {
        $this->signIn();

        $item = Belonging::factory()->stage()->create();

        Volt::test('belongings.index')
            ->set('eventType', EventType::Stage->value)
            ->call('deleteItem', $item->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('belongings', ['id' => $item->id]);
    }

    public function test_cannot_modify_other_users_belonging(): void
    {
        $this->signIn();
        $other = User::factory()->create();

        $item = Belonging::factory()->live()->create([
            'user_id' => $other->id,
            'name' => '他人の荷物',
        ]);

        Volt::test('belongings.index')
            ->set('eventType', EventType::Live->value)
            ->assertDontSee('他人の荷物');

        try {
            Volt::test('belongings.index')
                ->set('eventType', EventType::Live->value)
                ->call('deleteItem', $item->id);
            $this->fail('Expected ModelNotFoundException was not thrown.');
        } catch (ModelNotFoundException) {
            // expected
        }

        $this->assertDatabaseHas('belongings', ['id' => $item->id]);
    }
}

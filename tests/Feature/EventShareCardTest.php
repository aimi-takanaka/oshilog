<?php

namespace Tests\Feature;

use App\EventTimeOfDay;
use App\EventType;
use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use App\Models\Venue;
use App\Support\ShareCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class EventShareCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_share_card(): void
    {
        $event = Event::factory()->create();

        $this->get(route('events.card', $event))
            ->assertRedirect(route('login'));
    }

    public function test_event_show_links_to_share_card(): void
    {
        $this->signIn();

        $event = Event::factory()->create(['title' => 'シェアする公演']);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('投稿カード')
            ->assertSee(route('events.card', $event), false);
    }

    public function test_share_card_shows_basic_event_info(): void
    {
        $this->signIn();

        $venue = Venue::factory()->create(['name' => '明治座']);
        $event = Event::factory()->for($venue)->create([
            'title' => '春の舞台',
            'event_type' => EventType::Musical,
            'event_date' => '2026-04-01',
            'time_of_day' => EventTimeOfDay::Night,
            'impression' => '幕が上がった瞬間から最高だった',
        ]);

        $this->get(route('events.card', $event))
            ->assertOk()
            ->assertSee('春の舞台')
            ->assertSee('2026年4月1日')
            ->assertSee('明治座')
            ->assertSee('幕が上がった瞬間から最高だった')
            ->assertSee('スクラップブック')
            ->assertSee('チケット')
            ->assertSee('メンカラ')
            ->assertSee('setOshiLogMemberColor')
            ->assertSeeInOrder([
                'data-member-color-swatch="blue"',
                'data-member-color-swatch="purple"',
                'data-member-color-swatch="pink"',
                'data-member-color-swatch="green"',
                'data-member-color-swatch="red"',
                'data-member-color-swatch="yellow"',
                'data-member-color-swatch="orange"',
                'data-member-color-swatch="white"',
            ])
            ->assertSee('背景')
            ->assertSee('クリーム')
            ->assertSee('ドット')
            ->assertSee('ストライプ')
            ->assertSee('share-frame-ink', false)
            ->assertSee('share-impression-status', false)
            ->assertSee('share-impression-input-count', false)
            ->assertSee('およそ'.ShareCard::impressionLimit(ShareCard::STYLE_SCRAPBOOK).'文字まで')
            ->assertSee('公演に保存した感想は変わりません');
    }

    public function test_share_card_defaults_to_scrapbook_with_up_to_four_photos(): void
    {
        $this->signIn();

        $event = Event::factory()->create();
        $photos = Photo::factory()->count(6)->for($event)->create();

        Volt::test('events.card', ['event' => $event->load('photos')])
            ->assertSet('cardStyle', ShareCard::STYLE_SCRAPBOOK)
            ->assertSet('cardBackground', ShareCard::BACKGROUND_CREAM)
            ->assertSet('scrapbookPhotoIds', [
                (string) $photos[0]->id,
                (string) $photos[1]->id,
                (string) $photos[2]->id,
                (string) $photos[3]->id,
            ])
            ->assertSet('featuredPhotoId', (string) $photos[0]->id);
    }

    public function test_share_card_scrapbook_photos_are_capped_at_four(): void
    {
        $this->signIn();

        $event = Event::factory()->create();
        $photos = Photo::factory()->count(6)->for($event)->create();

        Volt::test('events.card', ['event' => $event->load('photos')])
            ->call('toggleScrapbookPhoto', (string) $photos[0]->id)
            ->call('toggleScrapbookPhoto', (string) $photos[4]->id)
            ->assertSet('scrapbookPhotoIds', [
                (string) $photos[1]->id,
                (string) $photos[2]->id,
                (string) $photos[3]->id,
                (string) $photos[4]->id,
            ]);
    }

    public function test_share_card_can_switch_to_ticket_style(): void
    {
        $this->signIn();

        $event = Event::factory()->create();
        $photos = Photo::factory()->count(2)->for($event)->create();

        Volt::test('events.card', ['event' => $event->load('photos')])
            ->call('selectStyle', ShareCard::STYLE_TICKET)
            ->assertSet('cardStyle', ShareCard::STYLE_TICKET)
            ->assertSee('チケットの裏面はおよそ'.ShareCard::impressionLimit(ShareCard::STYLE_TICKET).'文字まで')
            ->assertSee('OshiLog')
            ->assertSee('IMPRESSION')
            ->assertDontSee('OshiLog 表面')
            ->assertDontSee('裏面 OshiLog')
            ->call('selectTicketPhoto', (string) $photos[1]->id)
            ->assertSet('featuredPhotoId', (string) $photos[1]->id);
    }

    public function test_share_card_can_change_background(): void
    {
        $this->signIn();

        $event = Event::factory()->create();

        Volt::test('events.card', ['event' => $event])
            ->assertSee('share-frame-ink', false)
            ->call('selectBackground', ShareCard::BACKGROUND_DOTS)
            ->assertSet('cardBackground', ShareCard::BACKGROUND_DOTS)
            ->assertSee('share-bg-dots', false)
            ->call('selectBackground', 'invalid')
            ->assertSet('cardBackground', ShareCard::BACKGROUND_DOTS);
    }

    public function test_share_card_impression_can_be_edited_without_changing_event(): void
    {
        $this->signIn();

        $event = Event::factory()->create([
            'impression' => '公演に残した長い感想です',
        ]);

        Volt::test('events.card', ['event' => $event])
            ->assertSet('cardImpression', '公演に残した長い感想です')
            ->set('cardImpression', 'カード用の短い文')
            ->assertSet('cardImpression', 'カード用の短い文')
            ->call('restoreImpression')
            ->assertSet('cardImpression', '公演に残した長い感想です');

        $this->assertSame('公演に残した長い感想です', $event->fresh()->impression);
    }

    public function test_share_card_warns_when_impression_exceeds_style_limit(): void
    {
        $this->signIn();

        $event = Event::factory()->create(['impression' => '短い']);

        $overLimit = str_repeat('あ', ShareCard::impressionLimit(ShareCard::STYLE_SCRAPBOOK) + 1);

        Volt::test('events.card', ['event' => $event])
            ->set('cardImpression', $overLimit)
            ->assertSee('※文字数オーバーです')
            ->assertSee('share-impression-over', false)
            ->assertSee((ShareCard::impressionLimit(ShareCard::STYLE_SCRAPBOOK) + 1).' / '.ShareCard::impressionLimit(ShareCard::STYLE_SCRAPBOOK).'文字');
    }

    public function test_cannot_open_other_users_share_card(): void
    {
        $this->signIn();
        $other = User::factory()->create();

        $venue = Venue::factory()->create(['user_id' => $other->id]);
        $event = Event::factory()->for($venue)->create(['user_id' => $other->id]);

        $this->get(route('events.card', $event))->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use App\Models\Venue;
use App\PhotoCategory;
use App\Services\PhotoStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_photo_for_event(): void
    {
        $this->signIn();

        Storage::fake('public');

        $event = Event::factory()->for(Venue::factory())->create();
        $file = UploadedFile::fake()->create('outfit.jpg', 100, 'image/jpeg');

        $component = Volt::test('events.show', ['event' => $event])
            ->set('uploadCategory', PhotoCategory::Outfit->value)
            ->set('uploadCaption', '参戦服')
            ->set('uploads', [$file])
            ->assertHasNoErrors();

        $this->assertDatabaseCount('photos', 0);

        $component
            ->call('addPhotos')
            ->assertHasNoErrors();

        $photo = Photo::query()->first();

        $this->assertNotNull($photo);
        $this->assertSame(PhotoCategory::Outfit, $photo->category);
        $this->assertSame('参戦服', $photo->caption);
        $this->assertStringContainsString("users/{$event->user_id}/events/{$event->id}/", $photo->image_path);
        Storage::disk('public')->assertExists($photo->image_path);
    }

    public function test_selected_photos_show_a_preview(): void
    {
        $this->signIn();

        Storage::fake('public');

        $event = Event::factory()->for(Venue::factory())->create();
        $file = UploadedFile::fake()->create('outfit.jpg', 100, 'image/jpeg');

        Volt::test('events.show', ['event' => $event])
            ->set('activeTab', 'photos')
            ->set('uploads', [$file])
            ->assertSee('選択中（1枚）')
            ->assertSee('選択中の画像1');
    }

    public function test_can_update_photo_category_and_caption(): void
    {
        $this->signIn();

        Storage::fake('public');

        $event = Event::factory()->for(Venue::factory())->create();
        $photo = Photo::factory()->for($event)->create([
            'category' => PhotoCategory::Outfit,
            'caption' => '参戦服',
        ]);

        Volt::test('events.show', ['event' => $event])
            ->set('activeTab', 'photos')
            ->call('startEditPhoto', $photo->id)
            ->assertSet('editingPhotoId', $photo->id)
            ->assertSet('editCategory', PhotoCategory::Outfit->value)
            ->assertSet('editCaption', '参戦服')
            ->set('editCategory', PhotoCategory::Nail->value)
            ->set('editCaption', 'ネイルチェンジ')
            ->call('savePhoto')
            ->assertHasNoErrors()
            ->assertSet('editingPhotoId', null);

        $photo->refresh();

        $this->assertSame(PhotoCategory::Nail, $photo->category);
        $this->assertSame('ネイルチェンジ', $photo->caption);
    }

    public function test_cannot_upload_more_than_four_photos_per_category(): void
    {
        $this->signIn();

        Storage::fake('public');

        $event = Event::factory()->for(Venue::factory())->create();
        Photo::factory()->count(PhotoCategory::MAX_PHOTOS)->for($event)->create([
            'category' => PhotoCategory::Outfit,
        ]);

        $file = UploadedFile::fake()->create('extra.jpg', 100, 'image/jpeg');

        Volt::test('events.show', ['event' => $event->load('photos')])
            ->set('activeTab', 'photos')
            ->set('uploadCategory', PhotoCategory::Outfit->value)
            ->set('uploads', [$file])
            ->call('addPhotos')
            ->assertHasErrors(['uploads']);

        $this->assertSame(PhotoCategory::MAX_PHOTOS, Photo::query()->where('event_id', $event->id)->count());
    }

    public function test_cannot_move_photo_into_a_full_category(): void
    {
        $this->signIn();

        Storage::fake('public');

        $event = Event::factory()->for(Venue::factory())->create();
        Photo::factory()->count(PhotoCategory::MAX_PHOTOS)->for($event)->create([
            'category' => PhotoCategory::Nail,
        ]);
        $photo = Photo::factory()->for($event)->create([
            'category' => PhotoCategory::Outfit,
            'caption' => '服',
        ]);

        Volt::test('events.show', ['event' => $event->load('photos')])
            ->set('activeTab', 'photos')
            ->call('startEditPhoto', $photo->id)
            ->set('editCategory', PhotoCategory::Nail->value)
            ->call('savePhoto')
            ->assertHasErrors(['editCategory']);

        $this->assertSame(PhotoCategory::Outfit, $photo->fresh()->category);
    }

    public function test_can_delete_photo(): void
    {
        $this->signIn();

        Storage::fake('public');

        $event = Event::factory()->for(Venue::factory())->create();
        $path = app(PhotoStorageService::class)->store(
            UploadedFile::fake()->create('uchiwa.jpg', 100, 'image/jpeg'),
            $event->user_id,
            $event->id,
        );

        $photo = Photo::factory()->for($event)->create([
            'image_path' => $path,
            'category' => PhotoCategory::Uchiwa,
        ]);

        Storage::disk('public')->put($path, 'fake');

        Volt::test('events.show', ['event' => $event])
            ->call('deletePhoto', $photo->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_photo_url_includes_the_application_url(): void
    {
        Storage::fake('public');

        $event = Event::factory()->for(Venue::factory())->create();
        $path = "users/{$event->user_id}/events/{$event->id}/outfit.jpg";
        Storage::disk('public')->put($path, 'fake-image');

        $photo = Photo::factory()->for($event)->create([
            'image_path' => $path,
            'category' => PhotoCategory::Outfit,
        ]);

        $url = $photo->publicUrl();

        $this->assertNotNull($url);
        $this->assertStringContainsString('/storage/'.$path, $url);
        $this->assertStringStartsWith(rtrim((string) config('app.url'), '/'), $url);
    }
}

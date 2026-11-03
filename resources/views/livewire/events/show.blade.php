<?php

use App\Models\Event;
use App\Models\Photo;
use App\PhotoCategory;
use App\Services\PhotoStorageService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] #[Title('公演詳細')] class extends Component {
    use WithFileUploads;

    public Event $event;

    public string $activeTab = 'overview';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    public string $uploadCategory = 'outfit';

    public string $uploadCaption = '';

    public ?int $editingPhotoId = null;

    public string $editCategory = '';

    public string $editCaption = '';

    public function mount(Event $event): void
    {
        $this->event = $event->load(['venue.spots', 'companions', 'photos']);
    }

    public function addPhotos(): void
    {
        $this->validate([
            'uploads' => ['required', 'array', 'min:1'],
            'uploads.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'uploadCategory' => ['required', Rule::enum(PhotoCategory::class)],
        ]);

        $remaining = $this->remainingSlots($this->uploadCategory);

        if ($remaining <= 0) {
            $this->addError('uploads', $this->categoryLabel($this->uploadCategory).'は'.PhotoCategory::MAX_PHOTOS.'枚までです。');

            return;
        }

        if (count($this->uploads) > $remaining) {
            $this->addError('uploads', $this->categoryLabel($this->uploadCategory).'にあと'.$remaining.'枚まで追加できます。');

            return;
        }

        $service = app(PhotoStorageService::class);
        $userId = auth()->id();

        foreach ($this->uploads as $upload) {
            $path = $service->store($upload, $userId, $this->event->id);

            Photo::query()->create([
                'event_id' => $this->event->id,
                'category' => $this->uploadCategory,
                'image_path' => $path,
                'caption' => $this->uploadCaption !== '' ? $this->uploadCaption : null,
            ]);
        }

        $this->uploads = [];
        $this->uploadCaption = '';
        $this->event->load('photos');

        session()->flash('status', '画像をアップロードしました。');
    }

    public function startEditPhoto(int $photoId): void
    {
        $photo = $this->findEventPhoto($photoId);

        $this->editingPhotoId = $photo->id;
        $this->editCategory = $photo->category->value;
        $this->editCaption = $photo->caption ?? '';
        $this->resetErrorBag();
    }

    public function savePhoto(): void
    {
        $photo = $this->findEventPhoto((int) $this->editingPhotoId);

        $validated = $this->validate([
            'editCategory' => ['required', Rule::enum(PhotoCategory::class)],
            'editCaption' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['editCategory'] !== $photo->category->value
            && $this->remainingSlots($validated['editCategory']) <= 0) {
            $this->addError('editCategory', $this->categoryLabel($validated['editCategory']).'は'.PhotoCategory::MAX_PHOTOS.'枚までです。');

            return;
        }

        $photo->update([
            'category' => $validated['editCategory'],
            'caption' => filled($validated['editCaption'] ?? null) ? $validated['editCaption'] : null,
        ]);

        $this->cancelEditPhoto();
        $this->event->load('photos');

        session()->flash('status', '画像の項目を更新しました。');
    }

    public function cancelEditPhoto(): void
    {
        $this->editingPhotoId = null;
        $this->editCategory = '';
        $this->editCaption = '';
        $this->resetErrorBag(['editCategory', 'editCaption']);
    }

    public function deletePhoto(int $photoId): void
    {
        $photo = $this->findEventPhoto($photoId);

        app(PhotoStorageService::class)->delete($photo->image_path);
        $photo->delete();

        if ($this->editingPhotoId === $photoId) {
            $this->cancelEditPhoto();
        }

        $this->event->load('photos');
    }

    private function findEventPhoto(int $photoId): Photo
    {
        return Photo::query()
            ->where('event_id', $this->event->id)
            ->whereKey($photoId)
            ->firstOrFail();
    }

    private function remainingSlots(string $category, ?int $exceptPhotoId = null): int
    {
        $used = $this->event->photos
            ->filter(fn (Photo $photo): bool => $photo->category->value === $category && $photo->id !== $exceptPhotoId)
            ->count();

        return max(0, PhotoCategory::MAX_PHOTOS - $used);
    }

    private function categoryLabel(string $category): string
    {
        return PhotoCategory::tryFrom($category)?->label() ?? 'この項目';
    }

    public function deleteEvent(): void
    {
        $service = app(PhotoStorageService::class);

        foreach ($this->event->photos as $photo) {
            $service->delete($photo->image_path);
        }

        $this->event->delete();

        session()->flash('status', '公演を削除しました。');

        $this->redirectRoute('events.index', navigate: true);
    }

    public function with(): array
    {
        $photosByCategory = $this->event->photos->groupBy(fn (Photo $photo) => $photo->category->value);
        $uploadRemaining = $this->remainingSlots($this->uploadCategory);

        return [
            'photoCategories' => PhotoCategory::cases(),
            'photosByCategory' => $photosByCategory,
            'maxPhotosPerCategory' => PhotoCategory::MAX_PHOTOS,
            'uploadRemaining' => $uploadRemaining,
            'uploadPreviews' => collect($this->uploads)
                ->map(function ($upload): ?string {
                    try {
                        return $upload->isPreviewable() ? $upload->temporaryUrl() : null;
                    } catch (\Throwable) {
                        return null;
                    }
                })
                ->filter()
                ->values()
                ->all(),
        ];
    }
}; ?>

<div class="mx-auto w-full max-w-4xl p-6">
    <flux:button :href="route('events.index')" variant="ghost" size="sm" icon="arrow-left" wire:navigate class="mb-4">
        公演一覧へ
    </flux:button>

    <div class="mb-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
        <div class="min-w-0 w-full sm:flex-1">
            <flux:heading size="xl" class="break-words">{{ $event->title }}</flux:heading>
            <flux:text class="mt-1">
                {{ $event->event_date->format('Y年n月j日') }}（{{ $event->time_of_day->label() }}） · {{ $event->venue->name }} · {{ $event->event_type->label() }}
            </flux:text>
        </div>
        <div class="page-detail-actions flex w-full shrink-0 flex-nowrap gap-2 sm:w-auto sm:justify-end">
            <flux:button
                :href="route('events.card', $event)"
                variant="outline"
                size="sm"
                icon="share"
                wire:navigate
                class="min-w-0 flex-1 sm:flex-none"
                aria-label="投稿カード"
            >
                <span class="sm:hidden">カード</span>
                <span class="hidden sm:inline">投稿カード</span>
            </flux:button>
            <flux:button :href="route('events.edit', $event)" variant="outline" size="sm" icon="pencil" wire:navigate class="min-w-0 flex-1 sm:flex-none">編集</flux:button>
            <flux:button
                wire:click="deleteEvent"
                wire:confirm="この公演を削除しますか？画像も一緒に削除されます。"
                variant="danger"
                size="sm"
                icon="trash"
                class="min-w-0 flex-1 sm:flex-none"
            >削除</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout variant="success" class="mb-6">{{ session('status') }}</flux:callout>
    @endif

    <div class="mb-6 flex gap-2 border-b border-zinc-200 dark:border-zinc-700">
        <flux:button
            wire:click="$set('activeTab', 'overview')"
            :variant="$activeTab === 'overview' ? 'primary' : 'ghost'"
            size="sm"
        >概要</flux:button>
        <flux:button
            wire:click="$set('activeTab', 'photos')"
            :variant="$activeTab === 'photos' ? 'primary' : 'ghost'"
            size="sm"
        >画像</flux:button>
        <flux:button
            wire:click="$set('activeTab', 'venue')"
            :variant="$activeTab === 'venue' ? 'primary' : 'ghost'"
            size="sm"
        >会場</flux:button>
    </div>

    @if ($activeTab === 'overview')
        <div class="space-y-6">
            <flux:card class="p-4 space-y-4">
                <flux:heading size="lg">基本情報</flux:heading>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="font-medium">日付</flux:text>
                        <flux:text>{{ $event->event_date->format('Y年n月j日') }}（{{ $event->time_of_day->label() }}）</flux:text>
                    </div>
                    <div>
                        <flux:text class="font-medium">種別</flux:text>
                        <flux:text>{{ $event->event_type->label() }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="font-medium">昼夜</flux:text>
                        <flux:text>{{ $event->time_of_day->label() }}</flux:text>
                    </div>
                    <div class="sm:col-span-2">
                        <flux:text class="font-medium">会場</flux:text>
                        <flux:link :href="route('venues.show', $event->venue)" wire:navigate>{{ $event->venue->name }}</flux:link>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 space-y-4">
                <flux:heading size="lg">天気</flux:heading>
                @if ($event->weather || $event->temperature_max !== null || $event->temperature_min !== null || $event->precipitation_probability !== null)
                    <div class="grid gap-4 sm:grid-cols-2">
                        @if ($event->weather)
                            <div>
                                <flux:text class="font-medium">天気</flux:text>
                                <flux:text>{{ $event->weather }}</flux:text>
                            </div>
                        @endif
                        @if ($event->temperature_max !== null || $event->temperature_min !== null)
                            <div>
                                <flux:text class="font-medium">気温</flux:text>
                                <flux:text>
                                    @if ($event->temperature_min !== null && $event->temperature_max !== null)
                                        {{ number_format((float) $event->temperature_min, 1) }}℃ / {{ number_format((float) $event->temperature_max, 1) }}℃
                                    @elseif ($event->temperature_max !== null)
                                        最高 {{ number_format((float) $event->temperature_max, 1) }}℃
                                    @else
                                        最低 {{ number_format((float) $event->temperature_min, 1) }}℃
                                    @endif
                                </flux:text>
                            </div>
                        @endif
                        @if ($event->precipitation_probability !== null)
                            <div>
                                <flux:text class="font-medium">降水確率</flux:text>
                                <flux:text>{{ $event->precipitation_probability }}%</flux:text>
                            </div>
                        @endif
                    </div>
                @else
                    <flux:text>天気情報は記録されていません。</flux:text>
                @endif
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="lg" class="mb-3">同行者</flux:heading>
                @if ($event->companions->isEmpty())
                    <flux:text>同行者の記録はありません。</flux:text>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach ($event->companions as $companion)
                            <flux:badge>{{ $companion->name }}</flux:badge>
                        @endforeach
                    </div>
                @endif
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="lg" class="mb-3">メモ</flux:heading>
                @if ($event->impression)
                    <flux:text class="whitespace-pre-line">{{ $event->impression }}</flux:text>
                @else
                    <flux:text>感想はまだ書かれていません。</flux:text>
                @endif
            </flux:card>
        </div>
    @elseif ($activeTab === 'photos')
        <div class="space-y-6">
            <flux:card class="p-4">
                <flux:heading size="lg" class="mb-4">画像をアップロード</flux:heading>
                <div class="space-y-4">
                    <flux:select wire:model.live="uploadCategory" label="カテゴリ">
                        @foreach ($photoCategories as $category)
                            <flux:select.option :value="$category->value">{{ $category->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:text class="text-sm">各項目{{ $maxPhotosPerCategory }}枚までです。この項目はあと{{ $uploadRemaining }}枚追加できます。</flux:text>
                    <flux:input wire:model="uploadCaption" label="キャプション（任意）" />
                    <flux:input type="file" wire:model="uploads" label="画像ファイル" multiple accept="image/jpeg,image/png,image/webp" :disabled="$uploadRemaining === 0" />
                    <div wire:loading wire:target="uploads">
                        <flux:text class="text-sm">画像を読み込んでいます...</flux:text>
                    </div>
                    @if ($uploadPreviews !== [])
                        <div>
                            <flux:text class="mb-2 text-sm">選択中（{{ count($uploadPreviews) }}枚）</flux:text>
                            <div class="grid grid-cols-4 gap-2 sm:grid-cols-6">
                                @foreach ($uploadPreviews as $index => $previewUrl)
                                    <img
                                        wire:key="upload-preview-{{ $index }}"
                                        src="{{ $previewUrl }}"
                                        alt="選択中の画像{{ $index + 1 }}"
                                        class="upload-preview-thumb"
                                    />
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @error('uploads.*')
                        <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
                    @enderror
                    @error('uploads')
                        <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
                    @enderror
                    <flux:text class="text-sm">JPEG / PNG / WebP、1ファイル5MBまで</flux:text>
                    <flux:button
                        type="button"
                        variant="primary"
                        icon="plus"
                        wire:click="addPhotos"
                        wire:loading.attr="disabled"
                        :disabled="$uploadRemaining === 0"
                    >追加</flux:button>
                </div>
            </flux:card>

            @foreach ($photoCategories as $category)
                @php($photos = $photosByCategory->get($category->value, collect()))
                <flux:card class="p-4">
                    <flux:heading size="lg" class="mb-4">{{ $category->label() }}（{{ $photos->count() }} / {{ $maxPhotosPerCategory }}）</flux:heading>
                    @if ($photos->isEmpty())
                        <flux:text>画像はありません。</flux:text>
                    @else
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            @foreach ($photos as $photo)
                                <div class="space-y-2" wire:key="photo-{{ $photo->id }}">
                                    @if ($url = $photo->publicUrl())
                                        <x-photo-viewer
                                            :src="$url"
                                            :alt="$photo->caption ?? $category->label()"
                                            class="aspect-square w-full rounded-lg object-cover"
                                        />
                                    @else
                                        <div class="flex aspect-square items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-zinc-50 text-sm text-zinc-500 dark:border-zinc-600 dark:bg-zinc-900">
                                            画像なし
                                        </div>
                                    @endif
                                    @if ($editingPhotoId === $photo->id)
                                        <flux:select wire:model="editCategory" label="項目">
                                            @foreach ($photoCategories as $option)
                                                <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:input wire:model="editCaption" label="コメント（任意）" />
                                        <div class="flex flex-wrap gap-2">
                                            <flux:button type="button" variant="primary" size="sm" wire:click="savePhoto">保存</flux:button>
                                            <flux:button type="button" variant="ghost" size="sm" wire:click="cancelEditPhoto">キャンセル</flux:button>
                                        </div>
                                    @else
                                        @if ($photo->caption)
                                            <flux:text class="text-sm">{{ $photo->caption }}</flux:text>
                                        @endif
                                        <div class="flex flex-wrap gap-2">
                                            <flux:button type="button" variant="outline" size="sm" wire:click="startEditPhoto({{ $photo->id }})">編集</flux:button>
                                            <flux:button
                                                wire:click="deletePhoto({{ $photo->id }})"
                                                wire:confirm="この画像を削除しますか？"
                                                variant="danger"
                                                size="sm"
                                            >削除</flux:button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:card>
            @endforeach
        </div>
    @else
        <div class="space-y-6">
            <flux:card class="p-4">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">{{ $event->venue->name }}</flux:heading>
                    <flux:button :href="route('venues.edit', $event->venue)" variant="outline" size="sm" wire:navigate>会場を編集</flux:button>
                </div>
                <x-venue-map
                    :id="'event-venue-map-'.$event->id"
                    :latitude="$event->venue->latitude"
                    :longitude="$event->venue->longitude"
                    :name="$event->venue->name"
                />
            </flux:card>

            <flux:card class="p-4 space-y-4">
                <flux:heading size="lg">アクセス情報</flux:heading>
                <div>
                    <flux:text class="font-medium">住所</flux:text>
                    <flux:text>{{ $event->venue->address }}</flux:text>
                </div>
                <div>
                    <flux:text class="font-medium">最寄り駅</flux:text>
                    <flux:text>{{ $event->venue->nearest_station }}</flux:text>
                </div>
                @if ($event->venue->access_note)
                    <div>
                        <flux:text class="font-medium">アクセス方法</flux:text>
                        <flux:text class="whitespace-pre-line">{{ $event->venue->access_note }}</flux:text>
                    </div>
                @endif
                @if ($event->venue->entry_caution)
                    <div>
                        <flux:text class="font-medium">注意点</flux:text>
                        <flux:text class="whitespace-pre-line">{{ $event->venue->entry_caution }}</flux:text>
                    </div>
                @endif
                @if ($event->venue->seat_view)
                    <div>
                        <flux:text class="font-medium">座席の見え方</flux:text>
                        <flux:text class="whitespace-pre-line">{{ $event->venue->seat_view }}</flux:text>
                    </div>
                @endif
            </flux:card>

            <livewire:spots.manager :venue="$event->venue" :key="'event-spots-'.$event->id" />
        </div>
    @endif
</div>

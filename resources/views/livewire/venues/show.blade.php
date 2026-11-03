<?php

use App\Models\Venue;
use App\Services\PhotoStorageService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] #[Title('会場詳細')] class extends Component {
    public Venue $venue;

    public function mount(Venue $venue): void
    {
        $this->venue = $venue->load(['spots', 'events' => fn ($query) => $query->orderByDesc('event_date')]);
    }

    public function deleteVenue(): void
    {
        $service = app(PhotoStorageService::class);

        $this->venue->load('events.photos');

        foreach ($this->venue->events as $event) {
            foreach ($event->photos as $photo) {
                $service->delete($photo->image_path);
            }
        }

        $this->venue->delete();

        session()->flash('status', '会場を削除しました。');

        $this->redirectRoute('venues.index', navigate: true);
    }
}; ?>

<div class="mx-auto w-full max-w-4xl p-6">
    <flux:button :href="route('venues.index')" variant="ghost" size="sm" icon="arrow-left" wire:navigate class="mb-4">
        会場一覧へ
    </flux:button>

    <div class="mb-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
        <div class="min-w-0 w-full sm:flex-1">
            <flux:heading size="xl" class="break-words">{{ $venue->name }}</flux:heading>
            <flux:text class="mt-1 break-words">{{ $venue->address }}</flux:text>
        </div>
        <div class="page-detail-actions flex w-full shrink-0 flex-nowrap gap-2 sm:w-auto sm:justify-end">
            <flux:button :href="route('venues.edit', $venue)" variant="outline" size="sm" icon="pencil" wire:navigate class="min-w-0 flex-1 sm:flex-none">編集</flux:button>
            <flux:button
                wire:click="deleteVenue"
                wire:confirm="この会場を削除しますか？この会場の公演・スポット・画像も一緒に削除されます。"
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

    <div class="space-y-6">
        <flux:card class="p-4">
            <flux:heading size="lg" class="mb-4">地図</flux:heading>
            <x-venue-map
                :id="'venue-map-'.$venue->id"
                :latitude="$venue->latitude"
                :longitude="$venue->longitude"
                :name="$venue->name"
            />
        </flux:card>

        <flux:card class="p-4 space-y-4">
            <flux:heading size="lg">アクセス情報</flux:heading>
            <div>
                <flux:text class="font-medium">最寄り駅</flux:text>
                <flux:text>{{ $venue->nearest_station }}</flux:text>
            </div>
            @if ($venue->access_note)
                <div>
                    <flux:text class="font-medium">アクセス方法</flux:text>
                    <flux:text class="whitespace-pre-line">{{ $venue->access_note }}</flux:text>
                </div>
            @endif
            @if ($venue->entry_caution)
                <div>
                    <flux:text class="font-medium">注意点</flux:text>
                    <flux:text class="whitespace-pre-line">{{ $venue->entry_caution }}</flux:text>
                </div>
            @endif
            @if ($venue->seat_view)
                <div>
                    <flux:text class="font-medium">座席の見え方</flux:text>
                    <flux:text class="whitespace-pre-line">{{ $venue->seat_view }}</flux:text>
                </div>
            @endif
        </flux:card>

        <livewire:spots.manager :venue="$venue" :key="'spots-'.$venue->id" />

        @if ($venue->events->isNotEmpty())
            <flux:card class="p-4">
                <flux:heading size="lg" class="mb-4">この会場の公演</flux:heading>
                <div class="space-y-2">
                    @foreach ($venue->events as $event)
                        <a href="{{ route('events.show', $event) }}" wire:navigate class="flex items-start justify-between gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="min-w-0 flex-1">
                                <flux:text class="font-medium break-words">{{ $event->title }}</flux:text>
                                <flux:text class="text-sm">{{ $event->event_date->format('Y年n月j日') }}</flux:text>
                            </div>
                            <flux:badge class="shrink-0">{{ $event->event_type->label() }}</flux:badge>
                        </a>
                    @endforeach
                </div>
            </flux:card>
        @endif
    </div>
</div>

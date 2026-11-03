<?php

use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] #[Title('会場一覧')] class extends Component {
    public function with(): array
    {
        return [
            'venues' => Venue::query()
                ->forCurrentUser()
                ->withCount('events')
                ->orderBy('name')
                ->get(),
        ];
    }
}; ?>

<div class="mx-auto w-full max-w-4xl p-6">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl">会場一覧</flux:heading>
            <flux:text class="mt-1">参戦した会場のアクセス情報や周辺スポットを管理できます。</flux:text>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('venues.presets')" variant="outline" wire:navigate>定番会場から選ぶ</flux:button>
            <flux:button :href="route('venues.create')" variant="primary" icon="plus" wire:navigate>会場を登録</flux:button>
        </div>
    </div>

    @if ($venues->isEmpty())
        <flux:card class="p-8 text-center">
            <flux:text>まだ会場が登録されていません。定番会場から選ぶか、自分で登録できます。</flux:text>
            <div class="mt-4 flex flex-wrap justify-center gap-2">
                <flux:button :href="route('venues.presets')" variant="primary" wire:navigate>定番会場から選ぶ</flux:button>
                <flux:button :href="route('venues.create')" variant="outline" wire:navigate>自分で登録</flux:button>
            </div>
        </flux:card>
    @else
        <div class="space-y-3">
            @foreach ($venues as $venue)
                <a href="{{ route('venues.show', $venue) }}" wire:navigate class="block">
                    <flux:card class="p-4">
                        <div class="flex min-w-0 items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <flux:heading size="lg" class="break-words">{{ $venue->name }}</flux:heading>
                                <flux:text class="mt-1 break-words">{{ $venue->address }}</flux:text>
                                @if ($venue->nearest_station)
                                    <flux:text class="mt-1 text-sm break-words">最寄り: {{ $venue->nearest_station }}</flux:text>
                                @endif
                            </div>
                            <flux:badge class="shrink-0">{{ $venue->events_count }}件の公演</flux:badge>
                        </div>
                    </flux:card>
                </a>
            @endforeach
        </div>
    @endif
</div>

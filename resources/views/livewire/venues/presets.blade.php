<?php

use App\Models\Venue;
use App\Support\PresetVenues;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] #[Title('定番会場')] class extends Component {
    /** @var list<string> */
    public array $selected = [];

    public string $status = '';

    public function addSelected(): void
    {
        $this->validate(
            [
                'selected' => ['required', 'array', 'min:1'],
                'selected.*' => ['string'],
            ],
            [
                'selected.required' => '追加する会場を選んでください。',
                'selected.min' => '追加する会場を選んでください。',
            ],
        );

        $added = PresetVenues::addManyForUser((int) Auth::id(), $this->selected);

        $this->selected = [];

        $this->status = $added === 0
            ? '選んだ会場はすでに登録されています。'
            : $added.'件の定番会場を追加しました。';
    }

    public function with(): array
    {
        $venues = Venue::query()->forCurrentUser()->get();
        $addedVenues = [];

        foreach (PresetVenues::all() as $preset) {
            $match = $venues->first(
                fn (Venue $venue): bool => PresetVenues::matches($preset, $venue->name),
            );

            if ($match) {
                $addedVenues[$preset['name']] = $match;
            }
        }

        return [
            'presets' => PresetVenues::all(),
            'addedVenues' => collect($addedVenues),
        ];
    }
}; ?>

<div class="mx-auto w-full max-w-4xl p-6">
    <flux:button :href="route('venues.index')" variant="ghost" size="sm" icon="arrow-left" wire:navigate class="mb-4">
        会場一覧へ
    </flux:button>

    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl">定番会場</flux:heading>
            <flux:text class="mt-1">追加したい会場にチェックを入れてください。自分の会場一覧と地図で使えます。</flux:text>
        </div>
        <flux:button wire:click="addSelected" variant="primary" icon="plus">選択した会場を追加</flux:button>
    </div>

    @if ($status !== '')
        <flux:callout variant="success" class="mb-6">{{ $status }}</flux:callout>
    @endif

    @error('selected')
        <flux:callout variant="danger" class="mb-6">{{ $message }}</flux:callout>
    @enderror

    <form wire:submit="addSelected" class="space-y-3">
        @foreach ($presets as $preset)
            @php
                $addedVenue = $addedVenues->get($preset['name']);
            @endphp

            <flux:card class="p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        @if ($addedVenue)
                            <flux:checkbox :disabled="true" :label="PresetVenues::displayName($preset)" />
                        @else
                            <flux:checkbox wire:model="selected" :value="$preset['name']" :label="PresetVenues::displayName($preset)" />
                        @endif
                        <flux:text class="mt-1">{{ $preset['address'] }}</flux:text>
                        <flux:text class="mt-1 text-sm">最寄り: {{ $preset['nearest_station'] }}</flux:text>
                    </div>
                    @if ($addedVenue)
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:badge>追加済み</flux:badge>
                            <flux:button :href="route('venues.show', $addedVenue)" variant="outline" size="sm" wire:navigate>見る</flux:button>
                        </div>
                    @endif
                </div>
            </flux:card>
        @endforeach

        <div class="flex justify-end pt-2">
            <flux:button type="submit" variant="primary" icon="plus">選択した会場を追加</flux:button>
        </div>
    </form>
</div>

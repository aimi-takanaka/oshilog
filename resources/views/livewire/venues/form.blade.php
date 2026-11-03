<?php

use App\Models\Venue;
use App\Services\GeocodingService;
use App\Support\EventFormReturn;
use App\Support\PresetVenues;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] #[Title('会場登録')] class extends Component {
    public ?Venue $venue = null;

    public string $name = '';

    public string $address = '';

    public string $latitude = '';

    public string $longitude = '';

    public string $nearest_station = '';

    public string $access_note = '';

    public string $entry_caution = '';

    public string $seat_view = '';

    public string $geocodeError = '';

    public ?string $returnUrl = null;

    public function mount(?Venue $venue = null): void
    {
        $this->venue = $venue?->exists ? $venue : null;

        if ($this->venue) {
            $this->name = $venue->name;
            $this->address = $venue->address;
            $this->latitude = (string) $venue->latitude;
            $this->longitude = (string) $venue->longitude;
            $this->nearest_station = $venue->nearest_station;
            $this->access_note = $venue->access_note ?? '';
            $this->entry_caution = $venue->entry_caution ?? '';
            $this->seat_view = $venue->seat_view ?? '';
        } else {
            $this->returnUrl = EventFormReturn::url();
        }
    }

    public function updatedAddress(GeocodingService $geocoding): void
    {
        $this->resolveGeocode($geocoding);
    }

    public function updatedName(GeocodingService $geocoding): void
    {
        if (mb_strlen(trim($this->name)) < 2) {
            return;
        }

        $this->resolveGeocode($geocoding);
    }

    public function save(GeocodingService $geocoding): void
    {
        if ($this->latitude === '' || $this->longitude === '') {
            $this->resolveGeocode($geocoding);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'nearest_station' => ['required', 'string', 'max:255'],
            'access_note' => ['nullable', 'string'],
            'entry_caution' => ['nullable', 'string'],
            'seat_view' => ['nullable', 'string'],
        ]);

        $data = [
            ...$validated,
            'user_id' => Auth::id(),
        ];

        if ($this->venue) {
            $this->venue->update($data);
            $venue = $this->venue;
        } else {
            $venue = Venue::query()->create($data);
        }

        session()->flash('status', $this->venue ? '会場を更新しました。' : '会場を登録しました。');

        if (! $this->venue) {
            EventFormReturn::attachVenue($venue->id);
            $returnUrl = EventFormReturn::url();

            if ($returnUrl !== null) {
                $this->redirect($returnUrl, navigate: true);

                return;
            }
        }

        $this->redirectRoute('venues.show', $venue, navigate: true);
    }

    private function resolveGeocode(GeocodingService $geocoding): void
    {
        $this->geocodeError = '';

        if (trim($this->name) === '' && trim($this->address) === '') {
            return;
        }

        $result = $geocoding->geocodeVenue($this->name, $this->address);

        if ($result === null) {
            $this->geocodeError = '会場名・住所から位置を特定できませんでした。正式名称か住所を確認してください。';

            return;
        }

        $this->latitude = (string) $result['latitude'];
        $this->longitude = (string) $result['longitude'];

        $preset = PresetVenues::findByName($this->name);

        if (trim($this->address) === '') {
            $fromMap = trim((string) ($result['address'] ?? ''));
            $this->address = $fromMap !== '' ? $fromMap : ($preset['address'] ?? '');
        }

        if ($preset !== null && trim($this->nearest_station) === '') {
            $this->nearest_station = $preset['nearest_station'];
        }

        $this->dispatchVenueMapUpdate();
    }

    private function dispatchVenueMapUpdate(): void
    {
        $this->dispatch(
            'venue-map-updated',
            lat: (float) $this->latitude,
            lng: (float) $this->longitude,
            name: $this->name,
            id: 'venue-form-map',
        );
    }
}; ?>

<div class="mx-auto w-full max-w-2xl p-6">
    <flux:button :href="$returnUrl ?? ($venue ? route('venues.show', $venue) : route('venues.index'))" variant="ghost" size="sm" icon="arrow-left" wire:navigate class="mb-4">
        {{ $returnUrl ? '公演の記録に戻る' : '戻る' }}
    </flux:button>

    <flux:heading size="xl" class="mb-6">{{ $venue ? '会場を編集' : '会場を登録' }}</flux:heading>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:input wire:model.live.debounce.2000ms="name" label="会場名" required />
                <div wire:loading wire:target="name" class="mt-2">
                    <flux:text class="text-sm">位置を検索しています...</flux:text>
                </div>
            </div>
            <div>
                <flux:input wire:model.live.debounce.1500ms="address" label="住所" required />
                <div wire:loading wire:target="address" class="mt-2">
                    <flux:text class="text-sm">位置を検索しています...</flux:text>
                </div>
                @if ($geocodeError)
                    <flux:text class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $geocodeError }}</flux:text>
                @endif
            </div>

            <div>
                <flux:heading size="sm" class="mb-2">地図プレビュー</flux:heading>
                <x-venue-map
                    id="venue-form-map"
                    :latitude="$latitude"
                    :longitude="$longitude"
                    :name="$name"
                />
                <flux:text class="mt-2 text-sm">会場名を入力すると、地図と住所が自動で入ります。定番会場は最寄り駅も入ります。</flux:text>
            </div>

            <flux:input wire:model="nearest_station" label="最寄り駅" required />
            <flux:textarea wire:model="access_note" label="アクセス方法" rows="3" />
            <flux:textarea wire:model="entry_caution" label="注意点" rows="3" />
            <flux:textarea wire:model="seat_view" label="座席の見え方" rows="3" />

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit">{{ $venue ? '更新する' : ($returnUrl ? '登録して公演の記録に戻る' : '登録する') }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>

<?php

use App\EventType;
use App\EventTimeOfDay;
use App\EventVisibility;
use App\Models\Companion;
use App\Models\Event;
use App\Models\Venue;
use App\Services\WeatherService;
use App\Support\EventFormReturn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] #[Title('公演登録')] class extends Component {
    public ?Event $event = null;

    public string $title = '';

    public string $event_type = 'live';

    public string $event_date = '';

    public string $time_of_day = 'day';

    public string $venue_id = '';

    public string $weather = '';

    public ?string $temperature_max = null;

    public ?string $temperature_min = null;

    public ?string $precipitation_probability = null;

    public string $weatherError = '';

    public string $impression = '';

    /** @var array<int, string> */
    public array $companionNames = [''];

    public function mount(?Event $event = null, WeatherService $weather): void
    {
        $this->event = $event?->exists ? $event : null;

        if ($this->event) {
            $event->load('companions');

            $this->title = $event->title;
            $this->event_type = $event->event_type->value;
            $this->event_date = $event->event_date->format('Y-m-d');
            $this->time_of_day = $event->time_of_day->value;
            $this->venue_id = (string) $event->venue_id;
            $this->weather = $event->weather ?? '';
            $this->temperature_max = $event->temperature_max !== null ? (string) $event->temperature_max : null;
            $this->temperature_min = $event->temperature_min !== null ? (string) $event->temperature_min : null;
            $this->precipitation_probability = $event->precipitation_probability !== null
                ? (string) $event->precipitation_probability
                : null;
            $this->impression = $event->impression ?? '';
            $this->companionNames = $event->companions->pluck('name')->all() ?: [''];
        } else {
            $this->event_date = now()->format('Y-m-d');
        }

        $this->restoreFromVenueReturn($weather);
    }

    public function goToVenueCreate(): void
    {
        EventFormReturn::remember(
            [
                'title' => $this->title,
                'event_type' => $this->event_type,
                'event_date' => $this->event_date,
                'time_of_day' => $this->time_of_day,
                'venue_id' => $this->venue_id,
                'weather' => $this->weather,
                'temperature_max' => $this->temperature_max,
                'temperature_min' => $this->temperature_min,
                'precipitation_probability' => $this->precipitation_probability,
                'impression' => $this->impression,
                'companionNames' => $this->companionNames,
            ],
            $this->event ? 'events.edit' : 'events.create',
            $this->event?->id,
        );

        $this->redirectRoute('venues.create', navigate: true);
    }

    public function addCompanionField(): void
    {
        $this->companionNames[] = '';
    }

    public function removeCompanionField(int $index): void
    {
        if (count($this->companionNames) <= 1) {
            return;
        }

        unset($this->companionNames[$index]);
        $this->companionNames = array_values($this->companionNames);
    }

    public function updatedVenueId(WeatherService $weather): void
    {
        $this->resolveWeather($weather);
    }

    public function updatedEventDate(WeatherService $weather): void
    {
        $this->resolveWeather($weather);
    }

    public function refreshWeather(WeatherService $weather): void
    {
        $this->resolveWeather($weather);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', Rule::enum(EventType::class)],
            'event_date' => ['required', 'date'],
            'time_of_day' => ['required', Rule::enum(EventTimeOfDay::class)],
            'venue_id' => ['required', Rule::exists('venues', 'id')->where('user_id', Auth::id())],
            'weather' => ['nullable', 'string', 'max:50'],
            'temperature_max' => ['nullable', 'numeric', 'between:-90,60'],
            'temperature_min' => ['nullable', 'numeric', 'between:-90,60'],
            'precipitation_probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'impression' => ['nullable', 'string'],
            'companionNames' => ['array'],
            'companionNames.*' => ['nullable', 'string', 'max:255'],
        ]);

        $data = [
            'title' => $validated['title'],
            'event_type' => $validated['event_type'],
            'event_date' => $validated['event_date'],
            'time_of_day' => $validated['time_of_day'],
            'venue_id' => $validated['venue_id'],
            'weather' => $validated['weather'] !== '' ? $validated['weather'] : null,
            'temperature_max' => filled($validated['temperature_max'] ?? null) ? $validated['temperature_max'] : null,
            'temperature_min' => filled($validated['temperature_min'] ?? null) ? $validated['temperature_min'] : null,
            'precipitation_probability' => filled($validated['precipitation_probability'] ?? null)
                ? $validated['precipitation_probability']
                : null,
            'impression' => $validated['impression'] ?? null,
            'user_id' => Auth::id(),
            'visibility' => EventVisibility::Private,
        ];

        if ($this->event) {
            $this->event->update($data);
            $event = $this->event;
        } else {
            $event = Event::query()->create($data);
        }

        $companionIds = collect($validated['companionNames'])
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->map(fn (string $name) => Companion::query()->firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'name' => $name,
                ],
            )->id)
            ->all();

        $event->companions()->sync($companionIds);

        session()->flash('status', $this->event ? '公演を更新しました。' : '公演を記録しました。');

        $this->redirectRoute('events.show', $event, navigate: true);
    }

    private function restoreFromVenueReturn(WeatherService $weather): void
    {
        $payload = EventFormReturn::peek();

        if ($payload === null || ! $this->matchesReturnPayload($payload)) {
            return;
        }

        $payload = EventFormReturn::pull();
        $draft = is_array($payload['draft'] ?? null) ? $payload['draft'] : [];

        $this->title = is_string($draft['title'] ?? null) ? $draft['title'] : $this->title;
        $this->event_type = is_string($draft['event_type'] ?? null) ? $draft['event_type'] : $this->event_type;
        $this->event_date = is_string($draft['event_date'] ?? null) ? $draft['event_date'] : $this->event_date;
        $this->time_of_day = is_string($draft['time_of_day'] ?? null) ? $draft['time_of_day'] : $this->time_of_day;
        $this->weather = is_string($draft['weather'] ?? null) ? $draft['weather'] : $this->weather;
        $this->temperature_max = array_key_exists('temperature_max', $draft) ? $draft['temperature_max'] : $this->temperature_max;
        $this->temperature_min = array_key_exists('temperature_min', $draft) ? $draft['temperature_min'] : $this->temperature_min;
        $this->precipitation_probability = array_key_exists('precipitation_probability', $draft)
            ? $draft['precipitation_probability']
            : $this->precipitation_probability;
        $this->impression = is_string($draft['impression'] ?? null) ? $draft['impression'] : $this->impression;

        if (isset($draft['companionNames']) && is_array($draft['companionNames']) && $draft['companionNames'] !== []) {
            $this->companionNames = array_values(array_map(
                fn (mixed $name): string => is_string($name) ? $name : '',
                $draft['companionNames'],
            ));
        }

        $attachedVenueId = $payload['venue_id'] ?? null;

        if (filled($attachedVenueId)) {
            $this->venue_id = (string) $attachedVenueId;
            $this->resolveWeather($weather);

            return;
        }

        if (is_string($draft['venue_id'] ?? null)) {
            $this->venue_id = $draft['venue_id'];
        }
    }

    /**
     * @param  array{route: string, event_id: ?int}  $payload
     */
    private function matchesReturnPayload(array $payload): bool
    {
        if ($this->event) {
            return $payload['route'] === 'events.edit' && (int) ($payload['event_id'] ?? 0) === $this->event->id;
        }

        return $payload['route'] === 'events.create' && empty($payload['event_id']);
    }

    private function resolveWeather(WeatherService $weather): void
    {
        $this->weatherError = '';

        if ($this->venue_id === '' || $this->event_date === '') {
            return;
        }

        $venue = Venue::query()
            ->forCurrentUser()
            ->find($this->venue_id);

        if ($venue === null) {
            return;
        }

        $result = $weather->fetchForDate(
            (float) $venue->latitude,
            (float) $venue->longitude,
            $this->event_date,
        );

        if ($result === null) {
            $this->weatherError = '天気情報を取得できませんでした。手入力するか、再取得を試してください。';

            return;
        }

        $this->weather = $result['weather'];
        $this->temperature_max = (string) $result['temperature_max'];
        $this->temperature_min = (string) $result['temperature_min'];
        $this->precipitation_probability = $result['precipitation_probability'] !== null
            ? (string) $result['precipitation_probability']
            : null;
    }

    public function with(): array
    {
        return [
            'venues' => Venue::query()->forCurrentUser()->orderBy('name')->get(),
            'eventTypes' => EventType::cases(),
            'timeOfDayOptions' => EventTimeOfDay::cases(),
        ];
    }
}; ?>

<div class="mx-auto w-full max-w-2xl p-6">
    <flux:button :href="$event ? route('events.show', $event) : route('events.index')" variant="ghost" size="sm" icon="arrow-left" wire:navigate class="mb-4">
        戻る
    </flux:button>

    <flux:heading size="xl" class="mb-6">{{ $event ? '公演を編集' : '公演を記録' }}</flux:heading>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="title" label="公演タイトル" required />

            <flux:radio.group wire:model="event_type" label="種別">
                @foreach ($eventTypes as $type)
                    <flux:radio :value="$type->value" :label="$type->label()" />
                @endforeach
            </flux:radio.group>

            <flux:input wire:model.live.debounce.750ms="event_date" label="日付" type="date" required />

            <flux:radio.group wire:model="time_of_day" label="昼夜">
                @foreach ($timeOfDayOptions as $option)
                    <flux:radio :value="$option->value" :label="$option->label()" />
                @endforeach
            </flux:radio.group>

            <div>
                <flux:select wire:model.live="venue_id" label="会場" required>
                    <flux:select.option value="">会場を選択</flux:select.option>
                    @foreach ($venues as $venue)
                        <flux:select.option :value="(string) $venue->id">{{ $venue->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:button type="button" variant="ghost" size="sm" wire:click="goToVenueCreate" class="mt-2 px-0">新規会場を登録</flux:button>
            </div>

            <div class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between gap-4">
                    <flux:heading size="sm">天気</flux:heading>
                    <flux:button type="button" variant="outline" size="sm" wire:click="refreshWeather" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="refreshWeather, venue_id, event_date">再取得</span>
                        <span wire:loading wire:target="refreshWeather, venue_id, event_date">取得中...</span>
                    </flux:button>
                </div>

                @if ($weatherError)
                    <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $weatherError }}</flux:text>
                @endif

                <flux:text class="text-sm">会場と日付を選ぶと Open-Meteo から自動入力されます。過去の公演は降水確率が空のことがあり、その場合は手入力してください。</flux:text>

                <flux:input wire:model="weather" label="天気" placeholder="晴れ" />
                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="temperature_max" label="最高気温 (℃)" type="number" step="0.1" />
                    <flux:input wire:model="temperature_min" label="最低気温 (℃)" type="number" step="0.1" />
                    <flux:input wire:model="precipitation_probability" label="降水確率 (%)" type="number" min="0" max="100" />
                </div>
            </div>

            <div class="space-y-3">
                <flux:label>同行者</flux:label>
                @foreach ($companionNames as $index => $companionName)
                    <div class="flex gap-2" wire:key="companion-{{ $index }}">
                        <flux:input wire:model="companionNames.{{ $index }}" placeholder="同行者名" class="flex-1" />
                        @if (count($companionNames) > 1)
                            <flux:button type="button" variant="ghost" icon="x-mark" wire:click="removeCompanionField({{ $index }})" />
                        @endif
                    </div>
                @endforeach
                <flux:button type="button" variant="outline" size="sm" icon="plus" wire:click="addCompanionField">同行者を追加</flux:button>
            </div>

            <flux:textarea wire:model="impression" label="感想" rows="5" />

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit">{{ $event ? '更新する' : '記録する' }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>

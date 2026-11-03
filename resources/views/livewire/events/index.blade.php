<?php

use App\EventType;
use App\Models\Event;
use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] #[Title('公演一覧')] class extends Component {
    #[Url]
    public string $eventType = '';

    #[Url]
    public string $venueId = '';

    public function with(): array
    {
        $query = Event::query()
            ->forCurrentUser()
            ->with(['venue', 'photos'])
            ->orderByDesc('event_date');

        if ($this->eventType !== '') {
            $query->ofType(EventType::from($this->eventType));
        }

        if ($this->venueId !== '') {
            $query->where('venue_id', $this->venueId);
        }

        $events = $query->get();

        return [
            'groupedEvents' => $events->groupBy(fn (Event $event) => $event->event_date->format('Y年n月')),
            'venues' => Venue::query()->forCurrentUser()->orderBy('name')->get(),
            'eventTypes' => EventType::cases(),
        ];
    }
}; ?>

<div class="mx-auto w-full max-w-4xl px-4 py-4 sm:p-6">
    @if (session('status'))
        <flux:callout variant="success" class="mb-6">{{ session('status') }}</flux:callout>
    @endif

    <div class="comic-panel overflow-hidden p-4 sm:p-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <flux:heading size="xl">公演一覧</flux:heading>
                <flux:text class="mt-1">参戦記録をタイムラインで振り返れます。</flux:text>
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:justify-end">
                <flux:button :href="route('events.create')" variant="primary" icon="plus" class="w-full sm:w-auto" wire:navigate>公演を記録</flux:button>
                <flux:button :href="route('venues.create')" variant="outline" icon="plus" class="w-full sm:w-auto" wire:navigate>会場を記録</flux:button>
            </div>
        </div>

        <flux:card class="mb-6 p-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="eventType" label="種別">
                    <flux:select.option value="">すべて</flux:select.option>
                    @foreach ($eventTypes as $type)
                        <flux:select.option :value="$type->value">{{ $type->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="venueId" label="会場">
                    <flux:select.option value="">すべて</flux:select.option>
                    @foreach ($venues as $venue)
                        <flux:select.option :value="(string) $venue->id">{{ $venue->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        @if ($groupedEvents->isEmpty())
            <flux:card class="p-8 text-center">
                <flux:text>該当する公演がありません。</flux:text>
                <flux:button :href="route('events.create')" variant="primary" class="mt-4" wire:navigate>公演を記録する</flux:button>
            </flux:card>
        @else
            <div class="space-y-8">
                @foreach ($groupedEvents as $month => $events)
                    <section>
                        <flux:heading size="lg" class="mb-4">{{ $month }}</flux:heading>
                        <div class="space-y-3">
                            @foreach ($events as $event)
                                <a href="{{ route('events.show', $event) }}" wire:navigate class="block">
                                    <flux:card class="p-4">
                                        <div class="min-w-0">
                                            <div class="flex min-w-0 items-start justify-between gap-2">
                                                <flux:heading class="min-w-0 flex-1 break-words">{{ $event->title }}</flux:heading>
                                                <flux:badge class="shrink-0">{{ $event->event_type->label() }}</flux:badge>
                                            </div>

                                            <flux:text class="mt-1 text-sm break-words">
                                                {{ $event->event_date->format('n月j日') }}（{{ $event->time_of_day->label() }}） · {{ $event->venue->name }}
                                            </flux:text>

                                            @if ($event->impression)
                                                <flux:text class="mt-2 whitespace-pre-line break-words">{{ $event->impression }}</flux:text>
                                            @endif

                                            @if ($event->photos->isNotEmpty())
                                                <div @class([
                                                    'mt-3 grid max-w-[220px] gap-1 overflow-hidden rounded-2xl border-2 border-black shadow-[3px_3px_0_#1a1a1a]',
                                                    'grid-cols-2' => $event->photos->take(2)->count() > 1,
                                                    'grid-cols-1' => $event->photos->take(2)->count() === 1,
                                                ])>
                                                    @foreach ($event->photos->take(2) as $photo)
                                                        @if ($url = $photo->publicUrl())
                                                            <img
                                                                src="{{ $url }}"
                                                                alt="{{ $photo->caption ?? $event->title }}"
                                                                class="aspect-square w-full object-cover"
                                                            />
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </flux:card>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</div>

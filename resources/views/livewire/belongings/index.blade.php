<?php

use App\EventType;
use App\Models\Belonging;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] #[Title('持ち物')] class extends Component {
    #[Url]
    public string $eventType = 'live';

    public string $newName = '';

    public string $newMemo = '';

    public function mount(): void
    {
        $this->ensureValidEventType();
    }

    public function updatedEventType(): void
    {
        $this->ensureValidEventType();
    }

    public function addItem(): void
    {
        $validated = $this->validate([
            'eventType' => ['required', Rule::enum(EventType::class)],
            'newName' => ['required', 'string', 'max:255'],
            'newMemo' => ['nullable', 'string'],
        ]);

        Belonging::query()->create([
            'user_id' => Auth::id(),
            'event_type' => $validated['eventType'],
            'name' => $validated['newName'],
            'memo' => $validated['newMemo'] !== '' ? $validated['newMemo'] : null,
        ]);

        $this->reset('newName', 'newMemo');
    }

    public function togglePacked(int $itemId): void
    {
        $item = $this->findItem($itemId);
        $item->update([
            'is_packed' => ! $item->is_packed,
        ]);
    }

    public function resetPacked(): void
    {
        Belonging::query()
            ->forCurrentUser()
            ->ofType($this->currentType())
            ->update(['is_packed' => false]);
    }

    public function deleteItem(int $itemId): void
    {
        $this->findItem($itemId)->delete();
    }

    protected function findItem(int $itemId): Belonging
    {
        return Belonging::query()
            ->forCurrentUser()
            ->ofType($this->currentType())
            ->whereKey($itemId)
            ->firstOrFail();
    }

    protected function currentType(): EventType
    {
        return EventType::from($this->eventType);
    }

    protected function ensureValidEventType(): void
    {
        if (EventType::tryFrom($this->eventType) === null) {
            $this->eventType = EventType::Live->value;
        }
    }

    public function with(): array
    {
        $allItems = Belonging::query()
            ->forCurrentUser()
            ->orderBy('id')
            ->get();

        $currentType = $this->currentType();

        return [
            'eventTypes' => EventType::cases(),
            'currentType' => $currentType,
            'items' => $allItems->filter(fn (Belonging $item) => $item->event_type === $currentType)->values(),
            'counts' => $allItems->countBy(fn (Belonging $item) => $item->event_type->value),
        ];
    }
}; ?>

<div class="mx-auto w-full max-w-4xl p-6">
    <div class="mb-6">
        <flux:heading size="xl">持ち物</flux:heading>
        <flux:text class="mt-1">ライブ・舞台・ミュージカル・イベント・ロケ地ごとの持ち物リストを管理できます。</flux:text>
    </div>

    <div class="mb-6 flex gap-2 border-b border-zinc-200 dark:border-zinc-700">
        @foreach ($eventTypes as $type)
            <flux:button
                wire:click="$set('eventType', '{{ $type->value }}')"
                :variant="$eventType === $type->value ? 'primary' : 'ghost'"
                size="sm"
            >
                {{ $type->label() }}
                @if (($counts[$type->value] ?? 0) > 0)
                    ({{ $counts[$type->value] }})
                @endif
            </flux:button>
        @endforeach
    </div>

    <div class="space-y-6">
        <flux:card class="p-4">
            <form wire:submit="addItem" class="space-y-4">
                <flux:heading size="lg">持ち物を追加</flux:heading>
                <flux:input wire:model="newName" label="名前" required placeholder="ペンライト" />
                <flux:textarea wire:model="newMemo" label="メモ（任意）" rows="2" placeholder="予備電池も入れる" />
                <flux:button type="submit" variant="primary" size="sm">追加</flux:button>
            </form>
        </flux:card>

        <flux:card class="p-4">
            <div class="mb-4 flex items-center justify-between gap-3">
                <flux:heading size="lg">{{ $currentType->label() }}の持ち物</flux:heading>
                @if ($items->contains('is_packed', true))
                    <flux:button size="sm" variant="outline" wire:click="resetPacked">チェックをリセット</flux:button>
                @endif
            </div>

            @if ($items->isEmpty())
                <flux:text>まだ持ち物が登録されていません。</flux:text>
            @else
                <div class="space-y-3">
                    @foreach ($items as $item)
                        <div class="flex items-start justify-between gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="belonging-{{ $item->id }}">
                            <button
                                type="button"
                                wire:click="togglePacked({{ $item->id }})"
                                class="flex min-w-0 flex-1 items-start gap-3 text-left"
                            >
                                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded border-2 border-black {{ $item->is_packed ? 'bg-black text-white' : 'bg-white' }}">
                                    @if ($item->is_packed)
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5">
                                            <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.208Z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <flux:text class="font-medium {{ $item->is_packed ? 'line-through opacity-60' : '' }}">{{ $item->name }}</flux:text>
                                    @if ($item->memo)
                                        <flux:text class="mt-1 whitespace-pre-line text-sm {{ $item->is_packed ? 'opacity-60' : '' }}">{{ $item->memo }}</flux:text>
                                    @endif
                                </span>
                            </button>
                            <flux:button
                                size="sm"
                                variant="danger"
                                wire:click="deleteItem({{ $item->id }})"
                                wire:confirm="この持ち物を削除しますか？"
                            >削除</flux:button>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>
    </div>
</div>

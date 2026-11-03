<?php

use App\Models\Spot;
use App\Models\Venue;
use App\SpotCategory;
use App\SpotStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public Venue $venue;

    public string $newName = '';

    public string $newCategory = 'cafe';

    public string $newStatus = 'wishlist';

    public string $newMemo = '';

    public string $newMapUrl = '';

    public ?int $markingSpotId = null;

    public string $visitMemo = '';

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;
    }

    public function addSpot(): void
    {
        $validated = $this->validate([
            'newName' => ['required', 'string', 'max:255'],
            'newCategory' => ['required', Rule::enum(SpotCategory::class)],
            'newStatus' => ['required', Rule::enum(SpotStatus::class)],
            'newMemo' => ['nullable', 'string'],
            'newMapUrl' => ['nullable', 'string', 'max:255'],
        ]);

        Spot::query()->create([
            'user_id' => Auth::id(),
            'venue_id' => $this->venue->id,
            'name' => $validated['newName'],
            'category' => $validated['newCategory'],
            'status' => $validated['newStatus'],
            'memo' => $validated['newMemo'] ?? null,
            'map_url' => $validated['newMapUrl'] ?? null,
        ]);

        $this->reset('newName', 'newMemo', 'newMapUrl');
        $this->newCategory = SpotCategory::Cafe->value;
        $this->newStatus = SpotStatus::Wishlist->value;

        $this->venue->load('spots');
    }

    public function openVisitModal(int $spotId): void
    {
        $spot = $this->findSpot($spotId);
        $this->markingSpotId = $spot->id;
        $this->visitMemo = '';
        $this->dispatch('open-modal', 'mark-spot-visited-'.$this->venue->id);
    }

    public function markVisited(): void
    {
        $this->validate([
            'visitMemo' => ['nullable', 'string'],
        ]);

        if ($this->markingSpotId === null) {
            return;
        }

        $spot = $this->findSpot($this->markingSpotId);
        $memo = trim($spot->memo ?? '');

        if ($this->visitMemo !== '') {
            $memo = $memo === '' ? $this->visitMemo : $memo."\n\n".$this->visitMemo;
        }

        $spot->update([
            'status' => SpotStatus::Visited,
            'memo' => $memo !== '' ? $memo : null,
        ]);

        $this->reset('markingSpotId', 'visitMemo');
        $this->venue->load('spots');
        $this->dispatch('close-modal', 'mark-spot-visited-'.$this->venue->id);
    }

    public function deleteSpot(int $spotId): void
    {
        $this->findSpot($spotId)->delete();
        $this->venue->load('spots');
    }

    protected function findSpot(int $spotId): Spot
    {
        return Spot::query()
            ->where('venue_id', $this->venue->id)
            ->forCurrentUser()
            ->whereKey($spotId)
            ->firstOrFail();
    }

    public function with(): array
    {
        $spots = $this->venue->spots->sortBy('name');

        return [
            'wishlistSpots' => $spots->where('status', SpotStatus::Wishlist)->values(),
            'visitedSpots' => $spots->where('status', SpotStatus::Visited)->values(),
            'spotCategories' => SpotCategory::cases(),
            'spotStatuses' => SpotStatus::cases(),
            'markingSpot' => $this->markingSpotId
                ? $spots->firstWhere('id', $this->markingSpotId)
                : null,
        ];
    }
}; ?>

<flux:card class="p-4">
    <flux:heading size="lg" class="mb-4">立ち寄りスポット</flux:heading>

    <form wire:submit="addSpot" class="mb-6 space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:heading size="md">スポットを追加</flux:heading>
        <flux:input wire:model="newName" label="名前" required />
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:select wire:model="newCategory" label="カテゴリ">
                @foreach ($spotCategories as $category)
                    <flux:select.option :value="$category->value">{{ $category->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="newStatus" label="ステータス">
                @foreach ($spotStatuses as $status)
                    <flux:select.option :value="$status->value">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:textarea wire:model="newMemo" label="メモ" rows="2" />
        <flux:input wire:model="newMapUrl" label="地図リンク（任意）" placeholder="https://maps.google.com/..." />
        <flux:button type="submit" variant="primary" size="sm">追加</flux:button>
    </form>

    <div class="space-y-6">
        <section>
            <flux:heading size="md" class="mb-3">行きたい</flux:heading>
            @if ($wishlistSpots->isEmpty())
                <flux:text>行きたいスポットはありません。</flux:text>
            @else
                <div class="space-y-3">
                    @foreach ($wishlistSpots as $spot)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="wishlist-spot-{{ $spot->id }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <flux:text class="font-medium">{{ $spot->name }}</flux:text>
                                    <flux:badge size="sm" class="mt-1">{{ $spot->category->label() }}</flux:badge>
                                    @if ($spot->memo)
                                        <flux:text class="mt-2 whitespace-pre-line text-sm">{{ $spot->memo }}</flux:text>
                                    @endif
                                    @if ($spot->map_url)
                                        <flux:link href="{{ $spot->map_url }}" target="_blank" class="mt-2 text-sm">地図を開く</flux:link>
                                    @endif
                                </div>
                                <div class="flex shrink-0 flex-col gap-2">
                                    <flux:button size="sm" variant="primary" wire:click="openVisitModal({{ $spot->id }})">行ったにする</flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="deleteSpot({{ $spot->id }})" wire:confirm="このスポットを削除しますか？">削除</flux:button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section>
            <flux:heading size="md" class="mb-3">行った</flux:heading>
            @if ($visitedSpots->isEmpty())
                <flux:text>行ったスポットはありません。</flux:text>
            @else
                <div class="space-y-3">
                    @foreach ($visitedSpots as $spot)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="visited-spot-{{ $spot->id }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <flux:text class="font-medium">{{ $spot->name }}</flux:text>
                                    <flux:badge size="sm" class="mt-1">{{ $spot->category->label() }}</flux:badge>
                                    @if ($spot->memo)
                                        <flux:text class="mt-2 whitespace-pre-line text-sm">{{ $spot->memo }}</flux:text>
                                    @endif
                                    @if ($spot->map_url)
                                        <flux:link href="{{ $spot->map_url }}" target="_blank" class="mt-2 text-sm">地図を開く</flux:link>
                                    @endif
                                </div>
                                <flux:button size="sm" variant="danger" wire:click="deleteSpot({{ $spot->id }})" wire:confirm="このスポットを削除しますか？">削除</flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <flux:modal name="mark-spot-visited-{{ $venue->id }}" focusable class="max-w-lg">
        <form wire:submit="markVisited" class="space-y-6">
            <div>
                <flux:heading size="lg">行ったにする</flux:heading>
                @if ($markingSpot)
                    <flux:subheading>{{ $markingSpot->name }} の感想を追記できます。</flux:subheading>
                @endif
            </div>

            @if ($markingSpot?->memo)
                <div>
                    <flux:text class="font-medium">これまでのメモ</flux:text>
                    <flux:text class="mt-1 whitespace-pre-line text-sm">{{ $markingSpot->memo }}</flux:text>
                </div>
            @endif

            <flux:textarea wire:model="visitMemo" label="感想を追記" rows="4" placeholder="実際に行ってみた感想を書き留めましょう" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">キャンセル</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">行ったにする</flux:button>
            </div>
        </form>
    </flux:modal>
</flux:card>

<?php

use App\Models\Event;
use App\Models\Photo;
use App\Support\ShareCard;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.share')] #[Title('投稿カード')] class extends Component {
    public Event $event;

    public string $cardStyle = ShareCard::STYLE_SCRAPBOOK;

    public string $cardBackground = ShareCard::BACKGROUND_CREAM;

    public string $cardImpression = '';

    public string $featuredPhotoId = '';

    /** @var list<string> */
    public array $scrapbookPhotoIds = [];

    public function mount(Event $event): void
    {
        $this->event = $event->load(['venue', 'photos']);
        $this->cardImpression = $event->impression ?? '';

        $photoIds = $this->event->photos
            ->sortBy('id')
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->values();

        $this->featuredPhotoId = (string) ($photoIds->first() ?? '');
        $this->scrapbookPhotoIds = $photoIds->take(ShareCard::SCRAPBOOK_MAX_PHOTOS)->all();
    }

    public function selectStyle(string $style): void
    {
        if (! ShareCard::isValidStyle($style)) {
            return;
        }

        $this->cardStyle = $style;
    }

    public function selectBackground(string $background): void
    {
        if (! ShareCard::isValidBackground($background)) {
            return;
        }

        $this->cardBackground = $background;
    }

    public function restoreImpression(): void
    {
        $this->cardImpression = $this->event->impression ?? '';
    }

    public function selectTicketPhoto(string $photoId): void
    {
        if (! $this->ownsPhoto($photoId)) {
            return;
        }

        $this->featuredPhotoId = $photoId;
    }

    public function toggleScrapbookPhoto(string $photoId): void
    {
        if (! $this->ownsPhoto($photoId)) {
            return;
        }

        if (in_array($photoId, $this->scrapbookPhotoIds, true)) {
            $this->scrapbookPhotoIds = array_values(array_filter(
                $this->scrapbookPhotoIds,
                fn (string $id): bool => $id !== $photoId,
            ));

            return;
        }

        if (count($this->scrapbookPhotoIds) >= ShareCard::SCRAPBOOK_MAX_PHOTOS) {
            return;
        }

        $this->scrapbookPhotoIds[] = $photoId;
    }

    private function ownsPhoto(string $photoId): bool
    {
        return $this->event->photos->contains(fn (Photo $photo): bool => (string) $photo->id === $photoId);
    }

    public function with(): array
    {
        $photos = $this->event->photos->sortBy('id')->values();
        $limit = ShareCard::impressionLimit($this->cardStyle);
        $count = mb_strlen($this->cardImpression);

        return [
            'photos' => $photos,
            'featuredPhoto' => $photos->firstWhere('id', (int) $this->featuredPhotoId),
            'scrapbookPhotos' => collect($this->scrapbookPhotoIds)
                ->map(fn (string $id) => $photos->firstWhere('id', (int) $id))
                ->filter()
                ->values(),
            'impressionLimit' => $limit,
            'impressionCount' => $count,
            'impressionOver' => $count > $limit,
            'isScrapbook' => $this->cardStyle === ShareCard::STYLE_SCRAPBOOK,
            'isTicket' => $this->cardStyle === ShareCard::STYLE_TICKET,
            'ticketHasPhoto' => $photos->firstWhere('id', (int) $this->featuredPhotoId)?->publicUrl() !== null,
            'backgrounds' => ShareCard::BACKGROUNDS,
        ];
    }
}; ?>

<div>
    <div class="share-card-toolbar">
        <flux:button :href="route('events.show', $event)" variant="ghost" size="sm" icon="arrow-left" wire:navigate>
            公演詳細へ
        </flux:button>
        <flux:text class="text-sm">タイプを選んでカードをスクショできます。</flux:text>
    </div>

    <div class="share-style-switch" role="group" aria-label="カードのタイプ">
        <button
            type="button"
            wire:click="selectStyle('scrapbook')"
            @class(['share-style-switch-btn', 'is-active' => $isScrapbook])
        >スクラップブック</button>
        <button
            type="button"
            wire:click="selectStyle('ticket')"
            @class(['share-style-switch-btn', 'is-active' => $isTicket])
        >チケット</button>
    </div>

    <div class="share-card-stage">
        @if ($isTicket)
            <article class="share-card share-card-ticket-board share-bg-{{ $cardBackground }} share-frame-ink">
                <x-share-dots-art />
                <div class="share-strip-pair">
                    <div class="share-strip-ticket is-back">
                        <div class="share-strip-body is-back-body">
                            <p class="share-strip-back-kicker">IMPRESSION</p>
                            @if ($cardImpression !== '')
                                <p class="share-strip-impression">{{ $cardImpression }}</p>
                            @else
                                <p class="share-strip-impression is-empty">感想はまだありません</p>
                            @endif
                            <p class="share-strip-back-foot">{{ $event->title }} · {{ $event->event_date->format('Y.m.d') }} · OshiLog</p>
                        </div>
                    </div>

                    <div @class(['share-strip-ticket', 'is-front', 'has-no-photo' => ! $ticketHasPhoto])>
                        <div class="share-strip-body">
                            @if ($featuredPhoto && $featuredPhoto->publicUrl())
                                <img
                                    class="share-strip-bg"
                                    src="{{ $featuredPhoto->publicUrl() }}"
                                    alt="{{ $featuredPhoto->caption ?? $event->title }}"
                                />
                            @endif
                            <div class="share-strip-copy">
                                <p class="share-strip-kicker">{{ $event->event_type->label() }}</p>
                                <h1 class="share-strip-title">{{ $event->title }}</h1>
                                <p class="share-strip-meta">
                                    {{ $event->event_date->format('Y.m.d') }}　{{ $event->time_of_day->label() }}
                                </p>
                                <p class="share-strip-meta">{{ $event->venue->name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        @else
            <article class="share-card share-card-scrapbook share-bg-{{ $cardBackground }} share-frame-ink">
                <x-share-dots-art />
                <header class="share-card-header">
                    <p class="share-card-kicker">{{ $event->event_type->label() }}</p>
                    <h1 class="share-card-title">{{ $event->title }}</h1>
                    <p class="share-card-meta">
                        {{ $event->event_date->format('Y年n月j日') }}（{{ $event->time_of_day->label() }}） · {{ $event->venue->name }}
                    </p>
                </header>

                @if ($scrapbookPhotos->isNotEmpty())
                    <div class="share-polaroids share-polaroids-{{ min(4, $scrapbookPhotos->count()) }}">
                        @foreach ($scrapbookPhotos as $photo)
                            <figure class="share-polaroid">
                                <div class="share-polaroid-photo">
                                    @if ($url = $photo->publicUrl())
                                        <img src="{{ $url }}" alt="{{ $photo->caption ?? $event->title }}" />
                                    @endif
                                </div>
                            </figure>
                        @endforeach
                    </div>
                @endif

                @if ($cardImpression !== '')
                    <p class="share-card-impression is-scrapbook">{{ $cardImpression }}</p>
                @endif

                <footer class="share-card-footer">
                    <span>OshiLog</span>
                    <span>RECORD YOUR OSHIKATSU DAYS.</span>
                </footer>
            </article>
        @endif
    </div>

    <p
        @class(['share-impression-status', 'share-impression-over' => $impressionOver])
        @if ($impressionOver)
            style="color: #dc2626; -webkit-text-fill-color: #dc2626;"
        @endif
    >
        @if ($impressionOver)
            ※文字数オーバーです
        @else
            {{ $impressionCount }} / {{ $impressionLimit }}文字
        @endif
    </p>

    <section class="mt-6 space-y-4">
        <div class="share-look-pickers">
            <div>
                <flux:heading size="sm" class="mb-2">メンカラ</flux:heading>
                <x-member-color-picker :show-label="false" />
            </div>
            <div>
                <flux:heading size="sm" class="mb-2">背景</flux:heading>
                <div class="share-look-row" role="group" aria-label="背景">
                    @foreach ($backgrounds as $value => $label)
                        <button
                            type="button"
                            wire:click="selectBackground('{{ $value }}')"
                            @class(['share-look-swatch', 'share-bg-'.$value, 'is-active' => $cardBackground === $value])
                            aria-label="背景：{{ $label }}"
                        >
                            @if ($value === \App\Support\ShareCard::BACKGROUND_DOTS)
                                <x-share-dots-art />
                            @endif
                            <span>{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between gap-2">
                <flux:heading size="sm">カードに載せる感想</flux:heading>
                <flux:button type="button" variant="ghost" size="sm" wire:click="restoreImpression">元の感想に戻す</flux:button>
            </div>
            <flux:text class="mb-2 text-sm">
                {{ $isTicket ? 'チケットの裏面' : 'スクラップブック' }}はおよそ{{ $impressionLimit }}文字までです。公演に保存した感想は変わりません。
            </flux:text>
            <flux:textarea wire:model.live="cardImpression" rows="4" label="感想" />
            <p class="share-impression-input-count">{{ $impressionCount }} / {{ $impressionLimit }}文字</p>
        </div>

        @if ($photos->isNotEmpty())
            @if ($isTicket)
                <div>
                    <flux:heading size="sm" class="mb-2">チケットの写真（1枚）</flux:heading>
                    <flux:text class="mb-3 text-sm">表面の背景になります。裏面に感想が並びます。</flux:text>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach ($photos as $photo)
                            <button
                                type="button"
                                wire:click="selectTicketPhoto('{{ $photo->id }}')"
                                @class([
                                    'share-photo-choice',
                                    'is-featured' => (string) $photo->id === $featuredPhotoId,
                                ])
                            >
                                @if ($url = $photo->publicUrl())
                                    <img src="{{ $url }}" alt="{{ $photo->caption ?? $photo->category->label() }}" />
                                @else
                                    <span class="share-photo-fallback">{{ $photo->category->label() }}</span>
                                @endif
                                @if ((string) $photo->id === $featuredPhotoId)
                                    <span class="share-photo-badge">選択中</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @else
                <div>
                    <flux:heading size="sm" class="mb-2">ポラロイド（最大4枚）</flux:heading>
                    <flux:text class="mb-3 text-sm">3〜4枚が目安です。同じ大きさで、切れずに並びます。</flux:text>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach ($photos as $photo)
                            <button
                                type="button"
                                wire:click="toggleScrapbookPhoto('{{ $photo->id }}')"
                                @class([
                                    'share-photo-choice',
                                    'is-extra' => in_array((string) $photo->id, $scrapbookPhotoIds, true),
                                ])
                            >
                                @if ($url = $photo->publicUrl())
                                    <img src="{{ $url }}" alt="{{ $photo->caption ?? $photo->category->label() }}" />
                                @else
                                    <span class="share-photo-fallback">{{ $photo->category->label() }}</span>
                                @endif
                                @if (in_array((string) $photo->id, $scrapbookPhotoIds, true))
                                    <span class="share-photo-badge">{{ array_search((string) $photo->id, $scrapbookPhotoIds, true) + 1 }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </section>
</div>

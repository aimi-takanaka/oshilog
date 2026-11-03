@props([
    'src',
    'alt' => '',
])

<div x-data="{ open: false }" class="contents">
    <button
        type="button"
        class="photo-thumb"
        x-on:click="open = true"
        aria-label="拡大して表示"
    >
        <img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes }} />
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition.opacity.duration.150ms
            x-on:keydown.escape.window="open = false"
            class="photo-lightbox"
            role="dialog"
            aria-modal="true"
            aria-label="{{ $alt !== '' ? $alt : '画像の拡大表示' }}"
        >
            <div class="photo-lightbox-stage" x-on:click="open = false">
                <div class="photo-lightbox-frame" x-on:click.stop>
                    <button
                        type="button"
                        class="photo-lightbox-close"
                        x-on:click="open = false"
                        aria-label="閉じる"
                    >閉じる</button>
                    <img src="{{ $src }}" alt="{{ $alt }}" />
                </div>
            </div>
        </div>
    </template>
</div>

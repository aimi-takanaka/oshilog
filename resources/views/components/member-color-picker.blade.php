@props([
    'showLabel' => true,
])

@php
    $colors = [
        'blue' => 'ブルー',
        'purple' => 'パープル',
        'pink' => 'ピンク',
        'green' => 'グリーン',
        'red' => 'レッド',
        'yellow' => 'イエロー',
        'orange' => 'オレンジ',
        'white' => 'ホワイト',
    ];
@endphp

<div class="member-color-picker" role="group" aria-label="メンカラ">
    @if ($showLabel)
        <span class="member-color-picker-label">メンカラ</span>
    @endif
    @foreach ($colors as $color => $label)
        <button
            type="button"
            class="member-color-swatch member-color-swatch-{{ $color }}"
            data-member-color-swatch="{{ $color }}"
            aria-label="{{ $label }}に変更"
            onclick="setOshiLogMemberColor('{{ $color }}')"
        ></button>
    @endforeach
</div>

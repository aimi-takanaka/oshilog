<?php

use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Title('設定')] class extends Component {
    //
}; ?>

<div class="flex flex-col items-start">
    @include('partials.settings-heading')

    <x-settings.layout heading="外観" subheading="画面の見た目を変更できます">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">ライト</flux:radio>
            <flux:radio value="dark" icon="moon">ダーク</flux:radio>
            <flux:radio value="system" icon="computer-desktop">システム</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</div>

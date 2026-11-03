<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] #[Title('OshiLog')] class extends Component {
}; ?>

<div class="mx-auto w-full max-w-4xl space-y-6 p-6">
    <section class="home-notebook p-6 sm:p-10">
        <div class="flex flex-col items-center text-center">
            <img src="{{ asset('img/logo/main.png') }}" alt="OshiLog" class="h-40 w-auto max-w-full object-contain sm:h-52" />
            <p class="home-catchphrase mt-2">RECORD YOUR OSHIKATSU DAYS.</p>
            <flux:heading size="xl" class="mt-4">推し活の思い出を、まるごと記録。</flux:heading>
            <flux:text class="mt-3 max-w-xl">
                行った場所・その日の天気・洋服・ネイル・感想など、推し活した日のすべてを1つのログに残せるアプリです。アメコミ風のレトロな雰囲気で、シンプルだけどワクワクする記録帳です。
            </flux:text>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                @guest
                    <flux:button :href="route('register')" variant="primary" wire:navigate>はじめる</flux:button>
                    <flux:button :href="route('login')" variant="ghost" wire:navigate>ログイン</flux:button>
                @else
                    <flux:button :href="route('events.create')" variant="primary" icon="plus" wire:navigate>公演を記録</flux:button>
                    <flux:button :href="route('events.index')" variant="ghost" wire:navigate>公演一覧へ</flux:button>
                @endguest
                <flux:button href="#how-to" variant="outline">使い方を見る</flux:button>
            </div>
        </div>
    </section>

    <section class="comic-panel p-6 sm:p-8">
        <flux:heading size="lg" class="text-center">1つのログに残せるもの</flux:heading>
        <flux:text class="mt-2 text-center">推し活の1日を、写真もメモもまとめて残せます。</flux:text>

        <div class="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-5">
            @foreach ([
                ['file' => 'icon-1.jpg', 'label' => '行った場所'],
                ['file' => 'icon-2.jpg', 'label' => 'その日の天気'],
                ['file' => 'icon-3.jpg', 'label' => '洋服の記録'],
                ['file' => 'icon-4.jpg', 'label' => 'ネイルの記録'],
                ['file' => 'icon-5.jpg', 'label' => '感想・メモ'],
            ] as $index => $feature)
                <div @class(['flex flex-col items-center gap-3 text-center', 'col-span-2 sm:col-span-1' => $index === 4])>
                    <span class="home-feature-icon">
                        <img src="{{ asset('img/logo/'.$feature['file']) }}" alt="{{ $feature['label'] }}" />
                    </span>
                    <flux:text class="font-bold">{{ $feature['label'] }}</flux:text>
                </div>
            @endforeach
        </div>
    </section>

    <section id="how-to" class="comic-panel scroll-mt-24 p-6 sm:p-8 lg:scroll-mt-6">
        <flux:heading size="lg" class="text-center">使い方</flux:heading>
        <flux:text class="mt-2 text-center">会場を登録して、公演の1日をログに残していきましょう。</flux:text>

        <ol class="mt-8 space-y-4">
            <li class="flex gap-4 rounded-xl border-[3px] border-black bg-white p-4">
                <span class="home-step">1</span>
                <div>
                    <flux:text class="font-bold">会場を登録する</flux:text>
                    <flux:text class="mt-1 text-sm">ライブ会場やロケ地の住所・最寄り駅・アクセスを残します。周辺スポットも一緒にメモできます。</flux:text>
                </div>
            </li>
            <li class="flex gap-4 rounded-xl border-[3px] border-black bg-white p-4">
                <span class="home-step">2</span>
                <div>
                    <flux:text class="font-bold">公演を記録する</flux:text>
                    <flux:text class="mt-1 text-sm">日付と会場を選ぶと天気が入ります。同行者や感想も、その日のログとして残せます。</flux:text>
                </div>
            </li>
            <li class="flex gap-4 rounded-xl border-[3px] border-black bg-white p-4">
                <span class="home-step">3</span>
                <div>
                    <flux:text class="font-bold">写真と持ち物を残す</flux:text>
                    <flux:text class="mt-1 text-sm">服装・ネイル・うちわなどの写真を保存。ライブ・舞台・ミュージカル・イベント・ロケ地ごとの持ち物リストで当日の準備もできます。</flux:text>
                </div>
            </li>
            <li class="flex gap-4 rounded-xl border-[3px] border-black bg-white p-4">
                <span class="home-step">4</span>
                <div>
                    <flux:text class="font-bold">タイムラインで振り返る</flux:text>
                    <flux:text class="mt-1 text-sm">公演一覧で月ごとに思い出を見返せます。推し活の記録帳として、何度でも開いてください。</flux:text>
                </div>
            </li>
        </ol>
    </section>

    <section class="comic-panel p-6 sm:p-8">
        <flux:heading size="lg" class="text-center">メンカラにチェンジ！</flux:heading>
        <flux:text class="mt-2 text-center">推しのカラーに変更すると、かわいい雰囲気に早変わり！</flux:text>

        <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <button type="button" class="home-member-notebook home-member-notebook-blue" onclick="setOshiLogMemberColor('blue')">
                <span>Blue</span>
            </button>
            <button type="button" class="home-member-notebook home-member-notebook-purple" onclick="setOshiLogMemberColor('purple')">
                <span>Purple</span>
            </button>
            <button type="button" class="home-member-notebook home-member-notebook-pink" onclick="setOshiLogMemberColor('pink')">
                <span>Pink</span>
            </button>
            <button type="button" class="home-member-notebook home-member-notebook-green" onclick="setOshiLogMemberColor('green')">
                <span>Green</span>
            </button>
            <button type="button" class="home-member-notebook home-member-notebook-red" onclick="setOshiLogMemberColor('red')">
                <span>Red</span>
            </button>
            <button type="button" class="home-member-notebook home-member-notebook-yellow" onclick="setOshiLogMemberColor('yellow')">
                <span>Yellow</span>
            </button>
            <button type="button" class="home-member-notebook home-member-notebook-orange" onclick="setOshiLogMemberColor('orange')">
                <span>Orange</span>
            </button>
            <button type="button" class="home-member-notebook home-member-notebook-white" onclick="setOshiLogMemberColor('white')">
                <span>White</span>
            </button>
        </div>
    </section>

    <section class="flex flex-col items-center gap-4 pb-4 text-center">
        <p class="home-wordmark">OshiLog <span>オシログ</span></p>
        <div class="flex flex-wrap justify-center gap-3">
            @guest
                <flux:button :href="route('register')" variant="primary" wire:navigate>無料で始める</flux:button>
                <flux:button :href="route('login')" variant="outline" wire:navigate>ログイン</flux:button>
            @else
                <flux:button :href="route('events.create')" variant="primary" icon="plus" wire:navigate>はじめての公演を記録</flux:button>
                <flux:button :href="route('venues.create')" variant="outline" icon="plus" wire:navigate>会場を登録</flux:button>
            @endguest
        </div>
    </section>
</div>

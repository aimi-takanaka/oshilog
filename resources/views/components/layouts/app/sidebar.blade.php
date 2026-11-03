<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="comic-theme" data-member-color="blue">
    <head>
        @include('partials.head')
    </head>
    <body class="comic-body min-h-screen">
        <flux:sidebar sticky stashable class="comic-sidebar border-r-4 border-black">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('home') }}" class="comic-logo-banner" wire:navigate>
                <x-app-logo-icon />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group heading="メニュー" class="grid">
                    <flux:navlist.item icon="home" :href="route('home')" :current="request()->routeIs('home')" wire:navigate>トップ</flux:navlist.item>
                    <flux:navlist.item icon="calendar-days" :href="route('events.index')" :current="request()->routeIs('events.*')" wire:navigate>公演一覧</flux:navlist.item>
                    <flux:navlist.item icon="building-office" :href="route('venues.index')" :current="request()->routeIs('venues.index', 'venues.show', 'venues.edit')" wire:navigate>会場一覧</flux:navlist.item>
                    <flux:navlist.item icon="map-pin" :href="route('venues.presets')" :current="request()->routeIs('venues.presets')" wire:navigate>定番会場</flux:navlist.item>
                    <flux:navlist.item icon="plus" :href="route('venues.create')" :current="request()->routeIs('venues.create')" wire:navigate>会場を登録する</flux:navlist.item>
                    <flux:navlist.item icon="briefcase" :href="route('belongings.index')" :current="request()->routeIs('belongings.*')" wire:navigate>持ち物</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                @auth
                    <flux:navlist.item icon="plus" :href="route('events.create')" wire:navigate>公演を記録</flux:navlist.item>
                    <flux:navlist.item icon="cog" :href="route('settings.profile')" wire:navigate>設定</flux:navlist.item>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:navlist.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">ログアウト</flux:navlist.item>
                    </form>
                @else
                    <flux:navlist.item icon="arrow-right-end-on-rectangle" :href="route('login')" wire:navigate>ログイン</flux:navlist.item>
                    <flux:navlist.item icon="user-plus" :href="route('register')" wire:navigate>新規登録</flux:navlist.item>
                @endauth
            </flux:navlist>
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
        <script>
            window.localStorage.setItem('flux.appearance', 'light');
            if (window.Flux && typeof window.Flux.applyAppearance === 'function') {
                window.Flux.applyAppearance('light');
            }
            document.documentElement.classList.remove('dark');
            document.documentElement.style.colorScheme = 'light';
        </script>
    </body>
</html>

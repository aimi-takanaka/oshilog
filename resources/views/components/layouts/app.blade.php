<x-layouts.app.sidebar>
    <flux:header class="comic-mobile-header lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
    </flux:header>

    <flux:main>
        <div class="px-4 pt-4 sm:px-6">
            <x-member-color-picker />
        </div>
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>

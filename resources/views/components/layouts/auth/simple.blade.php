<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="comic-theme" data-member-color="blue">
    <head>
        @include('partials.head')
    </head>
    <body class="comic-body comic-auth min-h-screen">
        <div class="flex min-h-svh flex-col items-center justify-center gap-8 p-4 sm:p-8">
            <div class="comic-auth-shell flex w-full flex-col gap-6">
                <a href="{{ route('home') }}" class="comic-logo-banner comic-auth-logo" wire:navigate>
                    <x-app-logo-icon />
                </a>
                <div class="comic-panel comic-auth-panel">
                    {{ $slot }}
                </div>
            </div>
        </div>
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

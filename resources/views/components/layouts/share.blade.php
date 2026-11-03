<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="comic-theme" data-member-color="blue">
    <head>
        @include('partials.head')
    </head>
    <body class="comic-body min-h-screen">
        <div class="share-card-page">
            {{ $slot }}
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

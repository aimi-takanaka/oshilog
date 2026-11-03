<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="color-scheme" content="light">

<title>{{ $title ?? 'OshiLog' }}</title>

<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}" sizes="any">
<link rel="icon" type="image/png" href="{{ asset('img/logo/favicon-32.png') }}?v={{ filemtime(public_path('img/logo/favicon-32.png')) }}">
<link rel="apple-touch-icon" href="{{ asset('img/logo/apple-touch-icon.png') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=bangers:400|comic-neue:400,700" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
<script>
    window.localStorage.setItem('flux.appearance', 'light');
</script>
@fluxAppearance
<style>
    html, :root, :root.dark, html.dark {
        color-scheme: light !important;
    }
</style>
<script>
    (() => {
        const applyLight = () => {
            document.documentElement.classList.remove('dark');
            document.documentElement.style.colorScheme = 'light';
        };

        if (window.Flux && typeof window.Flux.applyAppearance === 'function') {
            const original = window.Flux.applyAppearance.bind(window.Flux);
            window.Flux.applyAppearance = () => original('light');
        }

        applyLight();
        window.localStorage.setItem('flux.appearance', 'light');

        const stored = localStorage.getItem('oshilog-member-color');
        const allowed = ['blue', 'purple', 'pink', 'green', 'red', 'yellow', 'orange', 'white'];
        document.documentElement.dataset.memberColor = allowed.includes(stored) ? stored : 'blue';
    })();
</script>

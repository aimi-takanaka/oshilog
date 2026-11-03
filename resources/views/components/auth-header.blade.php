@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-3 text-center">
    <h1 class="comic-auth-title">{{ $title }}</h1>
    <p class="comic-auth-description">{{ $description }}</p>
</div>

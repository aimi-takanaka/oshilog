<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Venue;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        File::ensureDirectoryExists(public_path('storage'));
        File::ensureDirectoryExists(storage_path('app/private/livewire-tmp'));

        app()->setLocale('ja');

        if ($root = config('app.url')) {
            URL::forceRootUrl($root);
        }

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Route::bind('event', fn (string $value) => Event::query()
            ->forCurrentUser()
            ->whereKey($value)
            ->firstOrFail());

        Route::bind('venue', fn (string $value) => Venue::query()
            ->forCurrentUser()
            ->whereKey($value)
            ->firstOrFail());
    }
}

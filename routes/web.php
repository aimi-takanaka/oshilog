<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'home')->name('home');

Route::middleware(['auth'])->group(function () {
    Volt::route('events', 'events.index')->name('events.index');
    Volt::route('events/create', 'events.form')->name('events.create');
    Volt::route('events/{event}/card', 'events.card')->name('events.card');
    Volt::route('events/{event}', 'events.show')->name('events.show');
    Volt::route('events/{event}/edit', 'events.form')->name('events.edit');

    Volt::route('venues', 'venues.index')->name('venues.index');
    Volt::route('venues/create', 'venues.form')->name('venues.create');
    Volt::route('venues/presets', 'venues.presets')->name('venues.presets');
    Volt::route('venues/{venue}', 'venues.show')->name('venues.show');
    Volt::route('venues/{venue}/edit', 'venues.form')->name('venues.edit');

    Volt::route('belongings', 'belongings.index')->name('belongings.index');

    Route::get('dashboard', fn () => redirect()->route('events.index'))->name('dashboard');

    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';

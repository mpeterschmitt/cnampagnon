<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Volt::route('/', 'home')->name('home');

// Route pour les liens de redirection courts
Route::get('s/{code}', [RedirectController::class, 'handle'])->name('redirect.handle');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Route pour l'emploi du temps (accessible aux utilisateurs authentifiés)
Volt::route('schedule', 'schedule.index')
    ->middleware(['auth'])
    ->name('schedule.index');

// Routes pour les devoirs (accessible aux utilisateurs authentifiés)
Volt::route('homeworks', 'homeworks.index')
    ->middleware(['auth'])
    ->name('homeworks.index');

Volt::route('homeworks/create', 'homeworks.create')
    ->middleware(['auth'])
    ->name('homeworks.create');

Volt::route('homeworks/{homework}/edit', 'homeworks.create')
    ->middleware(['auth'])
    ->name('homeworks.edit');

// Route pour l'export ICS
Route::get('schedule/export/ics', [ExportController::class, 'exportIcs'])
    ->name('schedule.export.ics');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

// Routes administrateur (protégées par middleware admin)
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::redirect('/', 'admin/users');

    Volt::route('users', 'admin.users')->name('admin.users');
    Volt::route('redirects', 'admin.redirects')->name('admin.redirects');
    Volt::route('import-ics', 'admin.import-ics')->name('admin.import-ics');
    Volt::route('import-pdf', 'admin.import-pdf')->name('admin.import-pdf');
});

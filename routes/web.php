<?php

use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Volt::route('/', 'home')->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Route pour l'emploi du temps (accessible aux utilisateurs authentifiés)
Volt::route('schedule', 'schedule.index')
    ->middleware(['auth'])
    ->name('schedule.index');

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
    Volt::route('import-ics', 'admin.import-ics')->name('admin.import-ics');
    Volt::route('import-pdf', 'admin.import-pdf')->name('admin.import-pdf');
});

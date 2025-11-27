<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Volt\Volt;

/**
 * Tests pour la page Emploi du Temps
 *
 * Ces tests vérifient que la structure de base de la page fonctionne correctement.
 * Au fur et à mesure que des fonctionnalités seront ajoutées (affichage des cours,
 * filtres, gestion des modifications), de nouveaux tests devront être ajoutés.
 */

// Test d'accès à la page (authentification requise)
test('unauthenticated users cannot access schedule page', function () {
    $this->get(route('schedule.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can access schedule page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSuccessful()
        ->assertSeeLivewire('schedule.index');
});

// Test de la structure de base de la page
test('schedule page displays main sections', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSee('Emploi du Temps')
        ->assertSee('Planning hebdomadaire des cours et activités')
        ->assertSee('Filtres')
        ->assertSee('Changements de dernière minute')
        ->assertSee('Légende');
});

// Test du composant Volt
test('schedule component initializes with correct default state', function () {
    $user = User::factory()->create();

    $component = Volt::actingAs($user)->test('schedule.index');

    expect($component->selectedSubject)->toBeNull();
    expect($component->selectedTeacher)->toBeNull();
    expect($component->selectedCourseType)->toBeNull();
    expect($component->viewMode)->toBe('week');
});

test('schedule component displays current week by default', function () {
    $user = User::factory()->create();

    $component = Volt::actingAs($user)->test('schedule.index');

    expect($component->selectedWeek->format('d/m/Y'))->toBe(now()->startOfWeek()->format('d/m/Y'));
});

// Test de navigation entre les semaines
test('user can navigate to previous week', function () {
    $user = User::factory()->create();

    $previousWeekStart = now()->startOfWeek()->subWeek();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('previousWeek')
        ->assertSee($previousWeekStart->format('d/m/Y'));
});

test('user can navigate to next week', function () {
    $user = User::factory()->create();

    $nextWeekStart = now()->startOfWeek()->addWeek();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('nextWeek')
        ->assertSee($nextWeekStart->format('d/m/Y'));
});

test('user can return to current week', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('nextWeek')
        ->call('nextWeek')
        ->call('currentWeek')
        ->assertSee(now()->startOfWeek()->format('d/m/Y'));
});

// Test des filtres
test('user can filter by course type', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->set('selectedCourseType', 'cm')
        ->assertSet('selectedCourseType', 'cm');
});

test('user can clear all filters', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->set('selectedCourseType', 'cm')
        ->set('selectedSubject', 'math')
        ->set('selectedTeacher', '1')
        ->call('clearFilters')
        ->assertSet('selectedCourseType', null)
        ->assertSet('selectedSubject', null)
        ->assertSet('selectedTeacher', null);
});

// Test de l'affichage des jours de la semaine
test('schedule displays all weekdays', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSee(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], false);
});

// Test de la légende
test('schedule displays legend with course types', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSee('Cours Magistral (CM)')
        ->assertSee('Travaux Dirigés (TD)')
        ->assertSee('Travaux Pratiques (TP)');
});

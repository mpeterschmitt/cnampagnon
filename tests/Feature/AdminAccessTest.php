<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Tests pour l'accès aux pages d'administration
 */

// Test d'accès - Utilisateurs non authentifiés
test('unauthenticated users cannot access admin pages', function () {
    $this->get(route('admin.users'))
        ->assertRedirect(route('login'));

    $this->get(route('admin.import-ics'))
        ->assertRedirect(route('login'));

    $this->get(route('admin.import-pdf'))
        ->assertRedirect(route('login'));
});

// Test d'accès - Utilisateurs non-admin
test('regular users cannot access admin pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.import-ics'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.import-pdf'))
        ->assertForbidden();
});

// Test d'accès - Administrateurs
test('admin users can access admin pages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertSuccessful()
        ->assertSeeLivewire('admin.users');

    $this->actingAs($admin)
        ->get(route('admin.import-ics'))
        ->assertSuccessful()
        ->assertSeeLivewire('admin.import-ics');

    $this->actingAs($admin)
        ->get(route('admin.import-pdf'))
        ->assertSuccessful()
        ->assertSeeLivewire('admin.import-pdf');
});

// Test de la navigation - Les liens admin n'apparaissent que pour les admins
test('admin navigation links only visible to admins', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    // Utilisateur normal ne voit pas les liens admin
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertDontSee('Administration')
        ->assertDontSee('Utilisateurs')
        ->assertDontSee('Import ICS')
        ->assertDontSee('Import PDF');

    // Admin voit les liens admin
    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSee('Administration');
});

// Test du contenu des pages admin
test('admin users page displays user management interface', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertSee('Gestion des Utilisateurs')
        ->assertSee('Total Utilisateurs')
        ->assertSee('Administrateurs');
});

test('admin import ics page displays import interface', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.import-ics'))
        ->assertSee('Importer depuis ICS')
        ->assertSee('Choisir un fichier');
});

test('admin import pdf page displays import interface', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.import-pdf'))
        ->assertSee('Importer depuis PDF')
        ->assertSee('Extraction automatique avec OCR');
});

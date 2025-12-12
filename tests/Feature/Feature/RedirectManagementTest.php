<?php

declare(strict_types=1);

use App\Models\Redirect;
use App\Models\User;
use Livewire\Livewire;

/**
 * Tests pour la gestion des liens de redirection
 */

// Test d'accès - Utilisateurs non authentifiés
test('unauthenticated users cannot access admin redirects page', function () {
    $this->get(route('admin.redirects'))
        ->assertRedirect(route('login'));
});

// Test d'accès - Utilisateurs non-admin
test('regular users cannot access admin redirects page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.redirects'))
        ->assertForbidden();
});

// Test d'accès - Administrateurs
test('admin users can access admin redirects page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.redirects'))
        ->assertSuccessful();
});

// Test affichage des redirects
test('admin can view all redirects', function () {
    $admin = User::factory()->admin()->create();
    $redirects = Redirect::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.redirects'))
        ->assertSee($redirects[0]->code)
        ->assertSee($redirects[1]->code)
        ->assertSee($redirects[2]->code);
});

// Test création de redirect
test('admin can create a new redirect', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->set('form.code', 'test-link')
        ->set('form.url', 'https://example.com')
        ->set('form.title', 'Test Link')
        ->set('form.is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Redirect::where('code', 'test-link')->exists())->toBeTrue();

    $redirect = Redirect::where('code', 'test-link')->first();
    expect($redirect->url)->toBe('https://example.com');
    expect($redirect->title)->toBe('Test Link');
    expect($redirect->is_active)->toBeTrue();
    expect($redirect->created_by)->toBe($admin->id);
});

// Test modification de redirect
test('admin can update an existing redirect', function () {
    $admin = User::factory()->admin()->create();
    $redirect = Redirect::factory()->create([
        'code' => 'old-code',
        'url' => 'https://old.com',
    ]);

    Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->call('openEditModal', $redirect->id)
        ->set('form.code', 'new-code')
        ->set('form.url', 'https://new.com')
        ->call('save')
        ->assertHasNoErrors();

    $redirect->refresh();
    expect($redirect->code)->toBe('new-code');
    expect($redirect->url)->toBe('https://new.com');
});

// Test suppression de redirect
test('admin can delete a redirect', function () {
    $admin = User::factory()->admin()->create();
    $redirect = Redirect::factory()->create();

    Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->call('deleteRedirect', $redirect->id);

    expect(Redirect::find($redirect->id))->toBeNull();
});

// Test validation du code
test('redirect code must be unique', function () {
    $admin = User::factory()->admin()->create();
    Redirect::factory()->create(['code' => 'existing-code']);

    Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->set('form.code', 'existing-code')
        ->set('form.url', 'https://example.com')
        ->call('save')
        ->assertHasErrors(['form.code']);
});

// Test validation du code - format
test('redirect code must contain only alphanumeric characters, dashes and underscores', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->set('form.code', 'invalid code!')
        ->set('form.url', 'https://example.com')
        ->call('save')
        ->assertHasErrors(['form.code']);
});

// Test validation de l'URL
test('redirect url must be valid', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->set('form.code', 'test')
        ->set('form.url', 'not-a-valid-url')
        ->call('save')
        ->assertHasErrors(['form.url']);
});

// Test validation - champs requis
test('redirect code and url are required', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->set('form.code', '')
        ->set('form.url', '')
        ->call('save')
        ->assertHasErrors(['form.code', 'form.url']);
});

// Test basculer le statut
test('admin can toggle redirect status', function () {
    $admin = User::factory()->admin()->create();
    $redirect = Redirect::factory()->create(['is_active' => true]);

    Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->call('toggleStatus', $redirect->id);

    $redirect->refresh();
    expect($redirect->is_active)->toBeFalse();

    Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->call('toggleStatus', $redirect->id);

    $redirect->refresh();
    expect($redirect->is_active)->toBeTrue();
});

// Test recherche
test('admin can search redirects by code', function () {
    $admin = User::factory()->admin()->create();
    Redirect::factory()->create(['code' => 'findme']);
    Redirect::factory()->create(['code' => 'other']);

    Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->set('search', 'findme')
        ->assertSee('findme')
        ->assertDontSee('other');
});

// Test filtrage par statut
test('admin can filter redirects by status', function () {
    $admin = User::factory()->admin()->create();
    $active = Redirect::factory()->create(['is_active' => true]);
    $inactive = Redirect::factory()->inactive()->create();

    Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->set('filterStatus', 'active')
        ->assertSee($active->code)
        ->assertDontSee($inactive->code);

    Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->set('filterStatus', 'inactive')
        ->assertSee($inactive->code)
        ->assertDontSee($active->code);
});

// Test génération de code aléatoire
test('admin can generate random code', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)
        ->test('admin.redirects')
        ->call('generateRandomCode');

    expect($component->form['code'])->not->toBeEmpty();
    expect(strlen($component->form['code']))->toBe(6);
});

// Test statistiques
test('admin can view redirect statistics', function () {
    $admin = User::factory()->admin()->create();
    Redirect::factory()->count(5)->create(['is_active' => true]);
    Redirect::factory()->count(2)->inactive()->create();
    Redirect::factory()->withClicks(10)->create();

    $component = Livewire::actingAs($admin)
        ->test('admin.redirects');

    expect($component->stats['total'])->toBe(8);
    expect($component->stats['active'])->toBe(6);
    expect($component->stats['inactive'])->toBe(2);
    expect($component->stats['total_clicks'])->toBeGreaterThan(0);
});

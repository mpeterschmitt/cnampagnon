<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Volt\Volt;

/**
 * Tests pour la logique de gestion des utilisateurs (Admin)
 */

// Test des statistiques
test('admin users page displays correct statistics', function () {
    // Créer quelques utilisateurs
    User::factory()->admin()->count(2)->create();
    User::factory()->count(5)->create(['email_verified_at' => now()]);
    User::factory()->count(3)->create(['email_verified_at' => null]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertSee('11') // Total (2+5+3+1)
        ->assertSee('3'); // Admins (2+1)
});

// Test de la recherche
test('admin can search users by name', function () {
    $admin = User::factory()->admin()->create();
    $user1 = User::factory()->create(['name' => 'Jean Dupont']);
    $user2 = User::factory()->create(['name' => 'Marie Martin']);

    Volt::actingAs($admin)
        ->test('admin.users')
        ->set('search', 'Jean')
        ->assertSee('Jean Dupont')
        ->assertDontSee('Marie Martin');
});

test('admin can search users by email', function () {
    $admin = User::factory()->admin()->create();
    $user1 = User::factory()->create(['email' => 'jean@test.com']);
    $user2 = User::factory()->create(['email' => 'marie@test.com']);

    Volt::actingAs($admin)
        ->test('admin.users')
        ->set('search', 'jean@test')
        ->assertSee('jean@test.com')
        ->assertDontSee('marie@test.com');
});

// Test des filtres
test('admin can filter by admin role', function () {
    $admin = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create(['name' => 'Admin User']);
    $user = User::factory()->create(['name' => 'Regular User']);

    Volt::actingAs($admin)
        ->test('admin.users')
        ->set('filterRole', 'admin')
        ->assertSee('Admin User')
        ->assertDontSee('Regular User');
});

test('admin can filter by user role', function () {
    $admin = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create(['name' => 'Admin User']);
    $user = User::factory()->create(['name' => 'Regular User']);

    Volt::actingAs($admin)
        ->test('admin.users')
        ->set('filterRole', 'user')
        ->assertSee('Regular User')
        ->assertDontSee('Admin User');
});

// Test du tri
test('admin can sort users by name', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'Zoe']);
    User::factory()->create(['name' => 'Alice']);

    $component = Volt::actingAs($admin)->test('admin.users');

    $component->call('changeSortBy', 'name');
    expect($component->sortBy)->toBe('name');
    expect($component->sortDirection)->toBe('asc');
});

test('sorting same column toggles direction', function () {
    $admin = User::factory()->admin()->create();

    $component = Volt::actingAs($admin)->test('admin.users');

    $component->set('sortBy', 'name');
    $component->set('sortDirection', 'asc');

    $component->call('changeSortBy', 'name');
    expect($component->sortDirection)->toBe('desc');
});

// Test de promotion/révocation admin
test('admin can promote user to admin', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_admin' => false]);

    Volt::actingAs($admin)
        ->test('admin.users')
        ->call('toggleAdmin', $user->id);

    expect($user->fresh()->is_admin)->toBeTrue();
});

test('admin can revoke admin privileges', function () {
    $admin1 = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create();

    Volt::actingAs($admin1)
        ->test('admin.users')
        ->call('toggleAdmin', $admin2->id);

    expect($admin2->fresh()->is_admin)->toBeFalse();
});

test('admin cannot revoke their own admin privileges', function () {
    $admin = User::factory()->admin()->create();

    Volt::actingAs($admin)
        ->test('admin.users')
        ->call('toggleAdmin', $admin->id)
        ->assertDispatched('error');

    expect($admin->fresh()->is_admin)->toBeTrue();
});

test('cannot revoke last admin', function () {
    $admin = User::factory()->admin()->create();

    Volt::actingAs($admin)
        ->test('admin.users')
        ->call('toggleAdmin', $admin->id)
        ->assertDispatched('error');

    expect($admin->fresh()->is_admin)->toBeTrue();
});

// Test de suppression
test('admin can delete regular user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    Volt::actingAs($admin)
        ->test('admin.users')
        ->call('deleteUser', $user->id)
        ->assertDispatched('success');

    expect(User::find($user->id))->toBeNull();
});

test('admin cannot delete themselves', function () {
    $admin = User::factory()->admin()->create();

    Volt::actingAs($admin)
        ->test('admin.users')
        ->call('deleteUser', $admin->id)
        ->assertDispatched('error');

    expect(User::find($admin->id))->not->toBeNull();
});

test('cannot delete last admin', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    // Try to delete the only admin
    Volt::actingAs($admin)
        ->test('admin.users')
        ->call('deleteUser', $admin->id)
        ->assertDispatched('error');

    expect(User::find($admin->id))->not->toBeNull();
});

// Test de renvoi d'email de vérification
test('admin can resend verification email to unverified user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email_verified_at' => null]);

    Volt::actingAs($admin)
        ->test('admin.users')
        ->call('resendVerification', $user->id)
        ->assertDispatched('success');
});

test('cannot resend verification to already verified user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);

    Volt::actingAs($admin)
        ->test('admin.users')
        ->call('resendVerification', $user->id)
        ->assertDispatched('error');
});

// Test de pagination
test('users are paginated', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(20)->create();

    $component = Volt::actingAs($admin)->test('admin.users');

    expect($component->users)->toHaveCount(15); // Default pagination is 15
});

<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Volt\Volt;

test('home page is accessible', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSeeLivewire('home');
});

test('home page displays welcome content', function () {
    $this->get(route('home'))
        ->assertSee('Bienvenue sur la plateforme de promotion')
        ->assertSee('Votre espace dédié pour collaborer')
        ->assertSee('Accéder à SharePoint');
});

test('home page displays features section', function () {
    $this->get(route('home'))
        ->assertSee('Fonctionnalités à venir')
        ->assertSee('Réseau')
        ->assertSee('Ressources')
        ->assertSee('Événements');
});

test('guest users see authentication links', function () {
    $this->get(route('home'))
        ->assertSee('Se connecter')
        ->assertSee('S\'inscrire');
});

test('authenticated users see dashboard link', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSee('Dashboard')
        ->assertDontSee('S\'inscrire');
});

test('sharepoint button has correct link', function () {
    Volt::test('home')
        ->assertSee('Accéder à SharePoint')
        ->assertSee('https://sharepoint.example.com/placeholder');
});

<?php

declare(strict_types=1);

use App\Models\Redirect;

/**
 * Tests pour le système de redirection public
 */

// Test redirection réussie
test('active redirect redirects to target url', function () {
    $redirect = Redirect::factory()->create([
        'code' => 'test',
        'url' => 'https://example.com',
        'is_active' => true,
    ]);

    $response = $this->get('/s/test');

    $response->assertRedirect('https://example.com');
});

// Test incrémentation des clics
test('redirect increments click counter', function () {
    $redirect = Redirect::factory()->create([
        'code' => 'test',
        'url' => 'https://example.com',
        'clicks' => 0,
    ]);

    expect($redirect->clicks)->toBe(0);

    $this->get('/s/test');

    $redirect->refresh();
    expect($redirect->clicks)->toBe(1);

    $this->get('/s/test');

    $redirect->refresh();
    expect($redirect->clicks)->toBe(2);
});

// Test redirect inactif
test('inactive redirect returns 404', function () {
    Redirect::factory()->inactive()->create([
        'code' => 'inactive',
        'url' => 'https://example.com',
    ]);

    $this->get('/s/inactive')
        ->assertNotFound();
});

// Test code inexistant
test('non-existent code returns 404', function () {
    $this->get('/s/non-existent')
        ->assertNotFound();
});

// Test avec différents formats de codes
test('redirect works with various code formats', function () {
    $codes = [
        'simple',
        'with-dashes',
        'with_underscores',
        'MixedCase123',
        'a1b2c3',
    ];

    foreach ($codes as $code) {
        $redirect = Redirect::factory()->create([
            'code' => $code,
            'url' => 'https://example.com',
        ]);

        $this->get("/s/{$code}")
            ->assertRedirect('https://example.com');
    }
});

// Test avec URLs de différents types
test('redirect works with various url types', function () {
    $urls = [
        'https://example.com',
        'https://example.com/path',
        'https://example.com/path?query=value',
        'https://example.com/path#anchor',
        'https://subdomain.example.com',
        'http://example.com',
    ];

    foreach ($urls as $index => $url) {
        $redirect = Redirect::factory()->create([
            'code' => "test{$index}",
            'url' => $url,
        ]);

        $this->get("/s/test{$index}")
            ->assertRedirect($url);
    }
});

// Test que les redirects ne nécessitent pas d'authentification
test('redirect works for unauthenticated users', function () {
    $redirect = Redirect::factory()->create([
        'code' => 'public',
        'url' => 'https://example.com',
    ]);

    $this->get('/s/public')
        ->assertRedirect('https://example.com');
});

// Test méthode incrementClicks du modèle
test('incrementClicks method works correctly', function () {
    $redirect = Redirect::factory()->create(['clicks' => 5]);

    expect($redirect->clicks)->toBe(5);

    $redirect->incrementClicks();
    $redirect->refresh();

    expect($redirect->clicks)->toBe(6);
});

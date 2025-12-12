<?php

declare(strict_types=1);

use App\Models\Redirect;
use App\Models\RedirectClick;
use App\Models\User;

/**
 * Tests pour le suivi des clics sur les redirections
 */

// Test enregistrement de clic pour utilisateur authentifié
test('redirect records click for authenticated user', function () {
    $user = User::factory()->create();
    $redirect = Redirect::factory()->create(['code' => 'test', 'clicks' => 0]);

    $this->actingAs($user)
        ->get('/s/test')
        ->assertRedirect();

    expect(RedirectClick::count())->toBe(1);

    $click = RedirectClick::first();
    expect($click->redirect_id)->toBe($redirect->id);
    expect($click->user_id)->toBe($user->id);
    expect($click->ip_address)->not->toBeNull();
    expect($click->clicked_at)->not->toBeNull();
});

// Test enregistrement de clic pour utilisateur anonyme
test('redirect records click for anonymous user', function () {
    $redirect = Redirect::factory()->create(['code' => 'test', 'clicks' => 0]);

    $this->get('/s/test')
        ->assertRedirect();

    expect(RedirectClick::count())->toBe(1);

    $click = RedirectClick::first();
    expect($click->redirect_id)->toBe($redirect->id);
    expect($click->user_id)->toBeNull();
    expect($click->ip_address)->not->toBeNull();
});

// Test enregistrement de l'adresse IP
test('redirect records ip address', function () {
    $redirect = Redirect::factory()->create(['code' => 'test']);

    $this->get('/s/test', ['REMOTE_ADDR' => '192.168.1.100'])
        ->assertRedirect();

    $click = RedirectClick::first();
    expect($click->ip_address)->toBe('192.168.1.100');
});

// Test enregistrement du user agent
test('redirect records user agent', function () {
    $redirect = Redirect::factory()->create(['code' => 'test']);

    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 Test Browser'])
        ->get('/s/test')
        ->assertRedirect();

    $click = RedirectClick::first();
    expect($click->user_agent)->toBe('Mozilla/5.0 Test Browser');
});

// Test enregistrement du referrer
test('redirect records referer', function () {
    $redirect = Redirect::factory()->create(['code' => 'test']);

    $this->withHeaders(['Referer' => 'https://google.com'])
        ->get('/s/test')
        ->assertRedirect();

    $click = RedirectClick::first();
    expect($click->referer)->toBe('https://google.com');
});

// Test compteur de clics incrémenté
test('redirect counter is incremented with each click', function () {
    $redirect = Redirect::factory()->create(['code' => 'test', 'clicks' => 5]);

    $this->get('/s/test');
    $redirect->refresh();
    expect($redirect->clicks)->toBe(6);

    $this->get('/s/test');
    $redirect->refresh();
    expect($redirect->clicks)->toBe(7);
});

// Test multiples clics par le même utilisateur
test('multiple clicks by same user are tracked separately', function () {
    $user = User::factory()->create();
    $redirect = Redirect::factory()->create(['code' => 'test']);

    $this->actingAs($user)->get('/s/test');
    $this->actingAs($user)->get('/s/test');
    $this->actingAs($user)->get('/s/test');

    expect(RedirectClick::where('user_id', $user->id)->count())->toBe(3);
    expect($redirect->fresh()->clicks)->toBe(3);
});

// Test clics de différents utilisateurs
test('clicks from different users are tracked separately', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $redirect = Redirect::factory()->create(['code' => 'test']);

    $this->actingAs($user1)->get('/s/test');

    auth()->logout();
    $this->actingAs($user2)->get('/s/test');

    auth()->logout();
    $this->get('/s/test'); // Anonymous

    expect(RedirectClick::count())->toBe(3);
    expect(RedirectClick::where('user_id', $user1->id)->count())->toBe(1);
    expect(RedirectClick::where('user_id', $user2->id)->count())->toBe(1);
    expect(RedirectClick::whereNull('user_id')->count())->toBe(1);
});

// Test relation redirect -> clicks
test('redirect has many click records relationship', function () {
    $redirect = Redirect::factory()->create(['code' => 'test']);

    $this->get('/s/test');
    $this->get('/s/test');

    $redirect->refresh();
    expect($redirect->clickRecords)->toHaveCount(2);
    expect($redirect->clickRecords->first())->toBeInstanceOf(RedirectClick::class);
});

// Test relation click -> user
test('click belongs to user relationship', function () {
    $user = User::factory()->create();
    $redirect = Redirect::factory()->create(['code' => 'test']);

    $this->actingAs($user)->get('/s/test');

    $click = RedirectClick::first();
    expect($click->user)->toBeInstanceOf(User::class);
    expect($click->user->id)->toBe($user->id);
});

// Test relation click -> redirect
test('click belongs to redirect relationship', function () {
    $redirect = Redirect::factory()->create(['code' => 'test']);

    $this->get('/s/test');

    $click = RedirectClick::first();
    expect($click->redirect)->toBeInstanceOf(Redirect::class);
    expect($click->redirect->id)->toBe($redirect->id);
});

// Test suppression en cascade
test('clicks are deleted when redirect is deleted', function () {
    $redirect = Redirect::factory()->create(['code' => 'test']);

    $this->get('/s/test');
    $this->get('/s/test');

    expect(RedirectClick::count())->toBe(2);

    $redirect->delete();

    expect(RedirectClick::count())->toBe(0);
});

// Test méthode recordClick
test('recordClick method creates click record and increments counter', function () {
    $user = User::factory()->create();
    $redirect = Redirect::factory()->create(['clicks' => 10]);

    $click = $redirect->recordClick(
        userId: $user->id,
        ipAddress: '10.0.0.1',
        userAgent: 'Test Browser',
        referer: 'https://example.com'
    );

    expect($click)->toBeInstanceOf(RedirectClick::class);
    expect($click->user_id)->toBe($user->id);
    expect($click->ip_address)->toBe('10.0.0.1');
    expect($click->user_agent)->toBe('Test Browser');
    expect($click->referer)->toBe('https://example.com');
    expect($redirect->fresh()->clicks)->toBe(11);
});

// Test méthode recordClick avec valeurs nulles
test('recordClick handles null values correctly', function () {
    $redirect = Redirect::factory()->create();

    $click = $redirect->recordClick(
        userId: null,
        ipAddress: null,
        userAgent: null,
        referer: null
    );

    expect($click->user_id)->toBeNull();
    expect($click->ip_address)->toBeNull();
    expect($click->user_agent)->toBeNull();
    expect($click->referer)->toBeNull();
});

// Test que l'utilisateur supprimé met le user_id à null
test('deleting user sets user_id to null in clicks', function () {
    $user = User::factory()->create();
    $redirect = Redirect::factory()->create(['code' => 'test']);

    $this->actingAs($user)->get('/s/test');

    $click = RedirectClick::first();
    expect($click->user_id)->toBe($user->id);

    $user->delete();

    $click->refresh();
    expect($click->user_id)->toBeNull();
});

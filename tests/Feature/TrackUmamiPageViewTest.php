<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['umami.enabled' => true]);
    config(['umami.website_id' => 'test-website-id']);
    config(['umami.endpoint' => 'https://test.umami.com']);
    config(['umami.track_ajax_requests' => false]);
    config(['umami.track_authenticated_users' => true]);
    config(['umami.excluded_paths' => ['/telescope*', '/up']]);
});

test('tracks page view on successful GET request', function () {
    Http::fake([
        'test.umami.com/*' => Http::response(['success' => true], 200),
    ]);

    $response = $this->get('/');

    $response->assertSuccessful();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://test.umami.com/api/send'
            && $request['type'] === 'event';
    });
});

test('does not track POST requests', function () {
    Http::fake();

    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    Http::assertNothingSent();
});

test('does not track when umami is disabled', function () {
    config(['umami.enabled' => false]);

    Http::fake();

    $this->get('/');

    Http::assertNothingSent();
});

test('does not track excluded paths', function () {
    Http::fake();

    $this->get('/up');

    Http::assertNothingSent();
});

test('does not track ajax requests by default', function () {
    Http::fake();

    $this->get('/', ['X-Requested-With' => 'XMLHttpRequest']);

    Http::assertNothingSent();
});

test('tracks ajax requests when configured', function () {
    config(['umami.track_ajax_requests' => true]);

    Http::fake([
        'test.umami.com/*' => Http::response(['success' => true], 200),
    ]);

    $this->get('/', ['X-Requested-With' => 'XMLHttpRequest']);

    Http::assertSent(function ($request) {
        return $request['type'] === 'event'
            && $request['payload']['name'] === '$pageview';
    });
});

test('includes user data for authenticated users', function () {
    $user = User::factory()->create();

    Http::fake([
        'test.umami.com/*' => Http::response(['success' => true], 200),
    ]);

    $this->actingAs($user)->get('/');

    Http::assertSent(function ($request) use ($user) {
        return $request['type'] === 'event'
            && $request['payload']['name'] === '$pageview'
            && isset($request['payload']['data']['user_id'])
            && $request['payload']['data']['user_id'] === $user->id;
    });
});

test('does not track failed requests', function () {
    Http::fake();

    $this->get('/non-existent-route');

    Http::assertNothingSent();
});

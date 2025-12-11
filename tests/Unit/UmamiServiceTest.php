<?php

declare(strict_types=1);

use App\Services\UmamiService;
use Illuminate\Support\Facades\Http;

test('tracks page view successfully', function () {
    Http::fake([
        'test.umami.com/*' => Http::response(['success' => true], 200),
    ]);

    $service = new UmamiService(
        endpoint: 'https://test.umami.com',
        websiteId: 'test-website-id',
        timeout: 5,
        verifySSL: true
    );

    config(['umami.enabled' => true]);

    $result = $service->trackPageView(
        url: 'https://example.com/page',
        hostname: 'example.com',
        referrer: 'https://google.com',
        userAgent: 'Mozilla/5.0',
        language: 'en',
        screen: '1920x1080'
    );

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://test.umami.com/api/send'
            && $request['type'] === 'event'
            && $request['payload']['name'] === '$pageview'
            && $request['payload']['url'] === 'https://example.com/page'
            && $request['payload']['website'] === 'test-website-id';
    });
});

test('tracks custom event successfully', function () {
    Http::fake([
        'test.umami.com/*' => Http::response(['success' => true], 200),
    ]);

    $service = new UmamiService(
        endpoint: 'https://test.umami.com',
        websiteId: 'test-website-id',
        timeout: 5,
        verifySSL: true
    );

    config(['umami.enabled' => true]);

    $result = $service->trackEvent(
        eventName: 'button_click',
        url: 'https://example.com/page',
        hostname: 'example.com',
        eventData: ['button' => 'submit']
    );

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://test.umami.com/api/send'
            && $request['type'] === 'event'
            && $request['payload']['name'] === 'button_click'
            && $request['payload']['data']['button'] === 'submit';
    });
});

test('does not track when umami is disabled', function () {
    config(['umami.enabled' => false]);

    Http::fake();

    $service = new UmamiService(
        endpoint: 'https://test.umami.com',
        websiteId: 'test-website-id',
        timeout: 5,
        verifySSL: true
    );

    $result = $service->trackPageView(
        url: 'https://example.com/page',
        hostname: 'example.com'
    );

    expect($result)->toBeFalse();
    Http::assertNothingSent();
});

test('handles tracking failures gracefully', function () {
    Http::fake([
        'test.umami.com/*' => Http::response(['error' => 'Server error'], 500),
    ]);

    $service = new UmamiService(
        endpoint: 'https://test.umami.com',
        websiteId: 'test-website-id',
        timeout: 5,
        verifySSL: true
    );

    config(['umami.enabled' => true]);

    $result = $service->trackPageView(
        url: 'https://example.com/page',
        hostname: 'example.com'
    );

    expect($result)->toBeFalse();
});

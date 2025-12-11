<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\UmamiService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackUmamiPageView
{
    public function __construct(
        protected UmamiService $umami
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track successful GET requests
        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        // Track asynchronously to avoid blocking the response
        $this->trackPageView($request);

        return $response;
    }

    /**
     * Determine if the request should be tracked.
     */
    protected function shouldTrack(Request $request, Response $response): bool
    {
        // Skip if Umami is disabled
        if (! config('umami.enabled')) {
            return false;
        }

        // Only track GET requests
        if (! $request->isMethod('GET')) {
            return false;
        }

        // Only track successful responses
        if (! $response->isSuccessful()) {
            return false;
        }

        // Skip AJAX requests unless configured to track them
        if ($request->ajax() && ! config('umami.track_ajax_requests')) {
            return false;
        }

        // Skip excluded paths
        $path = $request->path();
        foreach (config('umami.excluded_paths', []) as $excluded) {
            if (Str::is($excluded, $path) || Str::is($excluded, '/'.$path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Track the page view using Umami.
     */
    protected function trackPageView(Request $request): void
    {
        $url = $request->fullUrl();
        $hostname = $request->getHost();
        $referrer = $request->header('referer', '');
        $userAgent = $request->userAgent() ?? '';
        $language = $request->getPreferredLanguage() ?? 'en';

        // Add custom data if user is authenticated
        $customData = null;
        if (config('umami.track_authenticated_users') && $request->user()) {
            $customData = [
                'user_id' => $request->user()->id,
                'user_type' => 'authenticated',
            ];
        }

        // Track synchronously - Umami tracking should be fast
        $this->umami->trackPageView(
            url: $url,
            hostname: $hostname,
            referrer: $referrer,
            userAgent: $userAgent,
            language: $language,
            screen: '',
            title: null,
            data: $customData
        );
    }
}

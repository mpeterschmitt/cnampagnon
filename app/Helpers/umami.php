<?php

declare(strict_types=1);

use App\Services\UmamiService;

if (! function_exists('umami')) {
    /**
     * Get the Umami service instance or track an event.
     */
    function umami(?string $eventName = null, ?array $eventData = null): UmamiService|bool
    {
        $service = app(UmamiService::class);

        if ($eventName === null) {
            return $service;
        }

        $request = request();

        // if user exists add to event data
        if ($request->user() && config('umami.track_authenticated_users')) {
            $eventData = array_merge($eventData ?? [], [
                'user_id' => $request->user()->id,
                'user_type' => 'authenticated',
            ]);
        }

        return $service->trackEvent(
            eventName: $eventName,
            url: $request->fullUrl(),
            hostname: $request->getHost(),
            eventData: $eventData,
            userAgent: $request->userAgent() ?? ''
        );
    }
}

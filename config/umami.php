<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Umami Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Umami server-side tracking.
    |
    */

    'enabled' => env('UMAMI_ENABLED', false),

    'endpoint' => env('UMAMI_ENDPOINT', 'https://track.unurled.me'),

    'website_id' => env('UMAMI_WEBSITE_ID', '3c72526d-bdfc-4dfc-acbc-8ec858e37478'),

    /*
    |--------------------------------------------------------------------------
    | Tracking Options
    |--------------------------------------------------------------------------
    */

    'track_authenticated_users' => env('UMAMI_TRACK_AUTHENTICATED', true),

    'track_ajax_requests' => env('UMAMI_TRACK_AJAX', false),

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    |
    | Define paths that should not be tracked. Supports wildcards.
    |
    */

    'excluded_paths' => [
        '/telescope*',
        '/horizon*',
        '/up',
        '/livewire/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    */

    'timeout' => env('UMAMI_TIMEOUT', 5),

    'verify_ssl' => env('UMAMI_VERIFY_SSL', true),
];

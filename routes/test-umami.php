<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::get('/test-umami', function () {
    $service = app(\App\Services\UmamiService::class);

    $payload = [
        'type' => 'pageview',
        'payload' => [
            'hostname' => 'example.com',
            'url' => 'https://example.com/test',
            'referrer' => '',
            'language' => 'en',
            'screen' => '',
            'website' => config('umami.website_id'),
        ],
    ];

    try {
        $response = Http::timeout(5)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0',
                'Content-Type' => 'application/json',
            ])
            ->post(config('umami.endpoint') . '/api/send', $payload);

        return response()->json([
            'status' => $response->status(),
            'body' => $response->body(),
            'successful' => $response->successful(),
            'endpoint' => config('umami.endpoint') . '/api/send',
            'payload' => $payload,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'endpoint' => config('umami.endpoint') . '/api/send',
            'payload' => $payload,
        ], 500);
    }
});


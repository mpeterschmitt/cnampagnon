<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UmamiService
{
    public function __construct(
        protected string $endpoint,
        protected string $websiteId,
        protected int $timeout = 5,
        protected bool $verifySSL = true
    ) {}

    /**
     * Track a page view event.
     */
    public function trackPageView(
        string $url,
        string $hostname,
        string $referrer = '',
        string $userAgent = '',
        string $language = '',
        string $screen = '',
        ?string $title = null,
        ?array $data = null
    ): bool {
        if (! config('umami.enabled')) {
            return false;
        }

        try {
            $payload = [
                'type' => 'event',
                'payload' => array_filter([
                    'hostname' => $hostname,
                    'url' => $url,
                    'referrer' => $referrer,
                    'language' => $language,
                    'screen' => $screen,
                    'title' => $title,
                    'website' => $this->websiteId,
                    'data' => $data,
                ]),
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => $userAgent ?: 'Mozilla/5.0',
                    'Content-Type' => 'application/json',
                ])
                ->withOptions(['verify' => $this->verifySSL])
                ->post("{$this->endpoint}/api/send", $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Umami tracking failed', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);

            return false;
        }
    }

    /**
     * Track a custom event.
     */
    public function trackEvent(
        string $eventName,
        string $url,
        string $hostname,
        ?array $eventData = null,
        string $userAgent = ''
    ): bool {
        if (! config('umami.enabled')) {
            return false;
        }

        try {
            $payload = [
                'type' => 'event',
                'payload' => array_filter([
                    'hostname' => $hostname,
                    'url' => $url,
                    'website' => $this->websiteId,
                    'name' => $eventName,
                    'data' => $eventData,
                ]),
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => $userAgent ?: 'Mozilla/5.0',
                    'Content-Type' => 'application/json',
                ])
                ->withOptions(['verify' => $this->verifySSL])
                ->post("{$this->endpoint}/api/send", $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Umami event tracking failed', [
                'error' => $e->getMessage(),
                'event' => $eventName,
            ]);

            return false;
        }
    }
}

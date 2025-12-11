<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\UmamiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TrackUmamiPageViewJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $url,
        public string $hostname,
        public string $referrer,
        public string $userAgent,
        public string $language,
        public ?array $customData = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(UmamiService $umami): void
    {
        $umami->trackPageView(
            url: $this->url,
            hostname: $this->hostname,
            referrer: $this->referrer,
            userAgent: $this->userAgent,
            language: $this->language,
            screen: '',
            title: null,
            data: $this->customData
        );
    }
}

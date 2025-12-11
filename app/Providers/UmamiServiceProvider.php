<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\UmamiService;
use Illuminate\Support\ServiceProvider;

class UmamiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(UmamiService::class, function () {
            return new UmamiService(
                endpoint: config('umami.endpoint'),
                websiteId: config('umami.website_id'),
                timeout: (int) config('umami.timeout'),
                verifySSL: (bool) config('umami.verify_ssl')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

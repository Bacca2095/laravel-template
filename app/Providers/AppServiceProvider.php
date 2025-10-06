<?php

namespace App\Providers;

use App\Services\OtpService;
use App\Services\PasskeyService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(OtpService::class, function ($app) {
            $ttl = (int) config('services.otp.ttl', 300);

            return new OtpService($ttl);
        });

        $this->app->scoped(PasskeyService::class, function ($app) {
            return new PasskeyService($app->make(CacheRepository::class));
        });
    }

    public function boot(): void
    {
        //
    }
}

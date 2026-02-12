<?php

namespace Fisal\Otp;

use Fisal\Otp\Commands\CleanOtps;
use Illuminate\Support\ServiceProvider;

class OtpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/otp.php', 'otp');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->publishes([
            __DIR__ . '/config/otp.php' => config_path('otp.php'),
        ], 'otp-config');

        $this->commands([
            CleanOtps::class,
        ]);
    }
}

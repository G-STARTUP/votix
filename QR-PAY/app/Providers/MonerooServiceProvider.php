<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MonerooServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('moneroo.client', function () {
            $apiKey = config('moneroo.api_key');
            $mode   = config('moneroo.mode');
            $timeout = config('moneroo.timeout');

            // Defer class resolution to runtime, guard if SDK class absent
            $clientClass = 'Moneroo\\Client';
            if (class_exists($clientClass)) {
                return new $clientClass([
                    'api_key' => $apiKey,
                    'mode'    => $mode,
                    'timeout' => $timeout,
                ]);
            }
            // Fallback stub to avoid failures when package structure changes
            return (object) [
                'api_key' => $apiKey,
                'mode'    => $mode,
                'timeout' => $timeout,
                'available' => false,
            ];
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config if needed in future
    }
}

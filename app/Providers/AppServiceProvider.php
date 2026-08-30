<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Gravity\Auth\JsonUserProvider;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\API\Globals;
use Illuminate\Support\Facades\Log;
use OpenTelemetry\API\Behavior\Internal\Logging;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('json', function ($app, array $config) {
            return new JsonUserProvider();
        });
        
        // If the open-telemetry PECL extension is loaded, initialize the global SDK hooks
        if (class_exists(Sdk::class)) {
            // Force OTel internal errors to write straight to storage/logs/laravel.log
           // Logging::setLogWriter(Log::getLogger());
        
            \OpenTelemetry\SDK\Sdk::builder()->buildAndRegisterGlobal();
            // The SDK builder will automatically parse your .env variables starting with OTEL_*
            //Sdk::builder()->buildAndRegisterGlobal();
            Sdk::builder()->buildAndRegisterGlobal();

            // Force Laravel's terminating lifecycle hook to flush telemetry data out
            $this->app->terminating(function () {
                $tracerProvider = Globals::tracerProvider();
                if (method_exists($tracerProvider, 'shutdown')) {
                    $tracerProvider->shutdown();
                }
                
                $loggerProvider = Globals::loggerProvider();
                if (method_exists($loggerProvider, 'shutdown')) {
                    $loggerProvider->shutdown();
                }
            });
        }
    }
}

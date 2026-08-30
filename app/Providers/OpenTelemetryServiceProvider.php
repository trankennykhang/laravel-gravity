<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Globals;
use OpenTelemetry\SDK\SdkBuilder;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;

class OpenTelemetryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The auto-laravel package handles most setup via env vars.
        // This provider ensures the SDK is booted early.
    }

    public function boot(): void
    {
        // SDK is auto-configured from OTEL_* environment variables.
        // No manual wiring needed when using opentelemetry-auto-laravel.
    }
}
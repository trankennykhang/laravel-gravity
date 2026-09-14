<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telemetry:test', function () {
    $this->info('====================================================');
    $this->info('   Laravel Gravity Telemetry Verification Tool      ');
    $this->info('====================================================');

    // 1. Traces (Tempo)
    $tracer = \OpenTelemetry\API\Globals::tracerProvider()->getTracer('laravel-gravity-cli');
    $rootSpan = $tracer->spanBuilder('artisan:telemetry:test')
        ->setAttribute('command.name', 'telemetry:test')
        ->setAttribute('environment', config('app.env'))
        ->startSpan();

    $context = $rootSpan->storeInContext(\OpenTelemetry\Context\Context::getCurrent());
    $traceId = $rootSpan->getContext()->getTraceId();

    // Child span: simulate database query
    $dbSpan = $tracer->spanBuilder('db:simulate_query')
        ->setParent($context)
        ->setAttribute('db.system', 'sqlite')
        ->setAttribute('db.statement', 'SELECT count(*) FROM products')
        ->startSpan();
    usleep(25000); // 25ms simulation
    $dbSpan->end();

    // Child span: simulate cache lookup
    $cacheSpan = $tracer->spanBuilder('cache:simulate_lookup')
        ->setParent($context)
        ->setAttribute('cache.key', 'products:featured')
        ->setAttribute('cache.hit', true)
        ->startSpan();
    usleep(10000); // 10ms simulation
    $cacheSpan->end();

    $rootSpan->end();

    $this->line(" [✓] Trace dispatched to Tempo:");
    $this->line("     Trace ID: <comment>{$traceId}</comment>");

    // 2. Metrics (Prometheus)
    $meter = \OpenTelemetry\API\Globals::meterProvider()->getMeter('laravel-gravity-cli');
    $counter = $meter->createCounter('test_counter', 'count', 'Telemetry test verification counter');
    $counter->add(1, [
        'source' => 'artisan',
        'status' => 'success',
    ]);
    $this->line(" [✓] Metric incremented for Prometheus: <comment>test_counter_count_total</comment>");

    // 3. Logs (Loki)
    $logger = \OpenTelemetry\API\Globals::loggerProvider()->getLogger('laravel-gravity-cli');
    $logRecord = (new \OpenTelemetry\API\Logs\LogRecord("Verification telemetry generated from Artisan CLI with Trace ID: {$traceId}"))
        ->setSeverityText('INFO')
        ->setSeverityNumber(\OpenTelemetry\API\Logs\Severity::INFO)
        ->setAttribute('trace_id', $traceId)
        ->setAttribute('source', 'cli');
    $logger->emit($logRecord);
    \Illuminate\Support\Facades\Log::info("Verification telemetry generated from Artisan CLI with Trace ID: {$traceId}");
    $this->line(" [✓] Log emitted to Loki: <comment>Verification telemetry generated...</comment>");

    // Flush providers immediately
    \OpenTelemetry\API\Globals::tracerProvider()->forceFlush();
    \OpenTelemetry\API\Globals::meterProvider()->forceFlush();
    \OpenTelemetry\API\Globals::loggerProvider()->forceFlush();
    \OpenTelemetry\API\Globals::tracerProvider()->shutdown();
    \OpenTelemetry\API\Globals::meterProvider()->shutdown();
    \OpenTelemetry\API\Globals::loggerProvider()->shutdown();

    $this->newLine();
    $this->info('====================================================');
    $this->info('   How to see this data in Grafana (port 3001):     ');
    $this->info('====================================================');
    $this->line(" • Dashboard:        http://localhost:3001/d/laravel-gravity-overview");
    $this->line(" • Tempo (Trace):    http://localhost:3001/explore?left=" . urlencode(json_encode(['datasource' => 'tempo', 'queries' => [['query' => $traceId, 'queryType' => 'traceql']]])));
    $this->line(" • Loki (Logs):      http://localhost:3001/explore?left=" . urlencode(json_encode(['datasource' => 'loki', 'queries' => [['expr' => '{service_name=~".+"}']]])));
    $this->line(" • Prometheus:       http://localhost:3001/explore?left=" . urlencode(json_encode(['datasource' => 'prometheus', 'queries' => [['expr' => 'test_counter_count_total']]])));
    $this->newLine();
})->purpose('Generate test OpenTelemetry traces, metrics, and logs');


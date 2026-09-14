<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GravityController;
use OpenTelemetry\API\Globals;

// Auth Routes (Guest accessible)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});
Route::get('/otel-test', function () {
    // 1. Traces (Tempo)
    $tracer = Globals::tracerProvider()->getTracer('laravel-web-test');
    $span = $tracer->spanBuilder('web:test_telemetry')
        ->setAttribute('test.message', 'Hello from Laravel Gravity Web Test!')
        ->setAttribute('test.timestamp', now()->toIso8601String())
        ->startSpan();

    $traceId = $span->getContext()->getTraceId();
    $spanId = $span->getContext()->getSpanId();
    $span->end();

    // 2. Metrics (Prometheus)
    $meter = Globals::meterProvider()->getMeter('laravel-web-test');
    $counter = $meter->createCounter('test_counter', 'count', 'Telemetry test verification counter');
    $counter->add(1, [
        'source' => 'web',
        'status' => 'success',
    ]);

    // 3. Logs (Loki)
    $logger = Globals::loggerProvider()->getLogger('laravel-web-test');
    $logRecord = (new \OpenTelemetry\API\Logs\LogRecord("Verification telemetry triggered via web /otel-test with Trace ID: {$traceId}"))
        ->setSeverityText('INFO')
        ->setSeverityNumber(\OpenTelemetry\API\Logs\Severity::INFO)
        ->setAttribute('trace_id', $traceId)
        ->setAttribute('source', 'web');
    $logger->emit($logRecord);
    \Illuminate\Support\Facades\Log::info("Verification telemetry triggered via web /otel-test with Trace ID: {$traceId}");

    // Flush immediately to collector
    Globals::tracerProvider()->forceFlush();
    Globals::meterProvider()->forceFlush();
    Globals::loggerProvider()->forceFlush();

    return response()->json([
        'status' => 'success',
        'message' => 'Telemetry event successfully generated and exported!',
        'telemetry' => [
            'trace_id' => $traceId,
            'span_id' => $spanId,
            'metric' => 'test_counter_count_total incremented',
            'log' => 'Emitted to Loki with Trace ID correlation',
        ],
        'grafana_links' => [
            'dashboard' => 'http://localhost:3001/d/laravel-gravity-overview',
            'tempo_trace' => 'http://localhost:3001/explore?left=' . urlencode(json_encode(['datasource' => 'tempo', 'queries' => [['query' => $traceId, 'queryType' => 'traceql']]])),
            'loki_logs' => 'http://localhost:3001/explore?left=' . urlencode(json_encode(['datasource' => 'loki', 'queries' => [['expr' => '{service_name=~".+"}']]])),
            'prometheus_metrics' => 'http://localhost:3001/explore?left=' . urlencode(json_encode(['datasource' => 'prometheus', 'queries' => [['expr' => 'test_counter_count_total']]])),
        ],
    ]);
});
Route::get('/otel-debug', function () {
    return [
        'extension_loaded' => extension_loaded('opentelemetry'),
        'endpoint'   => getenv('OTEL_EXPORTER_OTLP_ENDPOINT'),
        'protocol'   => getenv('OTEL_EXPORTER_OTLP_PROTOCOL'),
        'service'    => getenv('OTEL_SERVICE_NAME'),
        'traces'     => getenv('OTEL_TRACES_EXPORTER'),
        'php_version'=> PHP_VERSION,
    ];
});
// Secure Routes (Requires Authentication)
//Route::middleware('auth')->group(function () {
    // Root points directly to Gravity default (products)
    Route::get('/', [GravityController::class, 'index'])->name('dashboard');
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // 1. Register routes from configuration file dynamically to preserve standard named routes
    foreach (array_keys(config('gravity.resources', [])) as $resource) {
        Route::resource($resource, GravityController::class)->names([
            'index'   => "{$resource}.index",
            'create'  => "{$resource}.create",
            'store'   => "{$resource}.store",
            'edit'    => "{$resource}.edit",
            'update'  => "{$resource}.update",
            'destroy' => "{$resource}.destroy",
        ]);
    }

    // 2. Default/fallback dynamic routes to map /{resource} to GravityController
    Route::get('/{resource}', [GravityController::class, 'index'])->name('gravity.generic.index');
    Route::get('/{resource}/create', [GravityController::class, 'create'])->name('gravity.generic.create');
    Route::post('/{resource}', [GravityController::class, 'store'])->name('gravity.generic.store');
    Route::get('/{resource}/{id}/edit', [GravityController::class, 'edit'])->name('gravity.generic.edit');
    Route::put('/{resource}/{id}', [GravityController::class, 'update'])->name('gravity.generic.update');
    Route::delete('/{resource}/{id}', [GravityController::class, 'destroy'])->name('gravity.generic.destroy');
//});

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
    $tracer = Globals::tracerProvider()->getTracer('laravel-manual-test');
    
    // 2. Start a manual test span block
    $span = $tracer->spanBuilder('ManualTestEvent')->startSpan();
    
    // 3. Attach dummy context data
    $span->setAttribute('test.message', 'Hello from Laravel Server 1!');
    
    // 4. End the span to force execution and trigger the flush cycle
    $span->end();
    
    return response()->json(['status' => 'Manual span dispatched to collector!']);
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

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GravityController;

// Auth Routes (Guest accessible)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Secure Routes (Requires Authentication)
Route::middleware('auth')->group(function () {
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
});

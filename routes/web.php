<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/products', [ProductController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('products.index');

Route::get('/products/create', [ProductController::class, 'create'])
    ->middleware(['auth', 'verified'])
    ->name('products.create');

Route::post('/products', [ProductController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('products.store');

Route::get('/products/{product}/edit', [ProductController::class, 'edit'])
    ->middleware(['auth', 'verified'])
    ->whereNumber('product')
    ->name('products.edit');

Route::put('/products/{product}', [ProductController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->whereNumber('product')
    ->name('products.update');

Route::delete('/products/{product}', [ProductController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->whereNumber('product')
    ->name('products.destroy');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

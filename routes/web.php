<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))
    ->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::inertia('invoices', 'invoices/Index')->name('invoices.index');
    Route::inertia('expenses', 'expenses/Index')->name('expenses.index');
    Route::inertia('documents', 'documents/Index')->name('documents.index');

    Route::resource('customers', CustomerController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
});

require __DIR__.'/settings.php';

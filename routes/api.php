<?php

use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CatalogVersionController;
use App\Http\Controllers\Api\V1\PrintOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('catalog', CatalogController::class)
        ->middleware('throttle:60,1')
        ->name('api.v1.catalog');

    Route::get('catalog/version', CatalogVersionController::class)
        ->middleware('throttle:60,1')
        ->name('api.v1.catalog.version');

    Route::post('orders/3d-print', PrintOrderController::class)
        ->middleware('throttle:5,1')
        ->name('api.v1.orders.print');
});

<?php

use App\Http\Controllers\Api\V1\CatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('catalog', CatalogController::class)
        ->middleware('throttle:60,1')
        ->name('api.v1.catalog');
});

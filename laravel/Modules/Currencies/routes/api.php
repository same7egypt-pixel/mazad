<?php

use Illuminate\Support\Facades\Route;
use Modules\Currencies\Http\Controllers\CurrenciesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('currencies', CurrenciesController::class)->names('currencies');
});

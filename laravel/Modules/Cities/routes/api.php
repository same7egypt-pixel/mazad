<?php

use Illuminate\Support\Facades\Route;
use Modules\Cities\Http\Controllers\CitiesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('cities', CitiesController::class)->names('cities');
});

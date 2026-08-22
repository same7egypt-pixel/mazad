<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Http\Controllers\ReviewsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('reviews', ReviewsController::class)->names('reviews');
});

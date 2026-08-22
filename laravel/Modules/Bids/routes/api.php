<?php

use Illuminate\Support\Facades\Route;
use Modules\Bids\Http\Controllers\BidsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('bids', BidsController::class)->names('bids');
});

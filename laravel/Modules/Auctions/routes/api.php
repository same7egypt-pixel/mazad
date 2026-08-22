<?php

use Illuminate\Support\Facades\Route;
use Modules\Auctions\Http\Controllers\AuctionsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('auctions', AuctionsController::class)->names('auctions');
});

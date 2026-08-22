<?php

use Illuminate\Support\Facades\Route;
use Modules\Auctions\Http\Controllers\AuctionsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('auctions', AuctionsController::class)->names('auctions');
});

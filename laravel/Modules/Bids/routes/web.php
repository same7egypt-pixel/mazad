<?php

use Illuminate\Support\Facades\Route;
use Modules\Bids\Http\Controllers\BidsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('bids', BidsController::class)->names('bids');
});

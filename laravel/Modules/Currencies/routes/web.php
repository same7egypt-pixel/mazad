<?php

use Illuminate\Support\Facades\Route;
use Modules\Currencies\Http\Controllers\CurrenciesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('currencies', CurrenciesController::class)->names('currencies');
});

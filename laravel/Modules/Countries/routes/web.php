<?php

use Illuminate\Support\Facades\Route;
use Modules\Countries\Http\Controllers\CountriesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('countries', CountriesController::class)->names('countries');
});

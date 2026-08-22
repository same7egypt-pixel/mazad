<?php

use Illuminate\Support\Facades\Route;
use Modules\Cities\Http\Controllers\CitiesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cities', CitiesController::class)->names('cities');
});

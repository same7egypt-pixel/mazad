<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BidController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['marketplace.country'])->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/products', [ProductController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
    return $request->user();
        });
        Route::post('/products', [ProductController::class, 'store']);
        Route::post('/products/{product}/submit-for-review', [ProductController::class, 'submitForReview']);
        Route::post('/auctions/{auction}/bids', [BidController::class, 'store'])->middleware('throttle:auction-bids');
    });
});

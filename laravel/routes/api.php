<?php

use App\Http\Controllers\Api\AuctionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BidController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductMediaController;
use App\Http\Controllers\Api\ProductModerationController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['marketplace.country'])->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/auctions', [AuctionController::class, 'index']);
    Route::get('/auctions/{auction}', [AuctionController::class, 'show']);
    Route::get('/auctions/{auction}/bids', [AuctionController::class, 'bids']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::post('/products', [ProductController::class, 'store']);
        Route::post('/products/{product}/submit-for-review', [ProductController::class, 'submitForReview']);
        Route::post('/products/{product}/media', [ProductMediaController::class, 'store']);
        Route::post('/products/{product}/approve', [ProductModerationController::class, 'approve']);
        Route::post('/products/{product}/reject', [ProductModerationController::class, 'reject']);
        Route::post('/auctions', [AuctionController::class, 'store']);
        Route::post('/auctions/{auction}/cancel', [AuctionController::class, 'cancel']);
        Route::post('/auctions/{auction}/bids', [BidController::class, 'store'])->middleware('throttle:auction-bids');
        Route::post('/orders/{order}/payments', [PaymentController::class, 'initiate']);
        Route::get('/wallets', [WalletController::class, 'index']);
        Route::post('/wallets/{wallet}/withdrawals', [WalletController::class, 'requestWithdrawal']);
        Route::post('/withdrawals/{withdrawal}/approve', [WalletController::class, 'approveWithdrawal']);
        Route::post('/withdrawals/{withdrawal}/reject', [WalletController::class, 'rejectWithdrawal']);
    });

    Route::post('/payment-webhooks/{gateway}', [PaymentController::class, 'webhook']);
});

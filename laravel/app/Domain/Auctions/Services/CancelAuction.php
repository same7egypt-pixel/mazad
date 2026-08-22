<?php

namespace App\Domain\Auctions\Services;

use App\Domain\Auctions\Events\AuctionLifecycleUpdated;
use App\Models\Auction;
use App\Models\User;
use App\Notifications\AuctionLifecycleNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelAuction
{
    public function handle(int $auctionId): Auction
    {
        return DB::transaction(function () use ($auctionId): Auction {
            $auction = Auction::query()->with('product')->lockForUpdate()->findOrFail($auctionId);

            if ($auction->status !== 'upcoming' || now()->gte($auction->start_time)) {
                throw ValidationException::withMessages(['auction' => 'Only an auction that has not started may be cancelled.']);
            }
            if ($auction->bids()->exists()) {
                throw ValidationException::withMessages(['auction' => 'An auction with bids cannot be cancelled.']);
            }

            $auction->update(['status' => 'cancelled']);
            $auction->product->update(['status' => 'approved']);
            $sellerId = $auction->product->user_id;
            DB::afterCommit(function () use ($auction, $sellerId): void {
                $freshAuction = $auction->fresh();
                AuctionLifecycleUpdated::dispatch($freshAuction, 'cancelled');
                User::query()->find($sellerId)?->notify(new AuctionLifecycleNotification($freshAuction, 'cancelled'));
            });

            return $auction->fresh(['product.media', 'currency']);
        }, 3);
    }
}

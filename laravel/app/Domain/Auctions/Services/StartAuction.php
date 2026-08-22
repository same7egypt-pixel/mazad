<?php

namespace App\Domain\Auctions\Services;

use App\Domain\Auctions\Events\AuctionLifecycleUpdated;
use App\Models\Auction;
use App\Models\User;
use App\Notifications\AuctionLifecycleNotification;
use Illuminate\Support\Facades\DB;

class StartAuction
{
    public function handle(int $auctionId): bool
    {
        return DB::transaction(function () use ($auctionId): bool {
            $auction = Auction::query()->with('product')->lockForUpdate()->findOrFail($auctionId);

            if ($auction->status !== 'upcoming' || now()->lt($auction->start_time)) {
                return false;
            }
            if (now()->gte($auction->end_time)) {
                $auction->update(['status' => 'ended']);
                $auction->product->update(['status' => 'approved']);

                return false;
            }
            if ($auction->product->status !== 'approved') {
                return false;
            }

            $auction->update(['status' => 'live']);
            $auction->product->update(['status' => 'active']);
            $sellerId = $auction->product->user_id;
            DB::afterCommit(function () use ($auction, $sellerId): void {
                $freshAuction = $auction->fresh();
                AuctionLifecycleUpdated::dispatch($freshAuction, 'started');
                $seller = User::query()->find($sellerId);
                $seller?->notify(new AuctionLifecycleNotification($freshAuction, 'started'));
            });

            return true;
        }, 3);
    }
}

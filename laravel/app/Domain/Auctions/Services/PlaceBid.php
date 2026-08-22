<?php

namespace App\Domain\Auctions\Services;

use App\Domain\Auctions\Events\BidPlaced;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceBid
{
    public function handle(int $auctionId, User $bidder, string $amount): Bid
    {
        return DB::transaction(function () use ($auctionId, $bidder, $amount): Bid {
            $auction = Auction::query()->with('product')->lockForUpdate()->findOrFail($auctionId);

            if (! $bidder->can('bid', $auction)) {
                throw new AuthorizationException('You are not eligible to bid on this auction.');
            }
            if ($auction->status !== 'live' || now()->lt($auction->start_time) || now()->gte($auction->end_time)) {
                throw ValidationException::withMessages(['auction' => 'This auction is not accepting bids.']);
            }
            if (! preg_match('/^\d{1,16}(\.\d{1,2})?$/', $amount)) {
                throw ValidationException::withMessages(['amount' => 'The bid amount must be a positive monetary value.']);
            }

            $minimum = bcadd((string) $auction->current_price, (string) $auction->minimum_increment, 2);
            if (bccomp($amount, $minimum, 2) < 0) {
                throw ValidationException::withMessages(['amount' => "The next bid must be at least {$minimum}."]);
            }

            $bid = Bid::query()->create(['auction_id' => $auction->id, 'user_id' => $bidder->id, 'amount' => $amount]);
            $auction->forceFill(['current_price' => $amount, 'winner_id' => $bidder->id, 'bid_count' => $auction->bid_count + 1, 'version' => $auction->version + 1])->save();
            $auction->refresh();

            DB::afterCommit(fn () => BidPlaced::dispatch($auction, $bid));

            return $bid;
        }, 3);
    }
}

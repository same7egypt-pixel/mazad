<?php

namespace App\Domain\Auctions\Services;

use App\Models\Auction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CloseAuction
{
    public function handle(int $auctionId): ?Order
    {
        return DB::transaction(function () use ($auctionId): ?Order {
            $auction = Auction::query()->with('product')->lockForUpdate()->findOrFail($auctionId);
            if (! in_array($auction->status, ['live', 'upcoming'], true) || now()->lt($auction->end_time)) return $auction->order;

            $reserveMet = $auction->reserve_price === null || bccomp((string) $auction->current_price, (string) $auction->reserve_price, 2) >= 0;
            if ($auction->winner_id === null || ! $reserveMet) {
                $auction->update(['status' => 'ended']);
                return null;
            }

            $commission = bcmul((string) $auction->current_price, bcdiv((string) config('marketplace.default_commission_rate'), '100', 4), 2);
            $order = Order::query()->firstOrCreate(['auction_id' => $auction->id], [
                'seller_id' => $auction->product->user_id, 'buyer_id' => $auction->winner_id,
                'country_id' => $auction->country_id, 'currency_id' => $auction->currency_id,
                'amount' => $auction->current_price, 'commission_amount' => $commission,
                'seller_amount' => bcsub((string) $auction->current_price, $commission, 2), 'status' => 'waiting_payment',
            ]);
            $auction->update(['status' => 'sold']);
            return $order;
        }, 3);
    }
}

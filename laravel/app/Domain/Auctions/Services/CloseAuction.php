<?php

namespace App\Domain\Auctions\Services;

use App\Domain\Auctions\Events\AuctionLifecycleUpdated;
use App\Domain\Core\Money\Decimal;
use App\Models\Auction;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AuctionLifecycleNotification;
use Illuminate\Support\Facades\DB;

class CloseAuction
{
    public function handle(int $auctionId): ?Order
    {
        return DB::transaction(function () use ($auctionId): ?Order {
            $auction = Auction::query()->with(['product', 'country'])->lockForUpdate()->findOrFail($auctionId);
            if ($auction->status !== 'live' || now()->lt($auction->end_time)) {
                return $auction->order;
            }

            $reserveMet = $auction->reserve_price === null || Decimal::compare((string) $auction->current_price, (string) $auction->reserve_price) >= 0;
            if ($auction->winner_id === null || ! $reserveMet) {
                $auction->update(['status' => 'ended']);
                $auction->product->update(['status' => 'approved']);
                $sellerId = $auction->product->user_id;
                DB::afterCommit(function () use ($auction, $sellerId): void {
                    $freshAuction = $auction->fresh();
                    AuctionLifecycleUpdated::dispatch($freshAuction, 'ended_without_sale');
                    User::query()->find($sellerId)?->notify(new AuctionLifecycleNotification($freshAuction, 'ended_without_sale'));
                });

                return null;
            }

            $commissionRate = (string) ($auction->country?->platform_commission_rate ?? config('marketplace.default_commission_rate'));
            $commission = Decimal::percentage((string) $auction->current_price, $commissionRate);
            $cashOnDelivery = $auction->country?->cash_on_delivery_enabled ?? false;
            $order = Order::query()->firstOrCreate(['auction_id' => $auction->id], [
                'seller_id' => $auction->product->user_id, 'buyer_id' => $auction->winner_id,
                'country_id' => $auction->country_id, 'currency_id' => $auction->currency_id,
                'amount' => $auction->current_price, 'commission_rate' => $commissionRate, 'commission_amount' => $commission,
                'seller_amount' => Decimal::subtract((string) $auction->current_price, $commission),
                'payment_method' => $cashOnDelivery ? 'cash_on_delivery' : 'online',
                'status' => $cashOnDelivery ? 'awaiting_cod_confirmation' : 'waiting_payment',
                'winner_confirmation_expires_at' => $cashOnDelivery ? now()->addHours($auction->country?->cod_confirmation_hours ?? 12) : null,
                'collection_status' => $cashOnDelivery ? 'awaiting_confirmation' : 'not_applicable',
            ]);
            $auction->update(['status' => 'sold']);
            $auction->product->update(['status' => 'sold']);
            $sellerId = $auction->product->user_id;
            $winnerId = $auction->winner_id;
            DB::afterCommit(function () use ($auction, $sellerId, $winnerId): void {
                $freshAuction = $auction->fresh();
                AuctionLifecycleUpdated::dispatch($freshAuction, 'sold');
                User::query()->find($sellerId)?->notify(new AuctionLifecycleNotification($freshAuction, 'sold'));
                User::query()->find($winnerId)?->notify(new AuctionLifecycleNotification($freshAuction, 'won'));
            });

            return $order;
        }, 3);
    }
}

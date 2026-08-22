<?php

namespace App\Domain\Governance\Services;

use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\Bid;
use App\Models\User;
use App\Models\UserActivity;

class RecordBidActivity
{
    public function handle(Auction $auction, Bid $bid, User $bidder): void
    {
        $properties = ['auction_id' => $auction->id, 'bid_id' => $bid->id, 'amount' => (string) $bid->amount];
        UserActivity::query()->create([
            'user_id' => $bidder->id,
            'country_id' => $auction->country_id,
            'event' => 'auction.bid_placed',
            'properties' => $properties,
        ]);
        AuditLog::query()->create([
            'actor_id' => $bidder->id,
            'country_id' => $auction->country_id,
            'event' => 'auction.bid_placed',
            'auditable_type' => Bid::class,
            'auditable_id' => $bid->id,
            'after' => $properties,
        ]);

        $recentBidCount = Bid::query()
            ->where('user_id', $bidder->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->whereHas('auction', fn ($query) => $query->where('country_id', $auction->country_id))
            ->count();

        if ($recentBidCount >= 5) {
            UserActivity::query()->create([
                'user_id' => $bidder->id,
                'country_id' => $auction->country_id,
                'event' => 'fraud.suspicious_bid_velocity',
                'properties' => ['auction_id' => $auction->id, 'bid_id' => $bid->id, 'recent_bid_count' => $recentBidCount, 'window_minutes' => 5],
            ]);
        }
    }
}

<?php

namespace App\Domain\Auctions\Events;

use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BidPlaced implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Auction $auction, public Bid $bid) {}

    public function broadcastOn(): array { return [new PrivateChannel('auctions.'.$this->auction->id)]; }
    public function broadcastAs(): string { return 'auction.bid.placed'; }
    public function broadcastWith(): array
    {
        return ['auction_id' => $this->auction->id, 'current_price' => $this->auction->current_price, 'bid_count' => $this->auction->bid_count, 'end_time' => $this->auction->end_time?->toIso8601String(), 'bid' => ['id' => $this->bid->id, 'amount' => $this->bid->amount, 'created_at' => $this->bid->created_at?->toIso8601String()]];
    }
}

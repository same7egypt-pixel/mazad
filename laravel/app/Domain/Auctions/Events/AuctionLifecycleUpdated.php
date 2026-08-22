<?php

namespace App\Domain\Auctions\Events;

use App\Models\Auction;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionLifecycleUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Auction $auction, public string $transition) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('auctions.'.$this->auction->id)];
    }

    public function broadcastAs(): string
    {
        return 'auction.lifecycle.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auction->id,
            'transition' => $this->transition,
            'status' => $this->auction->status,
            'current_price' => $this->auction->current_price,
            'bid_count' => $this->auction->bid_count,
            'end_time' => $this->auction->end_time?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Auction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AuctionLifecycleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Auction $auction, public string $event)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return "auction.{$this->event}";
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'auction_id' => $this->auction->id,
            'country_id' => $this->auction->country_id,
            'currency_id' => $this->auction->currency_id,
            'current_price' => (string) $this->auction->current_price,
            'end_time' => $this->auction->end_time?->toIso8601String(),
        ];
    }
}

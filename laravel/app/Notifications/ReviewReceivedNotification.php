<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReviewReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Review $review)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'review.received';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'order_id' => $this->review->order_id,
            'country_id' => $this->review->order->country_id,
        ];
    }
}

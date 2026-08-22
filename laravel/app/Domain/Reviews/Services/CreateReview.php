<?php

namespace App\Domain\Reviews\Services;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewReceivedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateReview
{
    public function handle(int $orderId, User $reviewer, int $rating, ?string $comment): Review
    {
        return DB::transaction(function () use ($orderId, $reviewer, $rating, $comment): Review {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);
            $reviewedUserId = $this->reviewedUserId($order, $reviewer);
            if (Review::query()->where('reviewer_id', $reviewer->id)->where('order_id', $order->id)->exists()) {
                throw ValidationException::withMessages(['order' => 'You have already reviewed this order.']);
            }

            $review = Review::query()->create([
                'reviewer_id' => $reviewer->id,
                'reviewed_user_id' => $reviewedUserId,
                'order_id' => $order->id,
                'rating' => $rating,
                'comment' => $comment,
            ]);

            DB::afterCommit(function () use ($review, $reviewedUserId): void {
                User::query()->find($reviewedUserId)?->notify(new ReviewReceivedNotification($review->fresh('order')));
            });

            return $review;
        }, 3);
    }

    public function reviewedUserId(Order $order, User $reviewer): int
    {
        if (! $reviewer->can('reviews.create') || $reviewer->country_id !== $order->country_id) {
            throw ValidationException::withMessages(['order' => 'You are not permitted to review this order.']);
        }
        if ($order->status !== 'completed') {
            throw ValidationException::withMessages(['order' => 'Only completed orders can be reviewed.']);
        }

        return match ($reviewer->id) {
            $order->buyer_id => $order->seller_id,
            $order->seller_id => $order->buyer_id,
            default => throw ValidationException::withMessages(['order' => 'Only an order participant may submit a review.']),
        };
    }
}

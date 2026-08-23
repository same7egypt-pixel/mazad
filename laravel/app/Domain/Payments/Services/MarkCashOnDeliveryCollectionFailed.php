<?php

namespace App\Domain\Payments\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkCashOnDeliveryCollectionFailed
{
    public function handle(int $orderId, User $operator, string $reason): Order
    {
        return DB::transaction(function () use ($orderId, $operator, $reason): Order {
            $order = Order::query()->with('payments')->lockForUpdate()->findOrFail($orderId);

            if (! $operator->can('orders.fulfill') || (! $operator->hasRole('GLOBAL_SUPER_ADMIN') && $operator->country_id !== $order->country_id)) {
                throw ValidationException::withMessages(['order' => 'You are not permitted to record collection failure for this order.']);
            }
            if ($order->payment_method !== 'cash_on_delivery' || $order->collection_status !== 'awaiting_collection') {
                throw ValidationException::withMessages(['order' => 'This order does not have an active cash collection.']);
            }

            $payment = $order->payments->firstWhere('gateway', 'cash_on_delivery');
            if (! $payment instanceof Payment) {
                throw ValidationException::withMessages(['payment' => 'The cash collection record is missing.']);
            }

            $payment->update(['status' => 'collection_failed', 'collection_failure_reason' => $reason]);
            $order->update([
                'status' => 'collection_failed',
                'collection_status' => 'collection_failed',
                'collection_failure_reason' => $reason,
                'settlement_status' => 'held',
            ]);

            return $order->fresh(['payments', 'shipment']);
        }, 3);
    }
}

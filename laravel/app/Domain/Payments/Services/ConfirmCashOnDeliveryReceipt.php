<?php

namespace App\Domain\Payments\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmCashOnDeliveryReceipt
{
    public function handle(int $orderId, User $buyer): Order
    {
        return DB::transaction(function () use ($orderId, $buyer): Order {
            $order = Order::query()->with(['shipment', 'payments'])->lockForUpdate()->findOrFail($orderId);

            if ($order->buyer_id !== $buyer->id || ! $buyer->canUseMarketplaceCountry($order->country_id)) {
                throw ValidationException::withMessages(['order' => 'This order is not available for receipt confirmation.']);
            }
            if ($order->payment_method !== 'cash_on_delivery' || $order->collection_status !== 'collected' || $order->shipment?->status !== 'delivered') {
                throw ValidationException::withMessages(['order' => 'This order is not ready for receipt confirmation.']);
            }
            if ($order->receipt_confirmed_at !== null) {
                return $order;
            }

            $order->update([
                'receipt_confirmed_at' => now(),
                'status' => 'completed',
                'completed_at' => $order->completed_at ?? now(),
                'settlement_status' => 'pending_release',
            ]);
            app(ReleaseSellerSettlement::class)->handle($order->fresh());

            return $order->fresh(['payments', 'shipment']);
        }, 3);
    }
}

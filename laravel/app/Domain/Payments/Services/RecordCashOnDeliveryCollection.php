<?php

namespace App\Domain\Payments\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordCashOnDeliveryCollection
{
    public function handle(int $orderId, User $operator, ?string $reference = null): Order
    {
        return DB::transaction(function () use ($orderId, $operator, $reference): Order {
            $order = Order::query()->with(['payments', 'shipment'])->lockForUpdate()->findOrFail($orderId);

            if (! $operator->can('orders.fulfill') || (! $operator->hasRole('GLOBAL_SUPER_ADMIN') && $operator->country_id !== $order->country_id)) {
                throw ValidationException::withMessages(['order' => 'You are not permitted to record collection for this order.']);
            }
            if ($order->payment_method !== 'cash_on_delivery' || ! in_array($order->status, ['cod_confirmed', 'fulfillment_pending', 'shipped', 'ready_for_pickup', 'awaiting_collection', 'awaiting_receipt_confirmation'], true)) {
                throw ValidationException::withMessages(['order' => 'This order is not ready for cash collection.']);
            }

            $payment = $order->payments->firstWhere('gateway', 'cash_on_delivery');
            if (! $payment instanceof Payment) {
                throw ValidationException::withMessages(['payment' => 'The cash collection record is missing.']);
            }
            if ($payment->status === 'collected') {
                return $order;
            }
            if ($payment->status !== 'awaiting_collection') {
                throw ValidationException::withMessages(['payment' => 'This cash collection cannot be recorded in its current state.']);
            }

            $payment->update([
                'status' => 'collected',
                'collected_at' => now(),
                'collection_reference' => $reference,
            ]);
            $order->update([
                'collection_status' => 'collected',
                'status' => in_array($order->status, ['cod_confirmed', 'awaiting_collection'], true) && $order->shipment?->status !== 'delivered' ? 'paid' : ($order->shipment?->status === 'delivered' ? 'awaiting_receipt_confirmation' : $order->status),
                'paid_at' => $order->paid_at ?? now(),
            ]);

            app(CreditSellerWallet::class)->handle($order->fresh());

            return $order->fresh(['payments', 'shipment']);
        }, 3);
    }
}

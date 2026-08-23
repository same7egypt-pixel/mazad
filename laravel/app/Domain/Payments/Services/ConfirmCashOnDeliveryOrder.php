<?php

namespace App\Domain\Payments\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmCashOnDeliveryOrder
{
    /** @param array{fulfilment_preference: string, shipping_address?: array<string, mixed>|null} $attributes */
    public function handle(int $orderId, User $buyer, array $attributes): Order
    {
        return DB::transaction(function () use ($orderId, $buyer, $attributes): Order {
            $order = Order::query()->with(['country', 'currency'])->lockForUpdate()->findOrFail($orderId);

            if ($order->buyer_id !== $buyer->id || ! $buyer->canUseMarketplaceCountry($order->country_id)) {
                throw ValidationException::withMessages(['order' => 'This order is not available for confirmation.']);
            }
            if ($buyer->status !== 'active' || $buyer->verification_status !== 'verified') {
                throw ValidationException::withMessages(['buyer' => 'A verified active account is required for cash on delivery.']);
            }
            if ($order->payment_method !== 'cash_on_delivery' || $order->status !== 'awaiting_cod_confirmation') {
                throw ValidationException::withMessages(['order' => 'This order cannot be confirmed for cash on delivery.']);
            }
            if (! $order->country->cash_on_delivery_enabled) {
                throw ValidationException::withMessages(['order' => 'Cash on delivery is unavailable in this marketplace.']);
            }
            if ($order->winner_confirmation_expires_at !== null && now()->greaterThan($order->winner_confirmation_expires_at)) {
                throw ValidationException::withMessages(['order' => 'The winner confirmation deadline has passed.']);
            }

            $preference = $attributes['fulfilment_preference'];
            if ($preference === 'external' && empty($attributes['shipping_address'])) {
                throw ValidationException::withMessages(['shipping_address' => 'A delivery address is required for delivery orders.']);
            }

            $order->update([
                'winner_confirmed_at' => now(),
                'fulfilment_preference' => $preference,
                'status' => 'cod_confirmed',
                'collection_status' => 'awaiting_collection',
            ]);

            Payment::query()->firstOrCreate(
                ['order_id' => $order->id, 'gateway' => 'cash_on_delivery'],
                [
                    'country_id' => $order->country_id,
                    'amount' => (string) $order->amount,
                    'currency' => $order->currency->code,
                    'status' => 'awaiting_collection',
                    'payload' => [
                        'fulfilment_preference' => $preference,
                        'shipping_address' => $preference === 'external' ? $attributes['shipping_address'] : null,
                    ],
                ],
            );

            return $order->fresh(['payments', 'shipment']);
        }, 3);
    }
}

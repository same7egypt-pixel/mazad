<?php

namespace App\Domain\Shipping\Services;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingProvider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateShipment
{
    /** @param array{fulfilment_type: string, provider_id?: int|null, tracking_number?: string|null, shipping_address?: array<string, mixed>|null} $attributes */
    public function handle(int $orderId, User $operator, array $attributes): Shipment
    {
        return DB::transaction(function () use ($orderId, $operator, $attributes): Shipment {
            $order = Order::query()->with('shipment')->lockForUpdate()->findOrFail($orderId);

            if (! $operator->can('shipping.manage') || (! $operator->hasRole('GLOBAL_SUPER_ADMIN') && $operator->country_id !== $order->country_id)) {
                throw ValidationException::withMessages(['order' => 'You are not permitted to fulfil this order.']);
            }
            if ($order->status !== 'paid') {
                throw ValidationException::withMessages(['order' => 'A shipment can only be created after payment succeeds.']);
            }
            if ($order->shipment !== null) {
                throw ValidationException::withMessages(['order' => 'This order already has a shipment.']);
            }

            $type = $attributes['fulfilment_type'];
            $providerId = $attributes['provider_id'] ?? null;
            if ($type === 'external') {
                $provider = ShippingProvider::query()->whereKey($providerId)->where('country_id', $order->country_id)->where('is_active', true)->first();
                if ($provider === null || $provider->provider_type !== 'external') {
                    throw ValidationException::withMessages(['provider_id' => 'An active external provider for this country is required.']);
                }
            } elseif ($providerId !== null) {
                throw ValidationException::withMessages(['provider_id' => 'Only external shipments may specify a provider.']);
            }

            $shipment = Shipment::query()->create([
                'order_id' => $order->id,
                'provider_id' => $providerId,
                'fulfilment_type' => $type,
                'tracking_number' => $attributes['tracking_number'] ?? null,
                'status' => 'pending',
                'shipping_address' => $type === 'self_pickup' ? null : ($attributes['shipping_address'] ?? null),
            ]);
            $order->update(['status' => 'fulfillment_pending']);

            return $shipment->fresh(['provider', 'order']);
        }, 3);
    }
}

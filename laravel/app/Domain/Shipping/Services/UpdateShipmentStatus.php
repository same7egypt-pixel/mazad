<?php

namespace App\Domain\Shipping\Services;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateShipmentStatus
{
    public function handle(int $shipmentId, User $operator, string $status, ?string $trackingNumber = null): Shipment
    {
        return DB::transaction(function () use ($shipmentId, $operator, $status, $trackingNumber): Shipment {
            $shipment = Shipment::query()->with('order')->lockForUpdate()->findOrFail($shipmentId);
            $order = $shipment->order;

            if (! $operator->can('shipping.manage') || (! $operator->hasRole('GLOBAL_SUPER_ADMIN') && $operator->country_id !== $order->country_id)) {
                throw ValidationException::withMessages(['shipment' => 'You are not permitted to update this shipment.']);
            }
            if ($shipment->status === $status) {
                return $shipment;
            }

            $allowed = match ($shipment->status) {
                'pending' => ['prepared'],
                'prepared' => $shipment->fulfilment_type === 'self_pickup' ? ['ready_for_pickup'] : ['shipped'],
                'shipped', 'ready_for_pickup' => ['delivered'],
                default => [],
            };
            if (! in_array($status, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'This shipment status transition is not allowed.']);
            }
            if ($status === 'shipped' && $shipment->fulfilment_type === 'external' && ($trackingNumber ?? $shipment->tracking_number) === null) {
                throw ValidationException::withMessages(['tracking_number' => 'External shipments require a tracking number before shipping.']);
            }

            $updates = ['status' => $status];
            if ($trackingNumber !== null) {
                $updates['tracking_number'] = $trackingNumber;
            }
            if ($status === 'shipped') {
                $updates['shipped_at'] = now();
                $order->update(['status' => 'shipped']);
            }
            if ($status === 'ready_for_pickup') {
                $order->update(['status' => 'ready_for_pickup']);
            }
            if ($status === 'delivered') {
                $updates['delivered_at'] = now();
                if ($order->status !== 'completed') {
                    $order->update(['status' => 'completed', 'completed_at' => now()]);
                }
            }

            $shipment->update($updates);

            return $shipment->fresh(['provider', 'order']);
        }, 3);
    }
}

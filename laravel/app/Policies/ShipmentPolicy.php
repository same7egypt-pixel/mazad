<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function manage(User $user, Shipment $shipment): bool
    {
        return $user->can('shipping.manage') && ($user->hasRole('GLOBAL_SUPER_ADMIN') || $user->country_id === $shipment->order->country_id);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Domain\Shipping\Services\CreateShipment;
use App\Domain\Shipping\Services\UpdateShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shipping\StoreShipmentRequest;
use App\Http\Requests\Shipping\UpdateShipmentStatusRequest;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;

class ShipmentController extends Controller
{
    public function store(Order $order, StoreShipmentRequest $request, MarketplaceContext $context, CreateShipment $service): JsonResponse
    {
        $context->assertMatches($order->country_id);
        $shipment = $service->handle($order->id, $request->user(), $request->validated());

        return response()->json(['shipment' => $shipment], 201);
    }

    public function updateStatus(Shipment $shipment, UpdateShipmentStatusRequest $request, MarketplaceContext $context, UpdateShipmentStatus $service): JsonResponse
    {
        $shipment->loadMissing('order');
        $context->assertMatches($shipment->order->country_id);
        $shipment = $service->handle($shipment->id, $request->user(), $request->string('status')->toString(), $request->string('tracking_number')->toString() ?: null);

        return response()->json(['shipment' => $shipment]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Domain\Payments\Services\ConfirmCashOnDeliveryOrder;
use App\Domain\Payments\Services\ConfirmCashOnDeliveryReceipt;
use App\Domain\Payments\Services\MarkCashOnDeliveryCollectionFailed;
use App\Domain\Payments\Services\RecordCashOnDeliveryCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\ConfirmCashOnDeliveryOrderRequest;
use App\Http\Requests\Payments\MarkCashOnDeliveryCollectionFailedRequest;
use App\Http\Requests\Payments\RecordCashOnDeliveryCollectionRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashOnDeliveryController extends Controller
{
    public function confirm(Order $order, ConfirmCashOnDeliveryOrderRequest $request, MarketplaceContext $context, ConfirmCashOnDeliveryOrder $service): JsonResponse
    {
        $context->assertMatches($order->country_id);
        $order = $service->handle($order->id, $request->user(), $request->validated());

        return response()->json(['order' => $order]);
    }

    public function recordCollection(Order $order, RecordCashOnDeliveryCollectionRequest $request, MarketplaceContext $context, RecordCashOnDeliveryCollection $service): JsonResponse
    {
        $context->assertMatches($order->country_id);
        $order = $service->handle($order->id, $request->user(), $request->string('collection_reference')->toString() ?: null);

        return response()->json(['order' => $order]);
    }

    public function markCollectionFailed(Order $order, MarkCashOnDeliveryCollectionFailedRequest $request, MarketplaceContext $context, MarkCashOnDeliveryCollectionFailed $service): JsonResponse
    {
        $context->assertMatches($order->country_id);
        $order = $service->handle($order->id, $request->user(), $request->string('reason')->toString());

        return response()->json(['order' => $order]);
    }

    public function confirmReceipt(Order $order, Request $request, MarketplaceContext $context, ConfirmCashOnDeliveryReceipt $service): JsonResponse
    {
        $context->assertMatches($order->country_id);
        $order = $service->handle($order->id, $request->user());

        return response()->json(['order' => $order]);
    }
}

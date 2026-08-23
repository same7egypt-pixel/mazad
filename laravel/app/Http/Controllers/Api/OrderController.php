<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request, MarketplaceContext $context): JsonResponse
    {
        $user = $request->user();

        if (! $user->canUseMarketplaceCountry($context->id())) {
            abort(403);
        }

        $orders = Order::query()
            ->with(['auction.product', 'seller', 'buyer', 'currency', 'shipment'])
            ->where('country_id', $context->id())
            ->where(fn ($query) => $query->where('buyer_id', $user->id)->orWhere('seller_id', $user->id))
            ->latest('id')
            ->paginate(20);

        return response()->json($orders);
    }
}

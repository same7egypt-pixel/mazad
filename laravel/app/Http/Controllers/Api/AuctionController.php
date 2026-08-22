<?php

namespace App\Http\Controllers\Api;

use App\Domain\Auctions\Services\CancelAuction;
use App\Domain\Auctions\Services\CreateAuction;
use App\Domain\Core\Context\MarketplaceContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auctions\StoreAuctionRequest;
use App\Models\Auction;
use Illuminate\Http\JsonResponse;

class AuctionController extends Controller
{
    public function index(MarketplaceContext $context): JsonResponse
    {
        $auctions = Auction::query()->with(['product.media', 'product.city', 'product.category', 'currency'])
            ->where('country_id', $context->id())->whereIn('status', ['upcoming', 'live'])
            ->orderBy('end_time')->paginate(20);

        return response()->json($auctions);
    }

    public function show(Auction $auction, MarketplaceContext $context): JsonResponse
    {
        $context->assertMatches($auction->country_id);
        $auction->load(['product.media', 'product.city', 'product.category', 'currency']);

        return response()->json(['auction' => $auction]);
    }

    public function bids(Auction $auction, MarketplaceContext $context): JsonResponse
    {
        $context->assertMatches($auction->country_id);
        $bids = $auction->bids()->select(['id', 'auction_id', 'amount', 'created_at'])
            ->latest('created_at')->paginate(50);

        return response()->json($bids);
    }

    public function store(StoreAuctionRequest $request, MarketplaceContext $context, CreateAuction $service): JsonResponse
    {
        $auction = $service->handle($request->user(), $request->validated(), $context);

        return response()->json(['auction' => $auction], 201);
    }

    public function cancel(Auction $auction, MarketplaceContext $context, CancelAuction $service): JsonResponse
    {
        $context->assertMatches($auction->country_id);
        $this->authorize('cancel', $auction);
        $auction = $service->handle($auction->id);

        return response()->json(['auction' => $auction]);
    }
}

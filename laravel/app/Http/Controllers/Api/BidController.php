<?php

namespace App\Http\Controllers\Api;

use App\Domain\Auctions\Services\PlaceBid;
use App\Domain\Core\Context\MarketplaceContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auctions\PlaceBidRequest;
use App\Models\Auction;
use Illuminate\Http\JsonResponse;

class BidController extends Controller
{
    public function store(PlaceBidRequest $request, Auction $auction, MarketplaceContext $context, PlaceBid $placeBid): JsonResponse
    {
        $context->assertMatches($auction->country_id);
        $bid = $placeBid->handle($auction->id, $request->user(), $request->string('amount')->toString());
        return response()->json(['bid' => $bid->load('auction')], 201);
    }
}

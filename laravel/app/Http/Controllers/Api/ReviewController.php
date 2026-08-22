<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Domain\Reviews\Services\CreateReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reviews\StoreReviewRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function store(Order $order, StoreReviewRequest $request, MarketplaceContext $context, CreateReview $service): JsonResponse
    {
        $context->assertMatches($order->country_id);
        $review = $service->handle($order->id, $request->user(), $request->integer('rating'), $request->string('comment')->toString() ?: null);

        return response()->json(['review' => $review], 201);
    }
}

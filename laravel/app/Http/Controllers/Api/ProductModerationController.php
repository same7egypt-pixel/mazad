<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductModerationController extends Controller
{
    public function approve(Request $request, Product $product, MarketplaceContext $context): JsonResponse
    {
        $context->assertMatches($product->country_id);
        $this->authorize('approve', $product);
        if ($product->status !== 'pending_review') return response()->json(['message' => 'Only products pending review may be approved.'], 422);
        $product->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => $request->user()->id]);
        return response()->json(['product' => $product->fresh()]);
    }

    public function reject(Request $request, Product $product, MarketplaceContext $context): JsonResponse
    {
        $context->assertMatches($product->country_id);
        $this->authorize('approve', $product);
        if ($product->status !== 'pending_review') return response()->json(['message' => 'Only products pending review may be rejected.'], 422);
        $product->update(['status' => 'rejected']);
        return response()->json(['product' => $product->fresh()]);
    }
}

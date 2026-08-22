<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request, MarketplaceContext $context): JsonResponse
    {
        $products = Product::query()->with(['media', 'city', 'category', 'currency'])
            ->where('country_id', $context->id())->where('status', 'active')
            ->when($request->integer('city_id'), fn ($q, $cityId) => $q->where('city_id', $cityId))
            ->when($request->integer('category_id'), fn ($q, $categoryId) => $q->where('category_id', $categoryId))
            ->when($request->string('condition')->toString(), fn ($q, $condition) => $q->where('condition', $condition))
            ->latest()->paginate(20);

        return response()->json($products);
    }

    public function store(StoreProductRequest $request, MarketplaceContext $context): JsonResponse
    {
        $product = Product::query()->create($request->validated() + [
            'user_id' => $request->user()->id,
            'country_id' => $context->id(),
            'currency_id' => $context->country()->currency_id,
            'status' => 'draft',
        ]);

        return response()->json(['product' => $product], 201);
    }

    public function mine(Request $request, MarketplaceContext $context): JsonResponse
    {
        $user = $request->user();

        if ($user->country_id !== $context->id()) {
            abort(403);
        }

        $products = Product::query()
            ->with(['media', 'city', 'category', 'currency', 'auction'])
            ->where('country_id', $context->id())
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate(20);

        return response()->json($products);
    }

    public function submitForReview(Request $request, Product $product, MarketplaceContext $context): JsonResponse
    {
        $context->assertMatches($product->country_id);
        $this->authorize('update', $product);

        if ($product->status !== 'draft') {
            return response()->json(['message' => 'Only draft products may be submitted for review.'], 422);
        }
        if (! $product->media()->exists()) {
            return response()->json(['message' => 'At least one product media item is required before review.'], 422);
        }

        $product->update(['status' => 'pending_review']);

        return response()->json(['product' => $product->fresh()]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchListingsRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ListingSearchController extends Controller
{
    public function index(SearchListingsRequest $request, MarketplaceContext $context): JsonResponse
    {
        $filters = $request->validated();
        $query = Product::query()
            ->with(['media', 'city', 'category', 'currency', 'auction'])
            ->where('products.country_id', $context->id())
            ->where('products.status', 'active')
            ->when($filters['city_id'] ?? null, fn ($builder, $cityId) => $builder->where('products.city_id', $cityId))
            ->when($filters['category_id'] ?? null, fn ($builder, $categoryId) => $builder->where('products.category_id', $categoryId))
            ->when($filters['condition'] ?? null, fn ($builder, $condition) => $builder->where('products.condition', $condition))
            ->when($filters['q'] ?? null, function ($builder, $term): void {
                $builder->where(function ($nested) use ($term): void {
                    $nested->where('products.title', 'ilike', "%{$term}%")
                        ->orWhere('products.description', 'ilike', "%{$term}%");
                });
            })
            ->when(($filters['price_min'] ?? null) !== null || ($filters['price_max'] ?? null) !== null, function ($builder) use ($filters): void {
                $builder->whereHas('auction', function ($auctionQuery) use ($filters): void {
                    $auctionQuery->where('status', 'live')
                        ->when($filters['price_min'] ?? null, fn ($nested, $price) => $nested->where('current_price', '>=', $price))
                        ->when($filters['price_max'] ?? null, fn ($nested, $price) => $nested->where('current_price', '<=', $price));
                });
            });

        return response()->json($query->latest('products.id')->paginate(20));
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class MarketplaceReferenceController extends Controller
{
    public function show(Country $country): JsonResponse
    {
        abort_unless($country->is_active, 404);

        return response()->json([
            'country' => $country->only(['id', 'name', 'code', 'timezone']),
            'currency' => $country->currency?->only(['id', 'name', 'code', 'symbol', 'decimal_places']),
            'cities' => City::query()->where('country_id', $country->id)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query->where('country_id', $country->id)->orWhereNull('country_id'))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'parent_id']),
        ]);
    }
}

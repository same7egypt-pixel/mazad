<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class MarketplaceCountryController extends Controller
{
    public function index(): JsonResponse
    {
        $countries = Country::query()
            ->with('currency:id,code,symbol,decimal_places')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Country $country): array => [
                'id' => $country->id,
                'name' => $country->name,
                'code' => $country->code,
                'timezone' => $country->timezone,
                'currency' => $country->currency === null ? null : [
                    'code' => $country->currency->code,
                    'symbol' => $country->currency->symbol,
                    'decimal_places' => $country->currency->decimal_places,
                ],
            ]);

        return response()->json(['countries' => $countries]);
    }
}

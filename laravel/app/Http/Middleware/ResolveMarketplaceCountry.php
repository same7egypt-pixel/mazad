<?php

namespace App\Http\Middleware;

use App\Domain\Core\Context\MarketplaceContext;
use App\Models\Country;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveMarketplaceCountry
{
    public function handle(Request $request, Closure $next): Response
    {
        $countryId = $request->header('X-Marketplace-Country');

        if (! ctype_digit((string) $countryId)) {
            return response()->json(['message' => 'A valid X-Marketplace-Country header is required.'], 422);
        }

        $country = Country::query()->whereKey((int) $countryId)->where('is_active', true)->first();

        if ($country === null) {
            return response()->json(['message' => 'The selected marketplace country is unavailable.'], 422);
        }

        app(MarketplaceContext::class)->setCountry($country);

        return $next($request);
    }
}

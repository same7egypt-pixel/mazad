<?php

namespace App\Providers;

use App\Domain\Core\Context\MarketplaceContext;
use App\Models\Auction;
use App\Models\Product;
use App\Models\Shipment;
use App\Policies\AuctionPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ShipmentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(MarketplaceContext::class, fn (): MarketplaceContext => new MarketplaceContext);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Auction::class, AuctionPolicy::class);
        Gate::policy(Shipment::class, ShipmentPolicy::class);

        RateLimiter::for('auction-bids', function (Request $request): Limit {
            return Limit::perMinute(30)->by(($request->user()?->getKey() ?? 'guest').':'.$request->ip());
        });

        RateLimiter::for('marketplace-auth', function (Request $request): Limit {
            $country = $request->header('X-Marketplace-Country', 'unresolved');
            $email = Str::lower(trim($request->input('email', '')));

            return Limit::perMinute(6)->by(implode(':', ['marketplace-auth', $country, $email ?: 'no-email', $request->ip()]));
        });
    }
}

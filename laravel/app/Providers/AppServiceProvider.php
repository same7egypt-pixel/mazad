<?php

namespace App\Providers;

use App\Domain\Core\Context\MarketplaceContext;
use App\Models\Auction;
use App\Models\Product;
use App\Policies\AuctionPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(MarketplaceContext::class, fn (): MarketplaceContext => new MarketplaceContext());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Auction::class, AuctionPolicy::class);

        RateLimiter::for('auction-bids', function (Request $request): Limit {
            return Limit::perMinute(30)->by(($request->user()?->getKey() ?? 'guest').':'.$request->ip());
        });
    }
}

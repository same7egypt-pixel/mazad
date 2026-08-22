<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use Database\Seeders\MarketplaceReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceReferenceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_only_idempotent_saudi_marketplace_reference_data(): void
    {
        $this->seed(MarketplaceReferenceSeeder::class);
        $this->seed(MarketplaceReferenceSeeder::class);

        $currency = Currency::query()->where('code', 'SAR')->sole();
        $country = Country::query()->where('code', 'SA')->sole();

        $this->assertSame($currency->id, $country->currency_id);
        $this->assertSame(3, City::query()->where('country_id', $country->id)->count());
        $this->assertSame(4, Category::query()->where('country_id', $country->id)->count());
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('auctions', 0);
        $this->assertDatabaseCount('reviews', 0);
    }
}

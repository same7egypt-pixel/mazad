<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_marketplace_exposes_only_active_cities_and_categories_for_seller_reference_data(): void
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        City::query()->create(['country_id' => $country->id, 'name' => 'Riyadh', 'is_active' => true]);
        City::query()->create(['country_id' => $country->id, 'name' => 'Inactive city', 'is_active' => false]);
        Category::query()->create(['country_id' => $country->id, 'name' => 'Watches', 'slug' => 'watches', 'is_active' => true]);
        Category::query()->create(['country_id' => $country->id, 'name' => 'Inactive category', 'slug' => 'inactive-category', 'is_active' => false]);

        $this->getJson("/api/marketplaces/{$country->id}/references")
            ->assertOk()
            ->assertJsonPath('currency.code', 'SAR')
            ->assertJsonCount(1, 'cities')
            ->assertJsonCount(1, 'categories')
            ->assertJsonPath('cities.0.name', 'Riyadh')
            ->assertJsonPath('categories.0.slug', 'watches');
    }
}

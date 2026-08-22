<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerProductListTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_product_list_is_limited_to_the_authenticated_seller_and_marketplace_country(): void
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $saudiArabia = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $egypt = Country::query()->create([
            'name' => 'Egypt',
            'code' => 'EG',
            'timezone' => 'Africa/Cairo',
            'currency_id' => $currency->id,
        ]);
        $riyadh = City::query()->create(['country_id' => $saudiArabia->id, 'name' => 'Riyadh']);
        $cairo = City::query()->create(['country_id' => $egypt->id, 'name' => 'Cairo']);
        $saudiCategory = Category::query()->create(['country_id' => $saudiArabia->id, 'name' => 'Watches', 'slug' => 'watches']);
        $egyptCategory = Category::query()->create(['country_id' => $egypt->id, 'name' => 'Art', 'slug' => 'art']);
        $seller = User::factory()->create(['country_id' => $saudiArabia->id, 'city_id' => $riyadh->id]);
        $otherSeller = User::factory()->create(['country_id' => $saudiArabia->id, 'city_id' => $riyadh->id]);
        $foreignSeller = User::factory()->create(['country_id' => $egypt->id, 'city_id' => $cairo->id]);

        $sellerProduct = Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $saudiArabia->id,
            'city_id' => $riyadh->id,
            'category_id' => $saudiCategory->id,
            'currency_id' => $currency->id,
            'title' => 'Seller watch',
            'description' => 'A seller-owned product visible only to its seller.',
            'condition' => 'excellent',
            'status' => 'approved',
        ]);
        Product::query()->create([
            'user_id' => $otherSeller->id,
            'country_id' => $saudiArabia->id,
            'city_id' => $riyadh->id,
            'category_id' => $saudiCategory->id,
            'currency_id' => $currency->id,
            'title' => 'Other seller watch',
            'description' => 'A product that must not appear in this seller list.',
            'condition' => 'excellent',
            'status' => 'draft',
        ]);
        Product::query()->create([
            'user_id' => $foreignSeller->id,
            'country_id' => $egypt->id,
            'city_id' => $cairo->id,
            'category_id' => $egyptCategory->id,
            'currency_id' => $currency->id,
            'title' => 'Foreign art',
            'description' => 'A product from a different marketplace country.',
            'condition' => 'excellent',
            'status' => 'approved',
        ]);

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/my/products', ['X-Marketplace-Country' => (string) $saudiArabia->id])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sellerProduct->id)
            ->assertJsonPath('data.0.status', 'approved');

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/my/products', ['X-Marketplace-Country' => (string) $egypt->id])
            ->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BidTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_bid_endpoint_accepts_a_matching_sanctum_token_and_marketplace_country(): void
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Riyadh']);
        $category = Category::query()->create(['country_id' => $country->id, 'name' => 'Watches', 'slug' => 'watches']);
        $seller = User::factory()->create(['country_id' => $country->id, 'city_id' => $city->id]);
        $bidder = User::factory()->create([
            'country_id' => $country->id,
            'city_id' => $city->id,
            'status' => 'active',
            'verification_status' => 'verified',
        ]);
        $bidder->givePermissionTo(Permission::findOrCreate('auctions.bid', 'web'));
        $product = Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'currency_id' => $currency->id,
            'title' => 'Live token bid watch',
            'description' => 'A product used to verify the Sanctum token bid API contract.',
            'condition' => 'good',
            'status' => 'active',
        ]);
        $auction = Auction::query()->create([
            'product_id' => $product->id,
            'country_id' => $country->id,
            'currency_id' => $currency->id,
            'starting_price' => '100.00',
            'current_price' => '100.00',
            'minimum_increment' => '10.00',
            'start_time' => now()->subMinute(),
            'end_time' => now()->addHour(),
            'status' => 'live',
        ]);
        $token = $bidder->createToken('Marketplace bid API test', ['marketplace:access'])->plainTextToken;

        $this->postJson("/api/auctions/{$auction->id}/bids", ['amount' => '110.00'], [
            'Authorization' => "Bearer {$token}",
            'X-Marketplace-Country' => (string) $country->id,
        ])->assertCreated()
            ->assertJsonPath('bid.auction_id', $auction->id)
            ->assertJsonPath('bid.amount', '110.00');

        $this->assertDatabaseHas('bids', ['auction_id' => $auction->id, 'user_id' => $bidder->id, 'amount' => '110.00']);
        $this->assertDatabaseHas('auctions', ['id' => $auction->id, 'current_price' => '110.00', 'winner_id' => $bidder->id, 'bid_count' => 1]);
    }
}

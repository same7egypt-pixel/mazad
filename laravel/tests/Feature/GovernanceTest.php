<?php

namespace Tests\Feature;

use App\Domain\Governance\Services\RecordBidActivity;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bid_velocity_creates_a_reviewable_signal_and_governance_apis_are_country_scoped(): void
    {
        $market = $this->marketplace();
        $otherMarket = $this->marketplace('OTS', 'OT', 'Otherland');
        $seller = $this->user($market, []);
        $bidder = $this->user($market, []);
        $reviewer = $this->user($market, ['fraud.review', 'audit.view']);
        $otherReviewer = $this->user($otherMarket, ['fraud.review']);
        $auction = $this->auction($market, $seller);

        Bid::query()->insert(collect(range(1, 4))->map(fn (int $index) => [
            'auction_id' => $auction->id,
            'user_id' => $bidder->id,
            'amount' => (string) (100 + $index * 10),
            'created_at' => now(),
        ])->all());
        $bid = Bid::query()->create(['auction_id' => $auction->id, 'user_id' => $bidder->id, 'amount' => '150.00']);

        app(RecordBidActivity::class)->handle($auction, $bid, $bidder);

        $this->assertDatabaseHas('user_activities', ['user_id' => $bidder->id, 'country_id' => $market['country']->id, 'event' => 'fraud.suspicious_bid_velocity']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $bidder->id, 'country_id' => $market['country']->id, 'event' => 'auction.bid_placed']);

        $this->actingAs($reviewer, 'sanctum')
            ->withHeader('X-Marketplace-Country', (string) $market['country']->id)
            ->getJson('/api/governance/fraud-signals')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($otherReviewer, 'sanctum')
            ->withHeader('X-Marketplace-Country', (string) $market['country']->id)
            ->getJson('/api/governance/fraud-signals')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('authorization');
    }

    /** @return array{currency: Currency, country: Country, city: City, category: Category} */
    private function marketplace(string $currencyCode = 'TST', string $countryCode = 'TS', string $countryName = 'Testland'): array
    {
        $suffix = strtolower($countryCode);
        $currency = Currency::query()->create(['name' => $countryName.' Dinar', 'code' => $currencyCode, 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]);
        $country = Country::query()->create(['name' => $countryName, 'code' => $countryCode, 'timezone' => 'UTC', 'currency_id' => $currency->id, 'is_active' => true]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => $countryName.' City', 'is_active' => true]);
        $category = Category::query()->create(['country_id' => $country->id, 'name' => $countryName.' Category', 'slug' => 'category-'.$suffix, 'is_active' => true]);

        return compact('currency', 'country', 'city', 'category');
    }

    /** @param array{currency: Currency, country: Country, city: City, category: Category} $market */
    private function user(array $market, array $permissions): User
    {
        $suffix = (string) Str::uuid();
        $user = User::query()->create([
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'name' => 'Governance test user',
            'email' => $suffix.'@auction.test',
            'password' => 'password',
            'status' => 'active',
            'verification_status' => 'verified',
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    /** @param array{currency: Currency, country: Country, city: City, category: Category} $market */
    private function auction(array $market, User $seller): Auction
    {
        $product = Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'category_id' => $market['category']->id,
            'currency_id' => $market['currency']->id,
            'title' => 'Governance test listing',
            'description' => 'Listing used only to validate marketplace activity controls.',
            'condition' => 'good',
            'status' => 'active',
        ]);

        return Auction::query()->create([
            'product_id' => $product->id,
            'country_id' => $market['country']->id,
            'currency_id' => $market['currency']->id,
            'starting_price' => '100.00',
            'current_price' => '100.00',
            'minimum_increment' => '10.00',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'status' => 'live',
        ]);
    }
}

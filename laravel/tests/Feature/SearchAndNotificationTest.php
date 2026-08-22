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
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_search_applies_country_text_category_condition_and_price_filters(): void
    {
        $market = $this->marketplace();
        $otherMarket = $this->marketplace('OTS', 'OT', 'Otherland');
        $seller = $this->user($market);
        $otherSeller = $this->user($otherMarket);
        $matchingProduct = $this->activeProduct($market, $seller, 'Camera available', 'good', '125.00');
        $this->activeProduct($market, $seller, 'Camera premium', 'new', '250.00');
        $this->activeProduct($otherMarket, $otherSeller, 'Camera available', 'good', '100.00');

        $response = $this->withHeader('X-Marketplace-Country', (string) $market['country']->id)
            ->getJson('/api/listings/search?'.http_build_query([
                'q' => 'camera',
                'city_id' => $market['city']->id,
                'category_id' => $market['category']->id,
                'condition' => 'good',
                'price_max' => '150.00',
            ]));

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $matchingProduct->id);
    }

    public function test_notification_inbox_is_country_scoped_and_read_state_cannot_cross_markets(): void
    {
        $market = $this->marketplace();
        $otherMarket = $this->marketplace('OTS', 'OT', 'Otherland');
        $user = $this->user($market);
        $inMarket = $this->databaseNotification($user, $market['country']->id);
        $otherMarketNotification = $this->databaseNotification($user, $otherMarket['country']->id);

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Marketplace-Country', (string) $market['country']->id)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inMarket->id);

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Marketplace-Country', (string) $market['country']->id)
            ->postJson('/api/notifications/'.$inMarket->id.'/read')
            ->assertOk()
            ->assertJsonPath('notification.id', $inMarket->id);

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Marketplace-Country', (string) $market['country']->id)
            ->postJson('/api/notifications/'.$otherMarketNotification->id.'/read')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('notification');
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
    private function user(array $market): User
    {
        $suffix = (string) Str::uuid();

        return User::query()->create([
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'name' => 'Search test user',
            'email' => $suffix.'@auction.test',
            'password' => 'password',
            'status' => 'active',
            'verification_status' => 'verified',
        ]);
    }

    /** @param array{currency: Currency, country: Country, city: City, category: Category} $market */
    private function activeProduct(array $market, User $seller, string $title, string $condition, string $price): Product
    {
        $product = Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'category_id' => $market['category']->id,
            'currency_id' => $market['currency']->id,
            'title' => $title,
            'description' => 'Searchable listing description for the marketplace.',
            'condition' => $condition,
            'status' => 'active',
        ]);
        Auction::query()->create([
            'product_id' => $product->id,
            'country_id' => $market['country']->id,
            'currency_id' => $market['currency']->id,
            'starting_price' => $price,
            'current_price' => $price,
            'minimum_increment' => '5.00',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'status' => 'live',
        ]);

        return $product;
    }

    private function databaseNotification(User $user, int $countryId): DatabaseNotification
    {
        return DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test.notice',
            'notifiable_type' => $user::class,
            'notifiable_id' => $user->id,
            'data' => ['country_id' => $countryId],
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Domain\Reviews\Services\CreateReview;
use App\Models\Auction;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReviewEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_completed_order_participant_with_permission_is_eligible_to_review(): void
    {
        $market = $this->marketplace();
        $seller = $this->user($market, []);
        $buyer = $this->user($market, ['reviews.create']);
        $outsider = $this->user($market, ['reviews.create']);
        $order = $this->order($market, $seller, $buyer, 'paid');
        $service = app(CreateReview::class);

        try {
            $service->reviewedUserId($order, $buyer);
            self::fail('A non-completed order must not be reviewable.');
        } catch (ValidationException) {
            self::assertTrue(true);
        }

        $order->update(['status' => 'completed', 'completed_at' => now()]);
        self::assertSame($seller->id, $service->reviewedUserId($order->fresh(), $buyer));

        $this->expectException(ValidationException::class);
        $service->reviewedUserId($order->fresh(), $outsider);
    }

    /** @return array{currency: Currency, country: Country, city: City, category: Category} */
    private function marketplace(): array
    {
        $currency = Currency::query()->create(['name' => 'Test Dinar', 'code' => 'TST', 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]);
        $country = Country::query()->create(['name' => 'Testland', 'code' => 'TS', 'timezone' => 'UTC', 'currency_id' => $currency->id, 'is_active' => true]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Test City', 'is_active' => true]);
        $category = Category::query()->create(['country_id' => $country->id, 'name' => 'Test Category', 'slug' => 'test-category', 'is_active' => true]);

        return compact('currency', 'country', 'city', 'category');
    }

    /** @param array{currency: Currency, country: Country, city: City, category: Category} $market */
    private function user(array $market, array $permissions): User
    {
        $suffix = (string) Str::uuid();
        $user = User::query()->create([
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'name' => 'Review eligibility user',
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
    private function order(array $market, User $seller, User $buyer, string $status): Order
    {
        $product = Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'category_id' => $market['category']->id,
            'currency_id' => $market['currency']->id,
            'title' => 'Eligibility test listing',
            'description' => 'Listing used to verify non-content review eligibility rules.',
            'condition' => 'good',
            'status' => 'sold',
        ]);
        $auction = Auction::query()->create([
            'product_id' => $product->id,
            'country_id' => $market['country']->id,
            'currency_id' => $market['currency']->id,
            'starting_price' => '100.00',
            'current_price' => '100.00',
            'minimum_increment' => '10.00',
            'start_time' => now()->subHour(),
            'end_time' => now()->subMinute(),
            'status' => 'sold',
            'winner_id' => $buyer->id,
            'bid_count' => 1,
        ]);

        return Order::query()->create([
            'auction_id' => $auction->id,
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'country_id' => $market['country']->id,
            'currency_id' => $market['currency']->id,
            'amount' => '100.00',
            'commission_amount' => '10.00',
            'seller_amount' => '90.00',
            'status' => $status,
            'paid_at' => now(),
        ]);
    }
}

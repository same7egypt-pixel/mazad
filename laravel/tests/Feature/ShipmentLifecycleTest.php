<?php

namespace Tests\Feature;

use App\Domain\Shipping\Services\CreateShipment;
use App\Domain\Shipping\Services\UpdateShipmentStatus;
use App\Models\Auction;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ShipmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_shipment_requires_country_provider_and_completes_order_on_delivery(): void
    {
        $market = $this->marketplace();
        $operator = $this->user($market, ['shipping.manage']);
        $seller = $this->user($market, []);
        $buyer = $this->user($market, []);
        $order = $this->paidOrder($market, $seller, $buyer);
        $provider = ShippingProvider::query()->create([
            'country_id' => $market['country']->id,
            'name' => 'Test Carrier',
            'code' => 'TEST-CARRIER',
            'provider_type' => 'external',
            'configuration' => [],
            'is_active' => true,
        ]);

        $shipment = app(CreateShipment::class)->handle($order->id, $operator, [
            'fulfilment_type' => 'external',
            'provider_id' => $provider->id,
            'shipping_address' => ['line1' => '1 Marketplace Road', 'city' => 'Test City'],
        ]);

        self::assertSame('pending', $shipment->status);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'fulfillment_pending']);

        app(UpdateShipmentStatus::class)->handle($shipment->id, $operator, 'prepared');
        app(UpdateShipmentStatus::class)->handle($shipment->id, $operator, 'shipped', 'TRACK-123');
        app(UpdateShipmentStatus::class)->handle($shipment->id, $operator, 'delivered');

        $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => 'delivered', 'tracking_number' => 'TRACK-123']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
    }

    public function test_self_pickup_follows_its_own_transition_and_unprivileged_users_cannot_create_shipments(): void
    {
        $market = $this->marketplace();
        $operator = $this->user($market, ['shipping.manage']);
        $unprivilegedUser = $this->user($market, []);
        $seller = $this->user($market, []);
        $buyer = $this->user($market, []);
        $order = $this->paidOrder($market, $seller, $buyer);

        $this->expectException(ValidationException::class);
        try {
            app(CreateShipment::class)->handle($order->id, $unprivilegedUser, ['fulfilment_type' => 'self_pickup']);
        } finally {
            $shipment = app(CreateShipment::class)->handle($order->id, $operator, ['fulfilment_type' => 'self_pickup']);
            app(UpdateShipmentStatus::class)->handle($shipment->id, $operator, 'prepared');
            app(UpdateShipmentStatus::class)->handle($shipment->id, $operator, 'ready_for_pickup');
            app(UpdateShipmentStatus::class)->handle($shipment->id, $operator, 'delivered');

            $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => 'delivered']);
            $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        }
    }

    public function test_different_country_operator_and_mismatched_marketplace_header_cannot_create_a_shipment(): void
    {
        $market = $this->marketplace();
        $otherMarket = $this->marketplace('OTS', 'OT', 'Otherland');
        $operator = $this->user($market, ['shipping.manage']);
        $differentCountryOperator = $this->user($otherMarket, ['shipping.manage']);
        $seller = $this->user($market, []);
        $buyer = $this->user($market, []);
        $order = $this->paidOrder($market, $seller, $buyer);

        $this->expectException(ValidationException::class);
        try {
            app(CreateShipment::class)->handle($order->id, $differentCountryOperator, ['fulfilment_type' => 'internal']);
        } finally {
            $response = $this->actingAs($operator, 'sanctum')
                ->withHeader('X-Marketplace-Country', (string) $otherMarket['country']->id)
                ->postJson('/api/orders/'.$order->id.'/shipments', ['fulfilment_type' => 'internal']);

            $response->assertUnprocessable()->assertJsonValidationErrors('country');
            $this->assertDatabaseCount('shipments', 0);
        }
    }

    public function test_different_country_operator_and_mismatched_marketplace_header_cannot_update_shipment_status(): void
    {
        $market = $this->marketplace();
        $otherMarket = $this->marketplace('OTS', 'OT', 'Otherland');
        $operator = $this->user($market, ['shipping.manage']);
        $differentCountryOperator = $this->user($otherMarket, ['shipping.manage']);
        $seller = $this->user($market, []);
        $buyer = $this->user($market, []);
        $order = $this->paidOrder($market, $seller, $buyer);
        $shipment = app(CreateShipment::class)->handle($order->id, $operator, ['fulfilment_type' => 'internal']);

        $this->expectException(ValidationException::class);
        try {
            app(UpdateShipmentStatus::class)->handle($shipment->id, $differentCountryOperator, 'prepared');
        } finally {
            $response = $this->actingAs($operator, 'sanctum')
                ->withHeader('X-Marketplace-Country', (string) $otherMarket['country']->id)
                ->postJson('/api/shipments/'.$shipment->id.'/status', ['status' => 'prepared']);

            $response->assertUnprocessable()->assertJsonValidationErrors('country');
            $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => 'pending']);
        }
    }

    /** @return array{currency: Currency, country: Country, city: City, category: Category} */
    private function marketplace(string $currencyCode = 'TST', string $countryCode = 'TS', string $countryName = 'Testland'): array
    {
        $suffix = strtolower($countryCode);
        $currency = Currency::query()->create(['name' => $countryName.' Dinar', 'code' => $currencyCode, 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]);
        $country = Country::query()->create(['name' => $countryName, 'code' => $countryCode, 'timezone' => 'UTC', 'currency_id' => $currency->id, 'is_active' => true]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => $countryName.' City', 'is_active' => true]);
        $category = Category::query()->create(['country_id' => $country->id, 'name' => $countryName.' Category', 'slug' => 'test-category-'.$suffix, 'is_active' => true]);

        return compact('currency', 'country', 'city', 'category');
    }

    /** @param array{currency: Currency, country: Country, city: City, category: Category} $market */
    private function user(array $market, array $permissions): User
    {
        $suffix = (string) Str::uuid();
        $user = User::query()->create([
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'name' => 'Shipping test user',
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
    private function paidOrder(array $market, User $seller, User $buyer): Order
    {
        $product = Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'category_id' => $market['category']->id,
            'currency_id' => $market['currency']->id,
            'title' => 'Shipping test listing',
            'description' => str_repeat('Shipping description ', 3),
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
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}

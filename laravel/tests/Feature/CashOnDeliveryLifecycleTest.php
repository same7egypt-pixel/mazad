<?php

namespace Tests\Feature;

use App\Domain\Auctions\Services\CloseAuction;
use App\Domain\Payments\Services\ConfirmCashOnDeliveryOrder;
use App\Domain\Payments\Services\ConfirmCashOnDeliveryReceipt;
use App\Domain\Payments\Services\RecordCashOnDeliveryCollection;
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

class CashOnDeliveryLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_on_delivery_order_is_confirmed_collected_and_settled_once_after_receipt(): void
    {
        $market = $this->marketplace();
        $seller = $this->user($market, []);
        $buyer = $this->user($market, []);
        $operator = $this->user($market, ['orders.fulfill', 'shipping.manage']);
        $order = $this->cashOnDeliveryOrder($market, $seller, $buyer);

        $confirmed = app(ConfirmCashOnDeliveryOrder::class)->handle($order->id, $buyer, ['fulfilment_preference' => 'self_pickup']);

        self::assertSame('cod_confirmed', $confirmed->status);
        self::assertSame('awaiting_collection', $confirmed->collection_status);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'gateway' => 'cash_on_delivery', 'status' => 'awaiting_collection']);

        $shipment = app(CreateShipment::class)->handle($order->id, $operator, ['fulfilment_type' => 'self_pickup']);
        app(UpdateShipmentStatus::class)->handle($shipment->id, $operator, 'prepared');
        app(UpdateShipmentStatus::class)->handle($shipment->id, $operator, 'ready_for_pickup');
        app(UpdateShipmentStatus::class)->handle($shipment->id, $operator, 'delivered');

        $collected = app(RecordCashOnDeliveryCollection::class)->handle($order->id, $operator, 'COD-001');
        $secondCollection = app(RecordCashOnDeliveryCollection::class)->handle($order->id, $operator, 'COD-001');

        self::assertSame('awaiting_receipt_confirmation', $collected->status);
        self::assertSame($collected->id, $secondCollection->id);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'collected', 'collection_reference' => 'COD-001']);
        $this->assertDatabaseHas('wallets', ['user_id' => $seller->id, 'currency_id' => $market['currency']->id, 'pending_balance' => '90.00', 'available_balance' => '0.00']);
        $this->assertDatabaseCount('wallet_transactions', 1);

        $completed = app(ConfirmCashOnDeliveryReceipt::class)->handle($order->id, $buyer);
        $secondReceipt = app(ConfirmCashOnDeliveryReceipt::class)->handle($order->id, $buyer);

        self::assertSame('completed', $completed->status);
        self::assertSame($completed->id, $secondReceipt->id);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'collection_status' => 'collected', 'settlement_status' => 'released']);
        $this->assertDatabaseHas('wallets', ['user_id' => $seller->id, 'currency_id' => $market['currency']->id, 'pending_balance' => '0.00', 'available_balance' => '90.00']);
        $this->assertDatabaseCount('wallet_transactions', 2);
        $this->assertDatabaseHas('wallet_transactions', ['type' => 'sale_earning_release', 'reference_id' => $order->id, 'status' => 'completed']);
    }

    public function test_operator_from_another_marketplace_cannot_record_cash_collection(): void
    {
        $market = $this->marketplace();
        $otherMarket = $this->marketplace('Otherland', 'OT');
        $seller = $this->user($market, []);
        $buyer = $this->user($market, []);
        $otherOperator = $this->user($otherMarket, ['orders.fulfill']);
        $order = $this->cashOnDeliveryOrder($market, $seller, $buyer);
        app(ConfirmCashOnDeliveryOrder::class)->handle($order->id, $buyer, ['fulfilment_preference' => 'self_pickup']);

        $this->expectException(ValidationException::class);

        app(RecordCashOnDeliveryCollection::class)->handle($order->id, $otherOperator, 'COD-OTHER');
    }

    public function test_winner_can_confirm_cash_on_delivery_through_the_country_scoped_api(): void
    {
        $market = $this->marketplace();
        $seller = $this->user($market, []);
        $buyer = $this->user($market, []);
        $order = $this->cashOnDeliveryOrder($market, $seller, $buyer);

        $this->actingAs($buyer, 'sanctum')
            ->withHeader('X-Marketplace-Country', (string) $market['country']->id)
            ->postJson("/api/orders/{$order->id}/cash-on-delivery/confirm", [
                'fulfilment_preference' => 'external',
                'shipping_address' => [
                    'address_line' => 'King Road 10',
                    'city' => 'Test City',
                    'phone' => '0500000000',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('order.status', 'cod_confirmed');

        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'gateway' => 'cash_on_delivery', 'status' => 'awaiting_collection']);
    }

    public function test_unverified_winner_cannot_confirm_cash_on_delivery_order(): void
    {
        $market = $this->marketplace();
        $seller = $this->user($market, []);
        $buyer = $this->user($market, []);
        $buyer->update(['verification_status' => 'pending']);
        $order = $this->cashOnDeliveryOrder($market, $seller, $buyer);

        $this->expectException(ValidationException::class);

        app(ConfirmCashOnDeliveryOrder::class)->handle($order->id, $buyer, ['fulfilment_preference' => 'self_pickup']);
    }

    public function test_winning_auction_creates_cash_on_delivery_order_for_enabled_marketplace(): void
    {
        $market = $this->marketplace();
        $seller = $this->user($market, []);
        $buyer = $this->user($market, []);
        $product = Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'category_id' => $market['category']->id,
            'currency_id' => $market['currency']->id,
            'title' => 'Winning COD listing',
            'description' => str_repeat('Winning COD listing description ', 3),
            'condition' => 'good',
            'status' => 'active',
        ]);
        $auction = Auction::query()->create([
            'product_id' => $product->id,
            'country_id' => $market['country']->id,
            'currency_id' => $market['currency']->id,
            'starting_price' => '100.00',
            'current_price' => '120.00',
            'minimum_increment' => '10.00',
            'start_time' => now()->subHours(2),
            'end_time' => now()->subMinute(),
            'status' => 'live',
            'winner_id' => $buyer->id,
            'bid_count' => 1,
        ]);

        $order = app(CloseAuction::class)->handle($auction->id);

        self::assertNotNull($order);
        self::assertSame('cash_on_delivery', $order->payment_method);
        self::assertSame('awaiting_cod_confirmation', $order->status);
        self::assertSame('awaiting_confirmation', $order->collection_status);
        self::assertNotNull($order->winner_confirmation_expires_at);
    }

    public function test_external_cash_on_delivery_shipment_reuses_the_confirmed_delivery_address(): void
    {
        $market = $this->marketplace();
        $seller = $this->user($market, []);
        $buyer = $this->user($market, []);
        $operator = $this->user($market, ['orders.fulfill', 'shipping.manage']);
        $order = $this->cashOnDeliveryOrder($market, $seller, $buyer);
        app(ConfirmCashOnDeliveryOrder::class)->handle($order->id, $buyer, [
            'fulfilment_preference' => 'external',
            'shipping_address' => ['address_line' => 'King Road 10', 'city' => 'Test City', 'phone' => '0500000000'],
        ]);
        $provider = ShippingProvider::query()->create([
            'country_id' => $market['country']->id,
            'name' => 'Test Carrier',
            'code' => 'test-carrier',
            'provider_type' => 'external',
            'is_active' => true,
        ]);

        $shipment = app(CreateShipment::class)->handle($order->id, $operator, [
            'fulfilment_type' => 'external',
            'provider_id' => $provider->id,
        ]);

        self::assertSame('King Road 10', $shipment->shipping_address['address_line']);
        self::assertSame('0500000000', $shipment->shipping_address['phone']);
    }

    /** @return array{currency: Currency, country: Country, city: City, category: Category} */
    private function marketplace(string $countryName = 'Testland', string $countryCode = 'TS'): array
    {
        $currency = Currency::query()->create(['name' => $countryName.' Dinar', 'code' => $countryCode.'D', 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]);
        $country = Country::query()->create(['name' => $countryName, 'code' => $countryCode, 'timezone' => 'UTC', 'currency_id' => $currency->id, 'cash_on_delivery_enabled' => true, 'is_active' => true]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => $countryName.' City', 'is_active' => true]);
        $category = Category::query()->create(['country_id' => $country->id, 'name' => $countryName.' Category', 'slug' => Str::slug($countryName.' category'), 'is_active' => true]);

        return compact('currency', 'country', 'city', 'category');
    }

    /** @param array{currency: Currency, country: Country, city: City, category: Category} $market */
    private function user(array $market, array $permissions): User
    {
        $user = User::query()->create([
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'name' => 'COD user',
            'email' => Str::uuid().'@auction.test',
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
    private function cashOnDeliveryOrder(array $market, User $seller, User $buyer): Order
    {
        $product = Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'category_id' => $market['category']->id,
            'currency_id' => $market['currency']->id,
            'title' => 'COD listing',
            'description' => str_repeat('COD listing description ', 3),
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
            'payment_method' => 'cash_on_delivery',
            'status' => 'awaiting_cod_confirmation',
            'collection_status' => 'awaiting_confirmation',
            'winner_confirmation_expires_at' => now()->addHours(12),
        ]);
    }
}

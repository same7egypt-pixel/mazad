<?php

namespace Tests\Feature;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Data\GatewayCheckout;
use App\Domain\Payments\Data\VerifiedPaymentWebhook;
use App\Domain\Payments\Gateways\HmacJsonPaymentGateway;
use App\Domain\Payments\Services\InitiatePayment;
use App\Domain\Payments\Services\ProcessPaymentWebhook;
use App\Domain\Payments\Services\RequestWithdrawal;
use App\Domain\Payments\Services\ReviewWithdrawal;
use App\Models\Auction;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentSettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_webhook_credits_seller_once_even_when_replayed(): void
    {
        $market = $this->marketplace();
        $gateway = new TestPaymentGateway;
        app()->instance(TestPaymentGateway::class, $gateway);
        config()->set('marketplace.payment_gateways', ['TS' => TestPaymentGateway::class]);
        $seller = $this->user($market, []);
        $buyer = $this->user($market, []);
        $order = $this->order($market, $seller, $buyer);

        $payment = app(InitiatePayment::class)->handle($order->id, $buyer);
        self::assertSame('pending', $payment->status);
        self::assertSame('test-gateway', $payment->gateway);
        self::assertSame(1, $gateway->initiationCount);

        $webhook = new VerifiedPaymentWebhook($payment->id, 'txn-'.$payment->id, 'succeeded', '100.00', 'TST', ['event_id' => 'evt-1']);
        $first = app(ProcessPaymentWebhook::class)->handle('test-gateway', $webhook);
        $second = app(ProcessPaymentWebhook::class)->handle('test-gateway', $webhook);

        self::assertSame($first->id, $second->id);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'succeeded', 'transaction_id' => 'txn-'.$payment->id]);
        $this->assertDatabaseHas('wallets', ['user_id' => $seller->id, 'currency_id' => $market['currency']->id, 'pending_balance' => '90.00']);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_signed_hmac_webhook_endpoint_settles_the_matching_payment_once_when_replayed(): void
    {
        $market = $this->marketplace();
        config()->set('marketplace.payment_gateways', ['TS' => HmacJsonPaymentGateway::class]);
        config()->set('marketplace.hmac_webhook', [
            'gateway_name' => 'generic-hmac',
            'secret' => 'test-hmac-secret',
            'signature_header' => 'x-payment-signature',
        ]);
        $seller = $this->user($market, []);
        $buyer = $this->user($market, []);
        $order = $this->order($market, $seller, $buyer);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'country_id' => $market['country']->id,
            'gateway' => 'generic-hmac',
            'amount' => '100.00',
            'currency' => 'TST',
            'status' => 'pending',
        ]);
        $payload = json_encode([
            'payment_id' => $payment->id,
            'transaction_id' => 'hmac-txn-'.$payment->id,
            'status' => 'succeeded',
            'amount' => '100.00',
            'currency' => 'TST',
        ], JSON_THROW_ON_ERROR);
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_MARKETPLACE_COUNTRY' => (string) $market['country']->id,
            'HTTP_X_PAYMENT_SIGNATURE' => hash_hmac('sha256', $payload, 'test-hmac-secret'),
        ];

        foreach ([1, 2] as $attempt) {
            $this->call('POST', '/api/payment-webhooks/generic-hmac', [], [], [], $server, $payload)
                ->assertOk()
                ->assertJsonPath('payment_id', $payment->id)
                ->assertJsonPath('status', 'succeeded');
        }

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_withdrawal_hold_and_finance_rejection_restore_available_balance(): void
    {
        $market = $this->marketplace();
        $owner = $this->user($market, ['wallet.withdraw']);
        $reviewer = $this->user($market, ['payments.manage']);
        $wallet = Wallet::query()->create([
            'user_id' => $owner->id,
            'currency_id' => $market['currency']->id,
            'available_balance' => '50.00',
            'pending_balance' => '0.00',
        ]);

        $withdrawal = app(RequestWithdrawal::class)->handle($wallet->id, $owner, '20.00', 'bank_account', ['iban' => 'TEST-IBAN']);

        $this->assertDatabaseHas('wallets', ['id' => $wallet->id, 'available_balance' => '30.00', 'pending_balance' => '20.00']);
        $this->assertDatabaseHas('wallet_transactions', ['wallet_id' => $wallet->id, 'type' => 'withdrawal_hold', 'reference_id' => $withdrawal->id]);

        app(ReviewWithdrawal::class)->reject($withdrawal->id, $reviewer);

        $this->assertDatabaseHas('withdrawals', ['id' => $withdrawal->id, 'status' => 'rejected', 'reviewed_by' => $reviewer->id]);
        $this->assertDatabaseHas('wallets', ['id' => $wallet->id, 'available_balance' => '50.00', 'pending_balance' => '0.00']);
        $this->assertDatabaseHas('wallet_transactions', ['wallet_id' => $wallet->id, 'type' => 'withdrawal_reversal', 'reference_id' => $withdrawal->id]);
    }

    public function test_user_without_finance_permission_cannot_review_a_withdrawal(): void
    {
        $market = $this->marketplace();
        $owner = $this->user($market, ['wallet.withdraw']);
        $unprivilegedUser = $this->user($market, []);
        $wallet = Wallet::query()->create([
            'user_id' => $owner->id,
            'currency_id' => $market['currency']->id,
            'available_balance' => '20.00',
            'pending_balance' => '0.00',
        ]);
        $withdrawal = app(RequestWithdrawal::class)->handle($wallet->id, $owner, '10.00', 'bank_account', ['iban' => 'TEST-IBAN']);

        $this->expectException(ValidationException::class);
        app(ReviewWithdrawal::class)->approve($withdrawal->id, $unprivilegedUser);
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
            'name' => 'Payment test user',
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
    private function order(array $market, User $seller, User $buyer): Order
    {
        $product = Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'category_id' => $market['category']->id,
            'currency_id' => $market['currency']->id,
            'title' => 'Settlement test listing',
            'description' => str_repeat('Settlement description ', 3),
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
            'status' => 'waiting_payment',
        ]);
    }
}

class TestPaymentGateway implements PaymentGateway
{
    public int $initiationCount = 0;

    public function name(): string
    {
        return 'test-gateway';
    }

    public function initiate(Order $order, int $paymentId): GatewayCheckout
    {
        $this->initiationCount++;

        return new GatewayCheckout('txn-'.$paymentId, 'https://payments.test/checkout/'.$paymentId, CarbonImmutable::now()->addMinutes(15));
    }

    public function verifyWebhook(string $rawPayload, array $headers): VerifiedPaymentWebhook
    {
        throw new \LogicException('Webhook verification is covered by production gateway drivers.');
    }
}

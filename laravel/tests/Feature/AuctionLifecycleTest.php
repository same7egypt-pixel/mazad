<?php

namespace Tests\Feature;

use App\Domain\Auctions\Events\AuctionLifecycleUpdated;
use App\Domain\Auctions\Events\BidPlaced;
use App\Domain\Auctions\Services\CancelAuction;
use App\Domain\Auctions\Services\CloseAuction;
use App\Domain\Auctions\Services\PlaceBid;
use App\Domain\Auctions\Services\StartAuction;
use App\Models\Auction;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use App\Notifications\AuctionLifecycleNotification;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuctionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_schedule_an_approved_product_for_an_upcoming_auction(): void
    {
        $market = $this->marketplace();
        $seller = $this->user($market, ['auctions.create']);
        $product = $this->product($market, $seller, 'approved');

        $response = $this->actingAs($seller, 'sanctum')
            ->withHeader('X-Marketplace-Country', (string) $market['country']->id)
            ->postJson('/api/auctions', [
                'product_id' => $product->id,
                'starting_price' => '100.00',
                'reserve_price' => '125.00',
                'minimum_increment' => '5.00',
                'start_time' => now()->addMinutes(5)->toIso8601String(),
                'end_time' => now()->addHour()->toIso8601String(),
            ]);

        $response->assertCreated()->assertJsonPath('auction.status', 'upcoming');
        $this->assertDatabaseHas('auctions', [
            'product_id' => $product->id,
            'country_id' => $market['country']->id,
            'currency_id' => $market['currency']->id,
            'status' => 'upcoming',
        ]);
    }

    public function test_unapproved_products_cannot_be_scheduled_for_auction(): void
    {
        $market = $this->marketplace();
        $seller = $this->user($market, ['auctions.create']);
        $product = $this->product($market, $seller, 'draft');

        $response = $this->actingAs($seller, 'sanctum')
            ->withHeader('X-Marketplace-Country', (string) $market['country']->id)
            ->postJson('/api/auctions', [
                'product_id' => $product->id,
                'starting_price' => '100.00',
                'minimum_increment' => '5.00',
                'start_time' => now()->addMinutes(5)->toIso8601String(),
                'end_time' => now()->addHour()->toIso8601String(),
            ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('product_id');
        $this->assertDatabaseCount('auctions', 0);
    }

    public function test_due_auction_becomes_live_and_activates_the_product(): void
    {
        Notification::fake();
        Event::fake([AuctionLifecycleUpdated::class]);
        $market = $this->marketplace();
        $seller = $this->user($market, ['auctions.create']);
        $auction = $this->auction($market, $seller, 'upcoming', now()->subMinute(), now()->addHour());

        self::assertTrue(app(StartAuction::class)->handle($auction->id));

        $this->assertDatabaseHas('auctions', ['id' => $auction->id, 'status' => 'live']);
        $this->assertDatabaseHas('products', ['id' => $auction->product_id, 'status' => 'active']);
        Event::assertDispatched(AuctionLifecycleUpdated::class, fn (AuctionLifecycleUpdated $event) => $event->auction->id === $auction->id && $event->transition === 'started');
    }

    public function test_seller_can_cancel_only_an_unstarted_auction(): void
    {
        Notification::fake();
        Event::fake([AuctionLifecycleUpdated::class]);
        $market = $this->marketplace();
        $seller = $this->user($market, ['auctions.create']);
        $auction = $this->auction($market, $seller, 'upcoming', now()->addMinutes(5), now()->addHour());

        app(CancelAuction::class)->handle($auction->id);

        $this->assertDatabaseHas('auctions', ['id' => $auction->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('products', ['id' => $auction->product_id, 'status' => 'approved']);
        Notification::assertSentTo($seller, AuctionLifecycleNotification::class, fn (AuctionLifecycleNotification $notification) => $notification->event === 'cancelled');
        Event::assertDispatched(AuctionLifecycleUpdated::class, fn (AuctionLifecycleUpdated $event) => $event->auction->id === $auction->id && $event->transition === 'cancelled');
    }

    public function test_expired_winning_auction_creates_one_order_idempotently(): void
    {
        Notification::fake();
        Event::fake([AuctionLifecycleUpdated::class]);
        $market = $this->marketplace();
        $seller = $this->user($market, ['auctions.create']);
        $winner = $this->user($market, ['auctions.bid']);
        $auction = $this->auction($market, $seller, 'live', now()->subHour(), now()->subMinute(), '120.00', $winner->id);

        $firstOrder = app(CloseAuction::class)->handle($auction->id);
        $secondOrder = app(CloseAuction::class)->handle($auction->id);

        self::assertNotNull($firstOrder);
        self::assertSame($firstOrder->id, $secondOrder?->id);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('auctions', ['id' => $auction->id, 'status' => 'sold']);
        $this->assertDatabaseHas('products', ['id' => $auction->product_id, 'status' => 'sold']);
        Notification::assertSentTo([$seller, $winner], AuctionLifecycleNotification::class);
        Event::assertDispatched(AuctionLifecycleUpdated::class, fn (AuctionLifecycleUpdated $event) => $event->auction->id === $auction->id && $event->transition === 'sold');
    }

    public function test_atomic_bid_service_updates_the_price_once_and_rejects_an_underbid(): void
    {
        $market = $this->marketplace();
        $seller = $this->user($market, ['auctions.create']);
        $bidder = $this->user($market, ['auctions.bid']);
        $auction = $this->auction($market, $seller, 'live', now()->subMinute(), now()->addHour());

        $bid = app(PlaceBid::class)->handle($auction->id, $bidder, '110.00');

        self::assertSame('110.00', $bid->amount);
        $this->expectException(ValidationException::class);
        try {
            app(PlaceBid::class)->handle($auction->id, $bidder, '119.00');
        } finally {
            $auction->refresh();
            self::assertSame('110.00', $auction->current_price);
            self::assertSame(1, $auction->bid_count);
        }
    }

    public function test_bid_event_is_queued_for_broadcasting(): void
    {
        $market = $this->marketplace();
        $seller = $this->user($market, ['auctions.create']);
        $auction = $this->auction($market, $seller, 'live', now()->subMinute(), now()->addHour());
        $bid = $auction->bids()->create(['user_id' => $seller->id, 'amount' => '110.00']);

        self::assertInstanceOf(ShouldBroadcast::class, new BidPlaced($auction, $bid));
    }

    public function test_ended_without_sale_uses_the_queued_lifecycle_broadcast_path(): void
    {
        Notification::fake();
        Event::fake([AuctionLifecycleUpdated::class]);
        $market = $this->marketplace();
        $seller = $this->user($market, ['auctions.create']);
        $auction = $this->auction($market, $seller, 'live', now()->subHour(), now()->subMinute());

        self::assertNull(app(CloseAuction::class)->handle($auction->id));

        $this->assertDatabaseHas('auctions', ['id' => $auction->id, 'status' => 'ended']);
        Event::assertDispatched(AuctionLifecycleUpdated::class, fn (AuctionLifecycleUpdated $event) => $event->auction->id === $auction->id && $event->transition === 'ended_without_sale');
        self::assertInstanceOf(ShouldBroadcast::class, new AuctionLifecycleUpdated($auction, 'ended_without_sale'));
    }

    public function test_lifecycle_broadcast_waits_for_outer_commit_then_enqueues_a_broadcast_job(): void
    {
        Notification::fake();
        Queue::fake();
        config()->set('broadcasting.default', 'reverb');
        $market = $this->marketplace();
        $seller = $this->user($market, ['auctions.create']);
        $auction = $this->auction($market, $seller, 'upcoming', now()->subMinute(), now()->addHour());

        DB::beginTransaction();

        try {
            self::assertTrue(app(StartAuction::class)->handle($auction->id));
            Queue::assertNothingPushed();
            DB::commit();
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }

        Queue::assertPushed(BroadcastEvent::class, fn (BroadcastEvent $job) => $job->event instanceof AuctionLifecycleUpdated
            && $job->event->auction->id === $auction->id
            && $job->event->transition === 'started');
    }

    /** @return array{currency: Currency, country: Country, city: City, category: Category} */
    private function marketplace(): array
    {
        $suffix = strtolower(substr(str_replace('-', '', (string) Str::uuid()), 0, 8));
        $currency = Currency::query()->create(['name' => 'Test Dinar', 'code' => 'TST', 'symbol' => 'T', 'decimal_places' => 2, 'is_active' => true]);
        $country = Country::query()->create(['name' => "Testland {$suffix}", 'code' => strtoupper(substr($suffix, 0, 2)), 'timezone' => 'UTC', 'currency_id' => $currency->id, 'is_active' => true]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => "City {$suffix}", 'is_active' => true]);
        $category = Category::query()->create(['country_id' => $country->id, 'name' => "Category {$suffix}", 'slug' => "category-{$suffix}", 'is_active' => true]);

        return compact('currency', 'country', 'city', 'category');
    }

    /** @param array{currency: Currency, country: Country, city: City, category: Category} $market */
    private function user(array $market, array $permissions): User
    {
        $suffix = (string) Str::uuid();
        $user = User::query()->create([
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'name' => "User {$suffix}",
            'email' => "{$suffix}@auction.test",
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
    private function product(array $market, User $seller, string $status): Product
    {
        return Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $market['country']->id,
            'city_id' => $market['city']->id,
            'category_id' => $market['category']->id,
            'currency_id' => $market['currency']->id,
            'title' => 'Auction test listing',
            'description' => str_repeat('Test description ', 4),
            'condition' => 'good',
            'status' => $status,
            'approved_at' => $status === 'approved' ? now() : null,
        ]);
    }

    /** @param array{currency: Currency, country: Country, city: City, category: Category} $market */
    private function auction(array $market, User $seller, string $status, \DateTimeInterface $startTime, \DateTimeInterface $endTime, string $currentPrice = '100.00', ?int $winnerId = null): Auction
    {
        $product = $this->product($market, $seller, $status === 'upcoming' ? 'approved' : 'active');

        return Auction::query()->create([
            'product_id' => $product->id,
            'country_id' => $market['country']->id,
            'currency_id' => $market['currency']->id,
            'starting_price' => '100.00',
            'current_price' => $currentPrice,
            'reserve_price' => null,
            'minimum_increment' => '10.00',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $status,
            'winner_id' => $winnerId,
            'bid_count' => $winnerId === null ? 0 : 1,
        ]);
    }
}

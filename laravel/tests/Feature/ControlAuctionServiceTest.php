<?php

namespace Tests\Feature;

use App\Domain\Auctions\Services\ControlAuction;
use App\Models\Auction;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ControlAuctionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_admin_can_pause_a_live_auction_in_their_country_and_an_audit_log_is_created(): void
    {
        [$auction, $admin] = $this->auctionAndAdmin();

        app(ControlAuction::class)->pause($auction->id, $admin, 'مراجعة تشغيلية');

        $this->assertSame('paused', $auction->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'country_id' => $auction->country_id,
            'event' => 'auction.paused',
            'auditable_type' => Auction::class,
            'auditable_id' => $auction->id,
        ]);
    }

    public function test_country_admin_cannot_cancel_an_auction_in_another_country(): void
    {
        [$auction, $admin] = $this->auctionAndAdmin();
        $auction->update(['country_id' => Country::query()->create([
            'name' => 'Egypt',
            'code' => 'EG',
            'timezone' => 'Africa/Cairo',
            'currency_id' => $auction->currency_id,
        ])->id]);

        $this->expectException(ValidationException::class);

        app(ControlAuction::class)->cancel($auction->id, $admin, 'خارج نطاق الدولة');
    }

    public function test_extension_requires_a_later_end_time(): void
    {
        [$auction, $admin] = $this->auctionAndAdmin();

        $this->expectException(ValidationException::class);

        app(ControlAuction::class)->extend($auction->id, $admin, $auction->end_time->subMinute(), 'وقت غير صالح');
    }

    /** @return array{Auction, User} */
    private function auctionAndAdmin(): array
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create(['name' => 'Saudi Arabia', 'code' => 'SA', 'timezone' => 'Asia/Riyadh', 'currency_id' => $currency->id]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Riyadh']);
        $category = Category::query()->create(['country_id' => $country->id, 'name' => 'Watches', 'slug' => 'watches']);
        $seller = User::factory()->create(['country_id' => $country->id, 'city_id' => $city->id]);
        $product = Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'currency_id' => $currency->id,
            'title' => 'Live auction product',
            'description' => 'An approved product for operational auction control.',
            'condition' => 'good',
            'status' => 'approved',
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
        $admin = User::factory()->create(['country_id' => $country->id, 'status' => 'active']);
        $admin->assignRole(Role::findOrCreate('COUNTRY_ADMIN', 'web'));
        $admin->givePermissionTo(Permission::findOrCreate('auctions.manage', 'web'));

        return [$auction, $admin];
    }
}

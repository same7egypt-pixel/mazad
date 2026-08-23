<?php

namespace Tests\Feature;

use App\Filament\Widgets\AuctionStatusChart;
use App\Filament\Widgets\AuditActivityFeed;
use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ControlTowerWidgetsScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_admin_dashboard_widgets_only_include_own_country_operations(): void
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $saudiArabia = $this->createCountry('Saudi Arabia', 'SA', $currency);
        $egypt = $this->createCountry('Egypt', 'EG', $currency);
        $saudiAuction = $this->createLiveAuction($saudiArabia, $currency, 'Saudi watch');
        $egyptAuction = $this->createLiveAuction($egypt, $currency, 'Egypt art');

        $countryAdmin = User::factory()->create(['country_id' => $saudiArabia->id, 'status' => 'active']);
        $countryAdmin->assignRole(Role::findOrCreate('COUNTRY_ADMIN', 'web'));
        $countryAdmin->givePermissionTo([
            Permission::findOrCreate('auctions.manage', 'web'),
            Permission::findOrCreate('audit.view', 'web'),
        ]);

        AuditLog::query()->create([
            'actor_id' => $countryAdmin->id,
            'country_id' => $saudiArabia->id,
            'event' => 'auction.paused',
            'auditable_type' => Auction::class,
            'auditable_id' => $saudiAuction->id,
        ]);
        AuditLog::query()->create([
            'actor_id' => $countryAdmin->id,
            'country_id' => $egypt->id,
            'event' => 'auction.cancelled',
            'auditable_type' => Auction::class,
            'auditable_id' => $egyptAuction->id,
        ]);

        $this->actingAs($countryAdmin);

        $statusChart = new AuctionStatusChart;
        $chartData = $this->invokeProtected($statusChart, 'getData');
        $activityFeed = new AuditActivityFeed;
        $feedData = $this->invokeProtected($activityFeed, 'getViewData');

        $this->assertTrue(AuctionStatusChart::canView());
        $this->assertTrue(AuditActivityFeed::canView());
        $this->assertSame([1, 0, 0, 0, 0], $chartData['datasets'][0]['data']);
        $this->assertCount(1, $feedData['activities']);
        $this->assertSame('تم إيقاف مزاد مؤقتاً', $feedData['activities']->first()['event']);
        $this->assertSame('#'.$saudiAuction->id, $feedData['activities']->first()['subject']);
    }

    private function createCountry(string $name, string $code, Currency $currency): Country
    {
        return Country::query()->create([
            'name' => $name,
            'code' => $code,
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
    }

    private function createLiveAuction(Country $country, Currency $currency, string $title): Auction
    {
        $city = City::query()->create(['country_id' => $country->id, 'name' => $title.' City']);
        $category = Category::query()->create(['country_id' => $country->id, 'name' => $title.' Category', 'slug' => str($title)->slug()->toString()]);
        $seller = User::factory()->create(['country_id' => $country->id, 'city_id' => $city->id]);
        $product = Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'currency_id' => $currency->id,
            'title' => $title,
            'description' => 'Approved marketplace item',
            'condition' => 'excellent',
            'status' => 'approved',
        ]);

        return Auction::query()->create([
            'product_id' => $product->id,
            'country_id' => $country->id,
            'currency_id' => $currency->id,
            'starting_price' => 100,
            'current_price' => 100,
            'minimum_increment' => 10,
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
            'status' => 'live',
            'bid_count' => 0,
            'version' => 1,
        ]);
    }

    private function invokeProtected(object $widget, string $method): mixed
    {
        $reflection = new ReflectionMethod($widget, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($widget);
    }
}

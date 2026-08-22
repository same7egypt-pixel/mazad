<?php

namespace Tests\Feature;

use App\Filament\Resources\Cities\CityResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentCountryScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_admin_sees_only_cities_from_their_marketplace_country(): void
    {
        $currency = Currency::query()->create([
            'name' => 'Saudi Riyal',
            'code' => 'SAR',
            'symbol' => 'ر.س',
        ]);

        $saudiArabia = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $egypt = Country::query()->create([
            'name' => 'Egypt',
            'code' => 'EG',
            'timezone' => 'Africa/Cairo',
            'currency_id' => $currency->id,
        ]);
        $riyadh = City::query()->create(['country_id' => $saudiArabia->id, 'name' => 'Riyadh']);
        City::query()->create(['country_id' => $egypt->id, 'name' => 'Cairo']);

        $countryAdmin = User::factory()->create(['country_id' => $saudiArabia->id, 'status' => 'active']);
        $countryAdmin->assignRole(Role::findOrCreate('COUNTRY_ADMIN', 'web'));

        $this->actingAs($countryAdmin);

        $this->assertSame([$riyadh->id], CityResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_country_admin_sees_only_products_from_their_marketplace_country(): void
    {
        $currency = Currency::query()->create([
            'name' => 'Saudi Riyal',
            'code' => 'SAR',
            'symbol' => 'ر.س',
        ]);
        $saudiArabia = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $egypt = Country::query()->create([
            'name' => 'Egypt',
            'code' => 'EG',
            'timezone' => 'Africa/Cairo',
            'currency_id' => $currency->id,
        ]);
        $riyadh = City::query()->create(['country_id' => $saudiArabia->id, 'name' => 'Riyadh']);
        $cairo = City::query()->create(['country_id' => $egypt->id, 'name' => 'Cairo']);
        $saudiCategory = Category::query()->create(['country_id' => $saudiArabia->id, 'name' => 'Watches', 'slug' => 'watches']);
        $egyptCategory = Category::query()->create(['country_id' => $egypt->id, 'name' => 'Art', 'slug' => 'art']);
        $saudiSeller = User::factory()->create(['country_id' => $saudiArabia->id, 'city_id' => $riyadh->id]);
        $egyptSeller = User::factory()->create(['country_id' => $egypt->id, 'city_id' => $cairo->id]);

        $saudiProduct = Product::query()->create([
            'user_id' => $saudiSeller->id,
            'country_id' => $saudiArabia->id,
            'city_id' => $riyadh->id,
            'category_id' => $saudiCategory->id,
            'currency_id' => $currency->id,
            'title' => 'Saudi watch',
            'description' => 'Pending product in Saudi Arabia',
            'condition' => 'excellent',
            'status' => 'pending_review',
        ]);
        Product::query()->create([
            'user_id' => $egyptSeller->id,
            'country_id' => $egypt->id,
            'city_id' => $cairo->id,
            'category_id' => $egyptCategory->id,
            'currency_id' => $currency->id,
            'title' => 'Egypt art',
            'description' => 'Pending product in Egypt',
            'condition' => 'excellent',
            'status' => 'pending_review',
        ]);

        $countryAdmin = User::factory()->create(['country_id' => $saudiArabia->id, 'status' => 'active']);
        $countryAdmin->assignRole(Role::findOrCreate('COUNTRY_ADMIN', 'web'));

        $this->actingAs($countryAdmin);

        $this->assertSame([$saudiProduct->id], ProductResource::getEloquentQuery()->pluck('id')->all());
    }
}

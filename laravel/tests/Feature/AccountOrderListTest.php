<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountOrderListTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_only_orders_for_their_country_context(): void
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $user = User::factory()->create(['country_id' => $country->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders', ['X-Marketplace-Country' => (string) $country->id])
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_order_list_rejects_another_country_context(): void
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $otherCountry = Country::query()->create([
            'name' => 'Egypt',
            'code' => 'EG',
            'timezone' => 'Africa/Cairo',
            'currency_id' => $currency->id,
        ]);
        $user = User::factory()->create(['country_id' => $country->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders', ['X-Marketplace-Country' => (string) $otherCountry->id])
            ->assertForbidden();
    }
}

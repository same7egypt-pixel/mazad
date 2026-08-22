<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthCountryContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_endpoint_rejects_a_different_marketplace_country(): void
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
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
        $city = City::query()->create(['country_id' => $saudiArabia->id, 'name' => 'Riyadh']);
        $user = User::factory()->create(['country_id' => $saudiArabia->id, 'city_id' => $city->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user', ['X-Marketplace-Country' => (string) $saudiArabia->id])
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user', ['X-Marketplace-Country' => (string) $egypt->id])
            ->assertForbidden();
    }
}

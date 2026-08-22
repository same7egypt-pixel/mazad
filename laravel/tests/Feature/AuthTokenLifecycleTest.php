<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTokenLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_token_login_current_user_and_logout_lifecycle(): void
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Riyadh']);
        $user = User::factory()->create([
            'country_id' => $country->id,
            'city_id' => $city->id,
            'email' => 'seller@gmail.com',
            'status' => 'active',
        ]);
        $countryHeader = ['X-Marketplace-Country' => (string) $country->id];

        $login = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Marketplace browser test',
        ], $countryHeader)->assertOk()->assertJsonPath('user.id', $user->id);
        $token = $login->json('token');

        $this->assertIsString($token);
        $authenticatedHeaders = $countryHeader + ['Authorization' => "Bearer {$token}"];
        $this->getJson('/api/user', $authenticatedHeaders)
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->postJson('/api/auth/logout', [], $authenticatedHeaders)->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        app('auth')->forgetGuards();
        $this->getJson('/api/user', $authenticatedHeaders)->assertUnauthorized();
    }
}

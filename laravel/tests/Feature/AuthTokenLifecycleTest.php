<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
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

    public function test_marketplace_registration_issues_a_token_and_rejects_a_city_from_another_country(): void
    {
        Role::findOrCreate('USER', 'web');
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Riyadh']);
        $otherCountry = Country::query()->create([
            'name' => 'United Arab Emirates',
            'code' => 'AE',
            'timezone' => 'Asia/Dubai',
            'currency_id' => $currency->id,
        ]);
        $otherCity = City::query()->create(['country_id' => $otherCountry->id, 'name' => 'Dubai']);
        $headers = ['X-Marketplace-Country' => (string) $country->id];

        $this->postJson('/api/auth/register', [
            'country_id' => $country->id,
            'city_id' => $city->id,
            'name' => 'New Marketplace Seller',
            'email' => 'new-seller@gmail.com',
            'phone' => '+966500000000',
            'password' => 'very-secure-password',
            'password_confirmation' => 'very-secure-password',
            'device_name' => 'Marketplace browser test',
        ], $headers)->assertCreated()
            ->assertJsonPath('user.country_id', $country->id)
            ->assertJsonPath('user.city_id', $city->id)
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', ['email' => 'new-seller@gmail.com', 'country_id' => $country->id, 'city_id' => $city->id]);

        $this->postJson('/api/auth/register', [
            'country_id' => $country->id,
            'city_id' => $otherCity->id,
            'name' => 'Invalid City User',
            'email' => 'invalid-city@gmail.com',
            'password' => 'very-secure-password',
            'password_confirmation' => 'very-secure-password',
            'device_name' => 'Marketplace browser test',
        ], $headers)->assertUnprocessable()
            ->assertJsonValidationErrors('city_id');
    }
}

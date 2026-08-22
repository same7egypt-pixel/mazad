<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
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

    public function test_login_is_rate_limited_after_six_failed_attempts(): void
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $headers = ['X-Marketplace-Country' => (string) $country->id];
        $payload = [
            'email' => 'attacker@gmail.com',
            'password' => 'incorrect-password',
            'device_name' => 'Marketplace browser test',
        ];

        foreach (range(1, 6) as $attempt) {
            $this->postJson('/api/auth/login', $payload, $headers)->assertUnprocessable();
        }

        $this->postJson('/api/auth/login', $payload, $headers)->assertTooManyRequests();
    }

    public function test_registration_is_rate_limited_after_six_requests(): void
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
        $headers = ['X-Marketplace-Country' => (string) $country->id];

        $payload = [
            'country_id' => $country->id,
            'city_id' => $city->id,
            'name' => 'Rate Limited User',
            'email' => 'rate-user@gmail.com',
            'password' => 'very-secure-password',
            'password_confirmation' => 'very-secure-password',
            'device_name' => 'Marketplace browser test',
        ];

        $this->postJson('/api/auth/register', $payload, $headers)->assertCreated();

        foreach (range(2, 6) as $attempt) {
            $this->postJson('/api/auth/register', $payload, $headers)->assertUnprocessable();
        }

        $this->postJson('/api/auth/register', $payload, $headers)->assertTooManyRequests();
    }

    public function test_auth_rate_limit_isolated_by_marketplace_country_and_client_ip(): void
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $otherCountry = Country::query()->create([
            'name' => 'United Arab Emirates',
            'code' => 'AE',
            'timezone' => 'Asia/Dubai',
            'currency_id' => $currency->id,
        ]);
        $payload = [
            'email' => 'attacker@gmail.com',
            'password' => 'incorrect-password',
            'device_name' => 'Marketplace browser test',
        ];
        $countryHeader = ['X-Marketplace-Country' => (string) $country->id];

        foreach (range(1, 6) as $attempt) {
            $this->postJson('/api/auth/login', $payload, $countryHeader)->assertUnprocessable();
        }

        $this->postJson('/api/auth/login', $payload, $countryHeader)->assertTooManyRequests();
        $this->postJson('/api/auth/login', $payload, ['X-Marketplace-Country' => (string) $otherCountry->id])
            ->assertUnprocessable();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/auth/login', $payload, $countryHeader)
            ->assertUnprocessable();
    }

    public function test_expired_marketplace_token_is_rejected(): void
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Riyadh']);
        $user = User::factory()->create(['country_id' => $country->id, 'city_id' => $city->id]);
        $plainTextToken = $user->createToken('Marketplace browser test')->plainTextToken;
        $token = PersonalAccessToken::findToken($plainTextToken);

        config(['sanctum.expiration' => 1]);
        $token->forceFill(['created_at' => now()->subMinutes(2)])->save();
        app('auth')->forgetGuards();

        $this->getJson('/api/user', [
            'Authorization' => "Bearer {$plainTextToken}",
            'X-Marketplace-Country' => (string) $country->id,
        ])->assertUnauthorized();
    }
}

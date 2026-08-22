<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_verification_link_marks_the_account_as_verified(): void
    {
        $user = $this->unverifiedUser();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->get($url)->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'verification_status' => 'verified',
        ]);
        self::assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_signed_verification_link_with_a_mismatched_email_hash_is_rejected(): void
    {
        $user = $this->unverifiedUser();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
            'id' => $user->id,
            'hash' => sha1('another-email@example.test'),
        ]);

        $this->get($url)->assertForbidden();

        self::assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'verification_status' => 'pending']);
    }

    private function unverifiedUser(): User
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Riyadh']);

        return User::factory()->unverified()->create([
            'country_id' => $country->id,
            'city_id' => $city->id,
            'verification_status' => 'pending',
        ]);
    }
}

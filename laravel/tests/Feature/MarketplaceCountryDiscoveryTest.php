<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceCountryDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_country_discovery_lists_only_active_marketplaces_with_safe_currency_metadata(): void
    {
        $activeCurrency = Currency::query()->create([
            'name' => 'Alpha Dinar',
            'code' => 'ALP',
            'symbol' => 'A',
            'decimal_places' => 2,
            'is_active' => true,
        ]);
        $inactiveCurrency = Currency::query()->create([
            'name' => 'Blocked Dinar',
            'code' => 'BLK',
            'symbol' => 'B',
            'decimal_places' => 2,
            'is_active' => true,
        ]);
        $activeCountry = Country::query()->create([
            'name' => 'Alpha Market',
            'code' => 'AM',
            'timezone' => 'UTC',
            'currency_id' => $activeCurrency->id,
            'is_active' => true,
        ]);
        Country::query()->create([
            'name' => 'Blocked Market',
            'code' => 'BM',
            'timezone' => 'UTC',
            'currency_id' => $inactiveCurrency->id,
            'is_active' => false,
        ]);

        $this->getJson('/api/marketplaces/countries')
            ->assertOk()
            ->assertJsonCount(1, 'countries')
            ->assertJsonPath('countries.0.id', $activeCountry->id)
            ->assertJsonPath('countries.0.code', 'AM')
            ->assertJsonPath('countries.0.currency.code', 'ALP')
            ->assertJsonMissing(['code' => 'BM']);
    }
}

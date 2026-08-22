<?php

namespace Tests\Unit;

use App\Domain\Core\Context\MarketplaceContext;
use App\Models\Country;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MarketplaceContextTest extends TestCase
{
    public function test_it_rejects_access_without_a_country_context(): void
    {
        $context = new MarketplaceContext();

        $this->expectException(ValidationException::class);

        $context->id();
    }

    public function test_it_accepts_an_active_country_and_enforces_its_identifier(): void
    {
        $country = new Country(['is_active' => true]);
        $country->setAttribute('id', 15);
        $context = new MarketplaceContext();

        $context->setCountry($country);

        self::assertSame(15, $context->id());
        $context->assertMatches(15);
    }
}

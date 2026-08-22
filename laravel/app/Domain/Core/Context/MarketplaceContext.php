<?php

namespace App\Domain\Core\Context;

use App\Models\Country;
use Illuminate\Validation\ValidationException;

class MarketplaceContext
{
    private ?Country $country = null;

    public function setCountry(Country $country): void
    {
        if (! $country->is_active) {
            throw ValidationException::withMessages(['country' => 'The selected marketplace is inactive.']);
        }

        $this->country = $country;
    }

    public function country(): Country
    {
        if ($this->country === null) {
            throw ValidationException::withMessages(['country' => 'A marketplace country context is required.']);
        }

        return $this->country;
    }

    public function id(): int
    {
        return $this->country()->getKey();
    }

    public function assertMatches(int $countryId): void
    {
        if ($countryId !== $this->id()) {
            throw ValidationException::withMessages(['country' => 'The resource belongs to a different marketplace country.']);
        }
    }
}

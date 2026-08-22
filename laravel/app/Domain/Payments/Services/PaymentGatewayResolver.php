<?php

namespace App\Domain\Payments\Services;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\Country;
use Illuminate\Validation\ValidationException;

class PaymentGatewayResolver
{
    public function forCountry(Country $country): PaymentGateway
    {
        $driver = config("marketplace.payment_gateways.{$country->code}");

        if (! is_string($driver) || $driver === '') {
            throw ValidationException::withMessages(['gateway' => 'No payment gateway is configured for this marketplace country.']);
        }

        return $this->resolveDriver($driver);
    }

    public function forWebhook(string $gateway): PaymentGateway
    {
        foreach (config('marketplace.payment_gateways', []) as $driver) {
            if (! is_string($driver) || $driver === '') {
                continue;
            }

            $resolved = $this->resolveDriver($driver);
            if ($resolved->name() === $gateway) {
                return $resolved;
            }
        }

        throw ValidationException::withMessages(['gateway' => 'The payment gateway is not configured.']);
    }

    private function resolveDriver(string $driver): PaymentGateway
    {
        $gateway = app($driver);

        if (! $gateway instanceof PaymentGateway) {
            throw ValidationException::withMessages(['gateway' => 'The configured payment gateway driver is invalid.']);
        }

        return $gateway;
    }
}

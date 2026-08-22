<?php

namespace App\Domain\Payments\Data;

use Carbon\CarbonImmutable;

readonly class GatewayCheckout
{
    public function __construct(
        public string $externalTransactionId,
        public string $checkoutUrl,
        public ?CarbonImmutable $expiresAt = null,
    ) {}
}

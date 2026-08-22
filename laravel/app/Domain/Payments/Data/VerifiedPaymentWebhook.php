<?php

namespace App\Domain\Payments\Data;

readonly class VerifiedPaymentWebhook
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public int $paymentId,
        public string $externalTransactionId,
        public string $status,
        public string $amount,
        public string $currency,
        public array $payload,
    ) {}
}

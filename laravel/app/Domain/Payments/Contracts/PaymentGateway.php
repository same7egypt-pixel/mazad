<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Payments\Data\GatewayCheckout;
use App\Domain\Payments\Data\VerifiedPaymentWebhook;
use App\Models\Order;

interface PaymentGateway
{
    public function name(): string;

    public function initiate(Order $order, int $paymentId): GatewayCheckout;

    /** @param array<string, array<int, string>> $headers */
    public function verifyWebhook(string $rawPayload, array $headers): VerifiedPaymentWebhook;
}

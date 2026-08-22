<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Data\GatewayCheckout;
use App\Domain\Payments\Data\VerifiedPaymentWebhook;
use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HmacJsonPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return (string) config('marketplace.hmac_webhook.gateway_name', 'generic-hmac');
    }

    public function initiate(Order $order, int $paymentId): GatewayCheckout
    {
        throw ValidationException::withMessages([
            'gateway' => 'The generic HMAC gateway verifies signed webhooks only and cannot create a checkout without a provider adapter.',
        ]);
    }

    /** @param array<string, array<int, string>> $headers */
    public function verifyWebhook(string $rawPayload, array $headers): VerifiedPaymentWebhook
    {
        $secret = config('marketplace.hmac_webhook.secret');
        $signatureHeader = (string) config('marketplace.hmac_webhook.signature_header', 'x-payment-signature');

        if (! is_string($secret) || $secret === '') {
            throw ValidationException::withMessages(['signature' => 'No generic HMAC webhook secret is configured.']);
        }

        $providedSignature = $this->header($headers, $signatureHeader);
        $expectedSignature = hash_hmac('sha256', $rawPayload, $secret);

        if ($providedSignature === null || ! hash_equals($expectedSignature, preg_replace('/^sha256=/', '', $providedSignature) ?? '')) {
            throw ValidationException::withMessages(['signature' => 'The webhook signature is invalid.']);
        }

        try {
            $payload = json_decode($rawPayload, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['payload' => 'The signed webhook payload must be valid JSON.']);
        }

        if (! is_array($payload)
            || ! isset($payload['payment_id'], $payload['transaction_id'], $payload['status'], $payload['amount'], $payload['currency'])
            || ! is_numeric($payload['payment_id'])
            || ! is_string($payload['transaction_id'])
            || ! is_string($payload['status'])
            || ! is_string($payload['amount'])
            || ! is_string($payload['currency'])
            || $payload['transaction_id'] === ''
            || ! preg_match('/^\d{1,16}(\.\d{1,2})?$/', $payload['amount'])
            || ! preg_match('/^[A-Z]{3}$/', $payload['currency'])) {
            throw ValidationException::withMessages(['payload' => 'The signed webhook payload has invalid payment fields.']);
        }

        return new VerifiedPaymentWebhook(
            paymentId: (int) $payload['payment_id'],
            externalTransactionId: $payload['transaction_id'],
            status: $payload['status'],
            amount: $payload['amount'],
            currency: $payload['currency'],
            payload: $payload,
        );
    }

    /** @param array<string, array<int, string>> $headers */
    private function header(array $headers, string $expectedHeader): ?string
    {
        foreach ($headers as $name => $values) {
            if (Str::lower($name) === Str::lower($expectedHeader)) {
                return $values[0] ?? null;
            }
        }

        return null;
    }
}

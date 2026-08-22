<?php

namespace App\Domain\Payments\Services;

use App\Domain\Core\Money\Decimal;
use App\Domain\Payments\Data\VerifiedPaymentWebhook;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessPaymentWebhook
{
    public function handle(string $gateway, VerifiedPaymentWebhook $webhook): Payment
    {
        return DB::transaction(function () use ($gateway, $webhook): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($webhook->paymentId);
            $order = Order::query()->with('currency')->lockForUpdate()->findOrFail($payment->order_id);

            if ($payment->gateway !== $gateway || ($payment->transaction_id !== null && $payment->transaction_id !== $webhook->externalTransactionId)) {
                throw ValidationException::withMessages(['payment' => 'The webhook does not match the expected payment transaction.']);
            }
            if (Decimal::compare((string) $payment->amount, $webhook->amount) !== 0 || $payment->currency !== $webhook->currency || $order->currency->code !== $webhook->currency) {
                throw ValidationException::withMessages(['payment' => 'The webhook amount or currency does not match the order.']);
            }
            if ($payment->status === 'succeeded') {
                return $payment;
            }

            $status = in_array($webhook->status, ['succeeded', 'failed', 'cancelled'], true) ? $webhook->status : null;
            if ($status === null) {
                throw ValidationException::withMessages(['payment' => 'The webhook payment status is unsupported.']);
            }

            $payment->update([
                'transaction_id' => $webhook->externalTransactionId,
                'status' => $status,
                'payload' => $webhook->payload,
            ]);

            if ($status === 'succeeded') {
                if (! in_array($order->status, ['waiting_payment', 'paid'], true)) {
                    throw ValidationException::withMessages(['order' => 'This order cannot be marked as paid in its current state.']);
                }

                $order->update(['status' => 'paid', 'paid_at' => $order->paid_at ?? now()]);
                app(CreditSellerWallet::class)->handle($order->fresh());
            }

            return $payment->fresh();
        }, 3);
    }
}

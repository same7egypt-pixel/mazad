<?php

namespace App\Domain\Payments\Services;

use App\Domain\Core\Money\Decimal;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Validation\ValidationException;

class ReleaseSellerSettlement
{
    public function handle(Order $order): WalletTransaction
    {
        if ($order->payment_method !== 'cash_on_delivery' || $order->collection_status !== 'collected' || $order->receipt_confirmed_at === null) {
            throw ValidationException::withMessages(['order' => 'Seller settlement can only be released after cash collection and receipt confirmation.']);
        }

        $wallet = Wallet::query()->where('user_id', $order->seller_id)->where('currency_id', $order->currency_id)->lockForUpdate()->firstOrFail();
        $referenceType = Order::class;
        $existing = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('type', 'sale_earning_release')
            ->where('reference_type', $referenceType)
            ->where('reference_id', $order->id)
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $earning = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('type', 'sale_earning')
            ->where('reference_type', $referenceType)
            ->where('reference_id', $order->id)
            ->lockForUpdate()
            ->firstOrFail();

        $amount = (string) $order->seller_amount;
        $release = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'type' => 'sale_earning_release',
            'amount' => $amount,
            'reference_type' => $referenceType,
            'reference_id' => $order->id,
            'status' => 'completed',
            'metadata' => ['order_id' => $order->id, 'policy_event' => 'cash_on_delivery_receipt_confirmed'],
        ]);

        $earning->update(['status' => 'completed']);
        $wallet->update([
            'pending_balance' => Decimal::subtract((string) $wallet->pending_balance, $amount),
            'available_balance' => Decimal::add((string) $wallet->available_balance, $amount),
        ]);

        $order->update(['settlement_status' => 'released', 'settled_at' => now()]);

        return $release;
    }
}

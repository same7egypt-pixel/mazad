<?php

namespace App\Domain\Payments\Services;

use App\Domain\Core\Money\Decimal;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestWithdrawal
{
    /** @param array<string, mixed> $destinationDetails */
    public function handle(int $walletId, User $user, string $amount, string $destinationType, array $destinationDetails): Withdrawal
    {
        return DB::transaction(function () use ($walletId, $user, $amount, $destinationType, $destinationDetails): Withdrawal {
            $wallet = Wallet::query()->with('user')->lockForUpdate()->findOrFail($walletId);

            if ($wallet->user_id !== $user->id || $wallet->user->country_id !== $user->country_id) {
                throw ValidationException::withMessages(['wallet' => 'This wallet is not available for withdrawal.']);
            }
            if (! preg_match('/^\d{1,16}(\.\d{1,2})?$/', $amount) || Decimal::compare($amount, '0.00') <= 0) {
                throw ValidationException::withMessages(['amount' => 'The withdrawal amount must be a positive monetary value.']);
            }
            if (Decimal::compare((string) $wallet->available_balance, $amount) < 0) {
                throw ValidationException::withMessages(['amount' => 'The wallet does not have sufficient available balance.']);
            }

            $withdrawal = Withdrawal::query()->create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'status' => 'requested',
                'destination_type' => $destinationType,
                'destination_details' => $destinationDetails,
            ]);

            $wallet->update([
                'available_balance' => Decimal::subtract((string) $wallet->available_balance, $amount),
                'pending_balance' => Decimal::add((string) $wallet->pending_balance, $amount),
            ]);

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal_hold',
                'amount' => $amount,
                'reference_type' => Withdrawal::class,
                'reference_id' => $withdrawal->id,
                'status' => 'pending',
                'metadata' => ['destination_type' => $destinationType],
            ]);

            return $withdrawal;
        }, 3);
    }
}

<?php

namespace App\Domain\Payments\Services;

use App\Domain\Core\Money\Decimal;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewWithdrawal
{
    public function approve(int $withdrawalId, User $reviewer): Withdrawal
    {
        return $this->review($withdrawalId, $reviewer, 'approved');
    }

    public function reject(int $withdrawalId, User $reviewer): Withdrawal
    {
        return $this->review($withdrawalId, $reviewer, 'rejected');
    }

    private function review(int $withdrawalId, User $reviewer, string $decision): Withdrawal
    {
        return DB::transaction(function () use ($withdrawalId, $reviewer, $decision): Withdrawal {
            $withdrawal = Withdrawal::query()->with('wallet.user')->lockForUpdate()->findOrFail($withdrawalId);
            if ($withdrawal->status !== 'requested') {
                throw ValidationException::withMessages(['withdrawal' => 'Only requested withdrawals may be reviewed.']);
            }
            if (! $reviewer->can('payments.manage') || (! $reviewer->hasRole('GLOBAL_SUPER_ADMIN') && $reviewer->country_id !== $withdrawal->wallet->user->country_id)) {
                throw ValidationException::withMessages(['withdrawal' => 'You are not permitted to review this withdrawal.']);
            }

            $withdrawal->update([
                'status' => $decision,
                'reviewed_by' => $reviewer->id,
                'processed_at' => $decision === 'approved' ? now() : null,
            ]);

            if ($decision === 'rejected') {
                $wallet = $withdrawal->wallet;
                $wallet->update([
                    'available_balance' => Decimal::add((string) $wallet->available_balance, (string) $withdrawal->amount),
                    'pending_balance' => Decimal::subtract((string) $wallet->pending_balance, (string) $withdrawal->amount),
                ]);
                WalletTransaction::query()->create([
                    'wallet_id' => $wallet->id,
                    'type' => 'withdrawal_reversal',
                    'amount' => $withdrawal->amount,
                    'reference_type' => Withdrawal::class,
                    'reference_id' => $withdrawal->id,
                    'status' => 'completed',
                    'metadata' => ['reason' => 'finance_rejected'],
                ]);
            }

            return $withdrawal->fresh(['wallet']);
        }, 3);
    }
}

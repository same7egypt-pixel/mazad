<?php

namespace App\Domain\Auctions\Services;

use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ControlAuction
{
    public function pause(int $auctionId, User $actor, string $reason): Auction
    {
        return $this->control($auctionId, $actor, 'pause', $reason);
    }

    public function cancel(int $auctionId, User $actor, string $reason): Auction
    {
        return $this->control($auctionId, $actor, 'cancel', $reason);
    }

    public function extend(int $auctionId, User $actor, Carbon|string $endTime, string $reason): Auction
    {
        return DB::transaction(function () use ($auctionId, $actor, $endTime, $reason): Auction {
            $auction = $this->authorizedAuction($auctionId, $actor);

            if ($auction->status !== 'live') {
                throw ValidationException::withMessages(['auction' => 'Only live auctions may be extended.']);
            }

            $nextEndTime = Carbon::parse($endTime);

            if ($nextEndTime->lessThanOrEqualTo($auction->end_time)) {
                throw ValidationException::withMessages(['end_time' => 'The end time must be later than the current end time.']);
            }

            $before = ['end_time' => $auction->end_time?->toIso8601String()];
            $auction->update(['end_time' => $nextEndTime]);
            $this->audit($auction, $actor, 'auction.extended', $before, ['end_time' => $nextEndTime->toIso8601String(), 'reason' => $reason]);

            return $auction->fresh();
        }, 3);
    }

    private function control(int $auctionId, User $actor, string $action, string $reason): Auction
    {
        return DB::transaction(function () use ($auctionId, $actor, $action, $reason): Auction {
            $auction = $this->authorizedAuction($auctionId, $actor);
            $nextStatus = $action === 'pause' ? 'paused' : 'cancelled';
            $allowed = $action === 'pause' ? ['upcoming', 'live'] : ['upcoming', 'live', 'paused'];

            if (! in_array($auction->status, $allowed, true)) {
                throw ValidationException::withMessages(['auction' => 'This auction cannot be controlled in its current status.']);
            }

            $before = ['status' => $auction->status];
            $auction->update(['status' => $nextStatus]);
            $this->audit($auction, $actor, 'auction.'.$nextStatus, $before, ['status' => $nextStatus, 'reason' => $reason]);

            return $auction->fresh();
        }, 3);
    }

    private function authorizedAuction(int $auctionId, User $actor): Auction
    {
        $auction = Auction::query()->lockForUpdate()->findOrFail($auctionId);

        if (! $actor->can('auctions.manage') || (! $actor->hasRole('GLOBAL_SUPER_ADMIN') && $actor->country_id !== $auction->country_id)) {
            throw ValidationException::withMessages(['auction' => 'You are not permitted to control this auction.']);
        }

        return $auction;
    }

    private function audit(Auction $auction, User $actor, string $event, array $before, array $after): void
    {
        AuditLog::query()->create([
            'actor_id' => $actor->id,
            'country_id' => $auction->country_id,
            'event' => $event,
            'auditable_type' => Auction::class,
            'auditable_id' => $auction->id,
            'before' => $before,
            'after' => $after,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

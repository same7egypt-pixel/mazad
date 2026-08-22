<?php

namespace App\Console\Commands;

use App\Domain\Auctions\Services\CloseAuction;
use App\Models\Auction;
use Illuminate\Console\Command;

class CloseExpiredAuctions extends Command
{
    protected $signature = 'auctions:close-expired {--limit=100}';
    protected $description = 'Close expired live auctions and create their winning orders';
    public function handle(CloseAuction $closer): int
    {
        Auction::query()->whereIn('status', ['live', 'upcoming'])->where('end_time', '<=', now())->orderBy('id')->limit((int) $this->option('limit'))->pluck('id')->each(fn (int $id) => $closer->handle($id));
        return self::SUCCESS;
    }
}

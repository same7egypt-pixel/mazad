<?php

namespace App\Console\Commands;

use App\Domain\Auctions\Services\StartAuction;
use App\Models\Auction;
use Illuminate\Console\Command;

class StartDueAuctions extends Command
{
    protected $signature = 'auctions:start-due {--limit=100}';

    protected $description = 'Start upcoming auctions whose scheduled start time has arrived.';

    public function handle(StartAuction $service): int
    {
        $ids = Auction::query()->where('status', 'upcoming')->where('start_time', '<=', now())
            ->orderBy('start_time')->limit((int) $this->option('limit'))->pluck('id');

        foreach ($ids as $id) {
            $service->handle($id);
        }

        $this->info("Processed {$ids->count()} due auctions.");

        return self::SUCCESS;
    }
}

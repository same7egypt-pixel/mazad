<?php

namespace Tests\Feature;

use App\Domain\Auctions\Services\PlaceBid;
use App\Models\Auction;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BidConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_two_process_bid_contention_accepts_one_identical_bid_and_rejects_the_other_against_the_updated_minimum(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('PCNTL is required for the multi-process bid contention test.');
        }

        [$auction, $firstBidder, $secondBidder] = $this->liveAuctionWithTwoEligibleBidders();
        $resultDirectory = storage_path('framework/testing/bid-contention-'.bin2hex(random_bytes(6)));
        mkdir($resultDirectory, 0700, true);
        $startAt = microtime(true) + 0.5;
        $processes = [
            [$firstBidder->id, "{$resultDirectory}/first.json"],
            [$secondBidder->id, "{$resultDirectory}/second.json"],
        ];
        $pids = [];

        foreach ($processes as [$bidderId, $resultFile]) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('Unable to fork a process for the bid contention test.');
            }

            if ($pid === 0) {
                $this->runConcurrentBidAttempt($auction->id, $bidderId, $startAt, $resultFile);
                exit(0);
            }

            $pids[] = $pid;
        }

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            self::assertTrue(pcntl_wifexited($status));
            self::assertSame(0, pcntl_wexitstatus($status));
        }

        $results = array_map(
            static fn (array $process): array => json_decode((string) file_get_contents($process[1]), true, flags: JSON_THROW_ON_ERROR),
            $processes,
        );
        array_map('unlink', array_column($processes, 1));
        rmdir($resultDirectory);

        self::assertSame(1, count(array_filter($results, static fn (array $result): bool => $result['status'] === 'accepted')));
        self::assertSame(1, count(array_filter($results, static fn (array $result): bool => $result['status'] === 'rejected')));

        DB::purge();
        DB::reconnect();
        $auction->refresh();
        self::assertSame('110.00', $auction->current_price);
        self::assertSame(1, $auction->bid_count);
        $this->assertDatabaseCount('bids', 1);
    }

    private function runConcurrentBidAttempt(int $auctionId, int $bidderId, float $startAt, string $resultFile): void
    {
        DB::purge();
        DB::reconnect();

        while (microtime(true) < $startAt) {
            usleep(1_000);
        }

        try {
            app(PlaceBid::class)->handle($auctionId, User::query()->findOrFail($bidderId), '110.00');
            file_put_contents($resultFile, json_encode(['status' => 'accepted'], JSON_THROW_ON_ERROR));
        } catch (\Illuminate\Validation\ValidationException) {
            file_put_contents($resultFile, json_encode(['status' => 'rejected'], JSON_THROW_ON_ERROR));
        } catch (\Throwable $exception) {
            file_put_contents($resultFile, json_encode(['status' => 'failed', 'class' => $exception::class], JSON_THROW_ON_ERROR));
        }
    }

    /** @return array{Auction, User, User} */
    private function liveAuctionWithTwoEligibleBidders(): array
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Riyadh']);
        $category = Category::query()->create(['country_id' => $country->id, 'name' => 'Watches', 'slug' => 'watches']);
        $seller = User::factory()->create(['country_id' => $country->id, 'city_id' => $city->id, 'status' => 'active', 'verification_status' => 'verified']);
        $firstBidder = User::factory()->create(['country_id' => $country->id, 'city_id' => $city->id, 'status' => 'active', 'verification_status' => 'verified']);
        $secondBidder = User::factory()->create(['country_id' => $country->id, 'city_id' => $city->id, 'status' => 'active', 'verification_status' => 'verified']);
        $permission = Permission::findOrCreate('auctions.bid', 'web');
        $firstBidder->givePermissionTo($permission);
        $secondBidder->givePermissionTo($permission);
        $product = Product::query()->create([
            'user_id' => $seller->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'currency_id' => $currency->id,
            'title' => 'Contention test watch',
            'description' => 'A listing used to verify concurrent PostgreSQL bid locking behavior.',
            'condition' => 'good',
            'status' => 'active',
        ]);
        $auction = Auction::query()->create([
            'product_id' => $product->id,
            'country_id' => $country->id,
            'currency_id' => $currency->id,
            'starting_price' => '100.00',
            'current_price' => '100.00',
            'minimum_increment' => '10.00',
            'start_time' => now()->subMinute(),
            'end_time' => now()->addHour(),
            'status' => 'live',
        ]);

        return [$auction, $firstBidder, $secondBidder];
    }
}

<?php

namespace Tests\Feature\QueueLine;

use App\Livewire\QueueLine\Board;
use App\Models\Orders\QueueLineFuelVerification;
use App\Services\QueueLine\QueueFuelVerificationService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * Phase 4 §8 — the board's query count must be FLAT with respect to card
 * volume: one eligibility query with eager loads, one batched fuel-state
 * query, fixed chrome queries (stores, suppressed drawer). Any per-card
 * query added to the Blade path (helper call, lazy relation) fails this
 * suite long before it fails on a wall TV.
 */
class QueueLineQueryBudgetTest extends QueueLineTestCase
{
    /** Seed $count eligible cards across both stores, half fuel-verified. */
    private function seedCards(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $row = $this->makeRow(null, [
                'delivery_store_id' => $i % 2 === 0 ? $this->storeNorth->id : $this->storeSouth->id,
                'delivery_date' => now()->addDays($i % 3 === 0 ? 1 : 0)->format('Y-m-d'), // today + tomorrow mix
            ]);

            if ($i % 4 !== 3) { // leave every 4th unassigned (attention row)
                $unit = $this->softAssign($row);

                if ($i % 2 === 0) {
                    QueueFuelVerificationService::verify(
                        orderProduct: $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']),
                        expected: $unit,
                        performedBy: $this->admin,
                        actor: $this->admin,
                        source: QueueLineFuelVerification::SOURCE_WEB,
                    );
                }
            }
        }
    }

    private function measure(): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $start = hrtime(true);

        Livewire::test(Board::class);

        $elapsedMs = (hrtime(true) - $start) / 1e6;
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$queries, $elapsedMs];
    }

    public function test_query_count_is_flat_from_empty_to_high_volume(): void
    {
        [$emptyQueries, $emptyMs] = $this->measure();

        $this->seedCards(8);
        [$smallQueries, $smallMs] = $this->measure();

        $this->seedCards(52); // total 60 cards on the board
        [$highQueries, $highMs] = $this->measure();

        fwrite(STDERR, sprintf(
            "\n[queue-line perf] empty: %d queries / %.0fms | 8 cards: %d queries / %.0fms | 60 cards: %d queries / %.0fms\n",
            $emptyQueries, $emptyMs, $smallQueries, $smallMs, $highQueries, $highMs,
        ));

        // The whole design contract: card volume must not add queries.
        $this->assertSame(
            $smallQueries,
            $highQueries,
            'query count grew with card volume — a per-card (N+1) query crept into the render path',
        );

        // Empty board may skip constant queries (fuel batch short-circuit);
        // it must never exceed the populated count.
        $this->assertLessThanOrEqual($smallQueries, $emptyQueries);
    }

    public function test_store_filter_keeps_the_same_flat_budget(): void
    {
        $this->seedCards(24);

        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(Board::class)->set('store', (string) $this->storeNorth->id);
        $filtered = count(DB::getQueryLog());
        DB::disableQueryLog();

        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(Board::class);
        $all = count(DB::getQueryLog());
        DB::disableQueryLog();

        // set() renders twice (mount + update) — allow exactly that shape,
        // nothing volume-proportional.
        $this->assertLessThanOrEqual($all * 2 + 2, $filtered);

        fwrite(STDERR, sprintf("\n[queue-line perf] all-stores render: %d queries | mount+filter: %d queries\n", $all, $filtered));
    }

    public function test_wallboard_mode_adds_no_queries(): void
    {
        $this->seedCards(12);

        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(Board::class, ['wallboard' => true]);
        $wall = count(DB::getQueryLog());
        DB::disableQueryLog();

        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(Board::class);
        $standard = count(DB::getQueryLog());
        DB::disableQueryLog();

        // The summary strip is computed from the loaded collection — zero
        // additional queries against the standard board.
        $this->assertLessThanOrEqual($standard, $wall);

        fwrite(STDERR, sprintf("\n[queue-line perf] standard: %d queries | wallboard(+summary): %d queries\n", $standard, $wall));
    }
}

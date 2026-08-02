<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\HistoricalTaxBasisFailure;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Services\Orders\HistoricalTaxBasisResolver;
use App\Services\Orders\SpecialTaxColumnBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The special-tax / added-fees backfill.
 *
 * The danger this suite exists to prevent is a backfill that writes a
 * confident `0.00` into a row whose snapshot it could not actually read. That
 * would be worse than leaving the column empty: a zero looks like an answer,
 * and it would let an order that has never reconciled start passing the
 * resolver's exact reconciliation by coincidence.
 *
 * Every order is therefore checked against the residual it must explain:
 *
 *     residual = grand_total - (subtotal + tax_amount - discount_amount)
 *
 * which IS special tax plus added fees, by construction of
 * `CartHelper::buildCart()`. `unreconciled_order_is_not_written` and
 * `missing_snapshot_with_an_unexplained_residual_is_not_written` are the
 * load-bearing tests — they prove the backfill declines rather than guesses.
 */
class SpecialTaxColumnBackfillTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        HistoricalTaxBasisResolver::flushSchemaMemo();

        $this->customer = Customer::factory()->create();

        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id'    => 'PRD-BACKFILL-1',
            'product_name' => 'Backfill Tester',
            'slug'         => 'backfill-tester',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    // ── Written categories ─────────────────────────────────────────────────

    public function test_complete_snapshot_that_reconciles_is_reconstructed(): void
    {
        // 200.00 basis @ 9.75% = 19.50 ; special 4.00 ; fees 6.00
        $order = $this->makeOrder(200.00, 19.50, 229.50);
        $this->addLine($order, 200.00, 19.50, ['special_tax' => 4.00, 'added_fees' => 6.00]);

        $audit = SpecialTaxColumnBackfill::apply();

        $this->assertSame(1, $audit['counts'][SpecialTaxColumnBackfill::RECONSTRUCTED]);

        $order->refresh();
        $this->assertSame('4.00', (string) $order->special_tax_amount);
        $this->assertSame('6.00', (string) $order->added_fees_amount);

        $line = $order->products()->firstOrFail();
        $this->assertSame('4.00', (string) $line->special_tax);
        $this->assertSame('6.00', (string) $line->added_fees);
    }

    public function test_order_owing_nothing_is_written_as_explicit_zero(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 19.50, []);

        $audit = SpecialTaxColumnBackfill::apply();

        $this->assertSame(1, $audit['counts'][SpecialTaxColumnBackfill::NO_COMPONENTS]);

        $order->refresh();
        $this->assertSame('0.00', (string) $order->special_tax_amount);
        $this->assertSame('0.00', (string) $order->added_fees_amount);
    }

    public function test_lineless_order_with_no_residual_is_written_at_order_level_only(): void
    {
        $order = $this->makeOrder(150.00, 14.63, 164.63);

        $audit = SpecialTaxColumnBackfill::apply();

        // The ZERO RESIDUAL is what licenses the zero — not the absence of lines.
        $this->assertSame(1, $audit['counts'][SpecialTaxColumnBackfill::LINELESS]);

        $order->refresh();
        $this->assertSame('0.00', (string) $order->special_tax_amount);
        $this->assertSame('0.00', (string) $order->added_fees_amount);
    }

    public function test_lineless_order_with_a_nonzero_residual_is_not_written(): void
    {
        // "No lines" does not independently prove "no special tax or fees" — it
        // only means there is no line to attribute a component to. This order
        // asserts 5.00 that nothing in its structure explains. Known extension
        // children happen to be constrained to a zero residual today, but that
        // is a property of how Extension\StoreController builds them, not a law
        // of the schema, so the backfill must not assume it.
        $order = $this->makeOrder(150.00, 14.63, 169.63);

        $audit = SpecialTaxColumnBackfill::apply();

        $this->assertSame(1, $audit['counts'][SpecialTaxColumnBackfill::LINELESS_UNEXPLAINED]);
        $this->assertSame(0, $audit['counts'][SpecialTaxColumnBackfill::LINELESS], 'Must not be grouped with safely-zero line-less orders.');
        $this->assertSame(0, $audit['counts'][SpecialTaxColumnBackfill::MISSING_JSON_UNEXPLAINED], 'There is no snapshot here to be missing — this is its own shape.');

        $order->refresh();
        $this->assertSame('0.00', (string) $order->special_tax_amount, 'Left at the default — not asserted as a fact.');

        $exception = $audit['exceptions'][SpecialTaxColumnBackfill::LINELESS_UNEXPLAINED][0];
        $this->assertSame($order->id, $exception['order_id']);
        $this->assertSame(500, $exception['residual_cents']);
    }

    public function test_the_two_lineless_shapes_are_reported_separately(): void
    {
        $safe       = $this->makeOrder(100.00, 9.75, 109.75);  // residual 0
        $unexplained = $this->makeOrder(100.00, 9.75, 113.75); // residual 4.00

        $audit = SpecialTaxColumnBackfill::analyze();

        $this->assertSame(1, $audit['counts'][SpecialTaxColumnBackfill::LINELESS]);
        $this->assertSame(1, $audit['counts'][SpecialTaxColumnBackfill::LINELESS_UNEXPLAINED]);

        $writableIds = array_column($audit['writable'], 'order_id');
        $this->assertContains($safe->id, $writableIds);
        $this->assertNotContains($unexplained->id, $writableIds, 'A nonzero residual must never be written as zero.');
    }

    public function test_multiple_lines_aggregate_to_the_order_from_the_backfilled_line_columns(): void
    {
        // 100 + 100 basis, 19.50 tax ; specials 2.00 + 3.00 ; fees 1.00 + 4.00
        $order = $this->makeOrder(200.00, 19.50, 229.50);
        $this->addLine($order, 100.00, 9.75, ['special_tax' => 2.00, 'added_fees' => 1.00]);
        $this->addLine($order, 100.00, 9.75, ['special_tax' => 3.00, 'added_fees' => 4.00]);

        SpecialTaxColumnBackfill::apply();

        $order->refresh();
        $lines = $order->products()->orderBy('id')->get();

        $this->assertSame('5.00', (string) $order->special_tax_amount);
        $this->assertSame('5.00', (string) $order->added_fees_amount);
        $this->assertEqualsWithDelta(5.00, $lines->sum(fn ($l) => (float) $l->special_tax), 0.0001);
        $this->assertEqualsWithDelta(5.00, $lines->sum(fn ($l) => (float) $l->added_fees), 0.0001);
    }

    // ── Declined categories — the load-bearing cases ───────────────────────

    public function test_missing_snapshot_with_an_unexplained_residual_is_not_written(): void
    {
        // grand_total owes 4.00 more than subtotal + tax explains, and the
        // snapshot that would prove why is gone.
        $order = $this->makeOrder(200.00, 19.50, 223.50);
        $this->addLine($order, 200.00, 19.50, null);

        $audit = SpecialTaxColumnBackfill::apply();

        $this->assertSame(1, $audit['counts'][SpecialTaxColumnBackfill::MISSING_JSON_UNEXPLAINED]);
        $this->assertSame(0, $audit['counts'][SpecialTaxColumnBackfill::NO_COMPONENTS]);

        $order->refresh();
        $this->assertSame('0.00', (string) $order->special_tax_amount, 'Left at the default — not asserted as a fact.');

        $exception = $audit['exceptions'][SpecialTaxColumnBackfill::MISSING_JSON_UNEXPLAINED][0];
        $this->assertSame($order->id, $exception['order_id']);
        $this->assertSame(400, $exception['residual_cents'], 'The audit must state the size of what it could not explain.');
    }

    public function test_missing_snapshot_owing_nothing_is_safely_zeroed(): void
    {
        // The snapshot is unreadable, but the order's own arithmetic proves
        // there was never a component to lose.
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 19.50, null);

        $audit = SpecialTaxColumnBackfill::apply();

        $this->assertSame(1, $audit['counts'][SpecialTaxColumnBackfill::NO_COMPONENTS]);
        $this->assertSame(0, $audit['counts'][SpecialTaxColumnBackfill::MISSING_JSON_UNEXPLAINED]);
    }

    public function test_non_object_snapshot_json_is_treated_as_unreadable(): void
    {
        // `product_data` is a MySQL JSON column, so syntactically broken text
        // cannot be stored at all — the engine rejects it on insert. The
        // reachable "malformed" shapes are therefore NULL (covered above) and
        // VALID JSON that simply is not an object, which is what this asserts.
        $order = $this->makeOrder(200.00, 19.50, 223.50);
        $this->addLineRaw($order, 200.00, 19.50, '123');

        $audit = SpecialTaxColumnBackfill::apply();

        $this->assertSame(1, $audit['counts'][SpecialTaxColumnBackfill::MISSING_JSON_UNEXPLAINED]);
        $this->assertSame('0.00', (string) $order->fresh()->special_tax_amount);
    }

    public function test_unreconciled_order_is_not_written(): void
    {
        // The snapshot claims 4.00 of special tax; the grand total says 9.00
        // is unexplained. One of the two is wrong and the backfill cannot know
        // which, so it writes neither.
        $order = $this->makeOrder(200.00, 19.50, 228.50);
        $this->addLine($order, 200.00, 19.50, ['special_tax' => 4.00]);

        $audit = SpecialTaxColumnBackfill::apply();

        $this->assertSame(1, $audit['counts'][SpecialTaxColumnBackfill::UNRECONCILED]);

        $order->refresh();
        $this->assertSame('0.00', (string) $order->special_tax_amount);
        $this->assertSame('0.00', (string) $order->products()->firstOrFail()->special_tax);

        $exception = $audit['exceptions'][SpecialTaxColumnBackfill::UNRECONCILED][0];
        $this->assertSame(900, $exception['residual_cents']);
        $this->assertSame(400, $exception['components_cents']);
    }

    public function test_a_declined_order_still_fails_the_resolver_afterwards(): void
    {
        // The whole safety argument: a default-zero column must not let a
        // never-reconcilable order start passing.
        $order = $this->makeOrder(200.00, 19.50, 228.50);
        $this->addLine($order, 200.00, 19.50, ['special_tax' => 4.00]);

        $before = HistoricalTaxBasisResolver::resolve($order->fresh());
        $this->assertFalse($before->succeeded());

        SpecialTaxColumnBackfill::apply();

        $after = HistoricalTaxBasisResolver::resolve($order->fresh());
        $this->assertFalse($after->succeeded(), 'The backfill must not make an unreconcilable order look sound.');
        $this->assertSame(HistoricalTaxBasisFailure::UnreconciledGrandTotal, $after->failure);
    }

    // ── Guarantees ─────────────────────────────────────────────────────────

    public function test_backfill_never_writes_product_data(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 229.50);
        $this->addLine($order, 200.00, 19.50, ['special_tax' => 4.00, 'added_fees' => 6.00]);

        $before = DB::table('order_products')->where('order_id', $order->id)->pluck('product_data')->all();

        SpecialTaxColumnBackfill::apply();

        $this->assertSame(
            $before,
            DB::table('order_products')->where('order_id', $order->id)->pluck('product_data')->all(),
            'product_data is the immutable original snapshot and must survive byte-identical.'
        );
    }

    public function test_analyze_is_read_only(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 229.50);
        $this->addLine($order, 200.00, 19.50, ['special_tax' => 4.00, 'added_fees' => 6.00]);

        $beforeOrders = DB::table('orders')->get()->toArray();
        $beforeLines  = DB::table('order_products')->get()->toArray();

        SpecialTaxColumnBackfill::analyze();

        $this->assertEquals($beforeOrders, DB::table('orders')->get()->toArray());
        $this->assertEquals($beforeLines, DB::table('order_products')->get()->toArray());
    }

    public function test_backfill_is_idempotent(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 229.50);
        $this->addLine($order, 200.00, 19.50, ['special_tax' => 4.00, 'added_fees' => 6.00]);

        SpecialTaxColumnBackfill::apply();
        $first = DB::table('orders')->where('id', $order->id)->first();

        $second = SpecialTaxColumnBackfill::apply();

        $this->assertEquals($first, DB::table('orders')->where('id', $order->id)->first());
        $this->assertSame(1, $second['counts'][SpecialTaxColumnBackfill::RECONSTRUCTED]);
    }

    public function test_audit_counts_every_order_and_line_examined(): void
    {
        $a = $this->makeOrder(200.00, 19.50, 229.50);
        $this->addLine($a, 200.00, 19.50, ['special_tax' => 4.00, 'added_fees' => 6.00]);

        $b = $this->makeOrder(100.00, 9.75, 109.75);
        $this->addLine($b, 50.00, 4.88, []);
        $this->addLine($b, 50.00, 4.87, []);

        $this->makeOrder(50.00, 0.00, 50.00); // line-less

        $audit = SpecialTaxColumnBackfill::analyze();

        $this->assertSame(3, $audit['orders_examined']);
        $this->assertSame(3, $audit['lines_examined']);
        $this->assertSame(3, array_sum($audit['counts']), 'Every order must land in exactly one category.');
    }

    // ── Fixtures ───────────────────────────────────────────────────────────

    private function makeOrder(float $subtotal, float $tax, float $grandTotal, float $discount = 0.0): Order
    {
        return Order::create([
            'order_date'      => now()->format('Y-m-d'),
            'customer_id'     => $this->customer->id,
            'customer_name'   => 'Backfill Customer',
            'subtotal'        => $subtotal,
            'tax_amount'      => $tax,
            'discount_amount' => $discount,
            'grand_total'     => $grandTotal,
        ]);
    }

    /** @param array<string,mixed>|null $frozen null writes NULL product_data (the legacy case). */
    private function addLine(Order $order, float $subTotal, float $tax, ?array $frozen): void
    {
        $this->addLineRaw($order, $subTotal, $tax, $frozen === null ? null : json_encode($frozen));
    }

    private function addLineRaw(Order $order, float $subTotal, float $tax, ?string $raw): void
    {
        static $n = 0;
        $n++;

        DB::table('order_products')->insert([
            'unique_id'    => 'ORD-SCH-BF-'.$n,
            'order_id'     => $order->id,
            'product_id'   => $this->productId,
            'product_name' => 'Backfill Tester',
            'price'        => $subTotal,
            'quantity'     => 1,
            'sub_total'    => $subTotal,
            'tax'          => $tax,
            'total'        => $subTotal + $tax,
            'product_data' => $raw,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}

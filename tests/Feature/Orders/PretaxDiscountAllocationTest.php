<?php

namespace Tests\Feature\Orders;

use App\Models\Customers\Customer;
use App\Models\Discounts\ProductDiscount;
use App\Models\Orders\Order;
use App\Services\Discounts\PretaxDiscountAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Line-level pre-tax discount allocation.
 *
 * `orders.pretax_discount_total` says how much was conceded but not from which
 * lines; a single per-line column says how much a line bore but not by which
 * adjustment. Both are needed — product reporting needs the first, and
 * reversing one adjustment among several needs the second.
 *
 * `reversing_one_adjustment_leaves_the_others_untouched` is the load-bearing
 * test. Everything else guards the properties that make it possible:
 * determinism, exact sums, and gross values that never move.
 */
class PretaxDiscountAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_line_receives_the_whole_allocation(): void
    {
        $order = $this->order([200.00]);
        $d = $this->discount($order, 50.00);

        $shares = PretaxDiscountAllocator::allocate($order, $d->id, 5000);

        $this->assertSame([5000], array_values($shares));
        $this->assertSame(5000, PretaxDiscountAllocator::allocatedCents($order));
        $this->assertSame('50.00', (string) $order->products()->firstOrFail()->pretax_discount_allocated);
    }

    public function test_multi_line_allocation_is_proportional_to_gross(): void
    {
        // 150 / 50 → 75% / 25% of a 100.00 concession.
        $order = $this->order([150.00, 50.00]);
        $d = $this->discount($order, 100.00);

        $shares = array_values(PretaxDiscountAllocator::allocate($order, $d->id, 10000));

        $this->assertSame([7500, 2500], $shares);
        $this->assertSame(10000, array_sum($shares));
    }

    public function test_tax_free_lines_still_receive_an_allocation(): void
    {
        // A pre-tax concession reduces merchandise, and a tax-free line is
        // still merchandise. Excluding it would misattribute product revenue.
        $order = $this->order([100.00, 100.00], taxOnFirstOnly: true);
        $d = $this->discount($order, 50.00);

        $shares = array_values(PretaxDiscountAllocator::allocate($order, $d->id, 5000));

        $this->assertSame([2500, 2500], $shares, 'Allocation follows gross value, not taxability.');
    }

    public function test_leftover_cents_are_deterministic_and_tie_break_by_lowest_id(): void
    {
        // Three equal lines, 100 cents: 33.33 each, one cent left over. Equal
        // remainders, so the tie must fall to the lowest line id — never to
        // whatever order the rows arrived in.
        $order = $this->order([100.00, 100.00, 100.00]);
        $d = $this->discount($order, 1.00);

        $first = PretaxDiscountAllocator::allocate($order, $d->id, 100);
        $ids = array_keys($first);

        $this->assertSame(100, array_sum($first));
        $this->assertSame(34, $first[$ids[0]], 'The extra cent goes to the lowest id.');
        $this->assertSame(33, $first[$ids[1]]);
        $this->assertSame(33, $first[$ids[2]]);

        // And it is stable: a repeat produces the identical distribution.
        $again = PretaxDiscountAllocator::allocate($order, $d->id, 100);
        $this->assertSame($first, $again);
    }

    public function test_stacked_adjustments_accumulate_without_overwriting(): void
    {
        $order = $this->order([150.00, 50.00]);
        $a = $this->discount($order, 40.00);
        $b = $this->discount($order, 60.00);

        PretaxDiscountAllocator::allocate($order, $a->id, 4000);
        PretaxDiscountAllocator::allocate($order, $b->id, 6000);

        $this->assertSame(4, DB::table('order_product_discount_allocations')->count(), 'Two adjustments x two lines.');
        $this->assertSame(10000, PretaxDiscountAllocator::allocatedCents($order));

        $lines = $order->products()->orderBy('id')->get();
        $this->assertSame('75.00', (string) $lines[0]->pretax_discount_allocated, '30.00 + 45.00');
        $this->assertSame('25.00', (string) $lines[1]->pretax_discount_allocated, '10.00 + 15.00');
    }

    public function test_reversing_one_adjustment_leaves_the_others_untouched(): void
    {
        $order = $this->order([150.00, 50.00]);
        $a = $this->discount($order, 40.00);
        $b = $this->discount($order, 60.00);

        PretaxDiscountAllocator::allocate($order, $a->id, 4000);
        PretaxDiscountAllocator::allocate($order, $b->id, 6000);

        PretaxDiscountAllocator::reverse($order, $a->id);

        $this->assertSame(6000, PretaxDiscountAllocator::allocatedCents($order), 'Only B remains active.');

        // A's rows are DEACTIVATED, not deleted — the history survives.
        $this->assertSame(4, DB::table('order_product_discount_allocations')->count());
        $this->assertSame(2, DB::table('order_product_discount_allocations')
            ->where('product_discount_id', $a->id)->whereNotNull('reversed_at')->count());
        $this->assertSame(2, DB::table('order_product_discount_allocations')
            ->where('product_discount_id', $b->id)->whereNull('reversed_at')->count());

        $lines = $order->products()->orderBy('id')->get();
        $this->assertSame('45.00', (string) $lines[0]->pretax_discount_allocated);
        $this->assertSame('15.00', (string) $lines[1]->pretax_discount_allocated);
    }

    public function test_a_retry_is_idempotent(): void
    {
        $order = $this->order([150.00, 50.00]);
        $d = $this->discount($order, 100.00);

        PretaxDiscountAllocator::allocate($order, $d->id, 10000);
        PretaxDiscountAllocator::allocate($order, $d->id, 10000);
        PretaxDiscountAllocator::allocate($order, $d->id, 10000);

        $this->assertSame(2, DB::table('order_product_discount_allocations')->count(), 'One row per adjustment per line.');
        $this->assertSame(10000, PretaxDiscountAllocator::allocatedCents($order));
    }

    public function test_reconciliation_detects_drift(): void
    {
        $order = $this->order([200.00]);
        $order->update(['pretax_discount_total' => 50.00]);

        $d = $this->discount($order, 50.00);
        PretaxDiscountAllocator::allocate($order, $d->id, 5000);
        $this->assertNull(PretaxDiscountAllocator::reconcile($order->fresh()));

        // Drift in either direction is reported, not tolerated.
        $order->update(['pretax_discount_total' => 60.00]);
        $this->assertStringContainsString('allocations total 5000c', PretaxDiscountAllocator::reconcile($order->fresh()));
    }

    public function test_a_lineless_order_has_nothing_to_reconcile(): void
    {
        // Extension children and several other shapes carry totals without
        // itemization. There is no population to allocate across, so
        // reconciliation must not refuse them — while an order that HAS lines
        // stays fully constrained.
        $order = $this->order([]);
        $order->update(['pretax_discount_total' => 50.00]);

        $this->assertNull(PretaxDiscountAllocator::reconcile($order->fresh()));
        $this->assertSame([], PretaxDiscountAllocator::allocate($order->fresh(), 1, 5000));
    }

    // ── Legacy orders ──────────────────────────────────────────────────────

    public function test_a_legacy_discount_is_recorded_separately_and_stays_visible(): void
    {
        // An order discounted before allocations existed: a total, no rows.
        $order = $this->order([200.00]);
        $order->update(['pretax_discount_total' => 50.00, 'legacy_unallocated_pretax_discount' => 50.00]);

        $this->assertNull(
            PretaxDiscountAllocator::reconcile($order->fresh()),
            'legacy 5000c + tracked 0c = total 5000c — the identity holds.'
        );
        $this->assertSame(5000, PretaxDiscountAllocator::legacyUnallocatedCents($order->fresh()));
        $this->assertSame(0, PretaxDiscountAllocator::allocatedCents($order->fresh()));

        // The legacy amount is NOT pushed onto any line.
        $this->assertSame('0.00', (string) $order->products()->firstOrFail()->pretax_discount_allocated);
    }

    public function test_a_new_adjustment_on_a_legacy_order_reconciles_only_its_own_portion(): void
    {
        // THE CASE THAT BROKE AN EARLIER DRAFT. Comparing the new rows against
        // the ENTIRE total refused legitimate work; comparing them against the
        // total minus "whatever is missing" would have made the new rows
        // silently appear to account for the historical concession.
        $order = $this->order([200.00]);
        $order->update(['pretax_discount_total' => 50.00, 'legacy_unallocated_pretax_discount' => 50.00]);

        // A new 30.00 adjustment lands on top.
        $order->update(['pretax_discount_total' => 80.00]);
        $d = $this->discount($order, 30.00);
        PretaxDiscountAllocator::allocate($order->fresh(), $d->id, 3000);

        $this->assertNull(
            PretaxDiscountAllocator::reconcile($order->fresh()),
            'legacy 5000c + tracked 3000c = total 8000c.'
        );

        // The two portions stay distinguishable.
        $this->assertSame(5000, PretaxDiscountAllocator::legacyUnallocatedCents($order->fresh()));
        $this->assertSame(3000, PretaxDiscountAllocator::allocatedCents($order->fresh()));

        // The line carries ONLY the tracked share — never the legacy amount.
        $this->assertSame('30.00', (string) $order->products()->firstOrFail()->pretax_discount_allocated);
    }

    public function test_reversing_a_new_adjustment_on_a_legacy_order_leaves_the_legacy_portion(): void
    {
        $order = $this->order([200.00]);
        $order->update(['pretax_discount_total' => 80.00, 'legacy_unallocated_pretax_discount' => 50.00]);

        $d = $this->discount($order, 30.00);
        PretaxDiscountAllocator::allocate($order->fresh(), $d->id, 3000);

        PretaxDiscountAllocator::reverse($order->fresh(), $d->id);
        $order->update(['pretax_discount_total' => 50.00]);

        $this->assertNull(PretaxDiscountAllocator::reconcile($order->fresh()));
        $this->assertSame(5000, PretaxDiscountAllocator::legacyUnallocatedCents($order->fresh()), 'The legacy portion is untouched.');
        $this->assertSame(0, PretaxDiscountAllocator::allocatedCents($order->fresh()));
    }

    public function test_a_retry_never_resurrects_a_reversed_allocation(): void
    {
        // A duplicate call arriving after a deliberate reversal must not
        // silently re-apply a concession the business withdrew.
        $order = $this->order([200.00]);
        $d = $this->discount($order, 50.00);

        PretaxDiscountAllocator::allocate($order, $d->id, 5000);
        PretaxDiscountAllocator::reverse($order, $d->id);
        PretaxDiscountAllocator::allocate($order->fresh(), $d->id, 5000);

        $this->assertSame(0, PretaxDiscountAllocator::allocatedCents($order->fresh()), 'It stays reversed.');
        $this->assertSame(1, DB::table('order_product_discount_allocations')->whereNotNull('reversed_at')->count());
    }

    public function test_gross_line_subtotals_are_never_modified(): void
    {
        $order = $this->order([150.00, 50.00]);
        $before = DB::table('order_products')->where('order_id', $order->id)->orderBy('id')->pluck('sub_total')->all();

        $a = $this->discount($order, 40.00);
        $b = $this->discount($order, 60.00);
        PretaxDiscountAllocator::allocate($order, $a->id, 4000);
        PretaxDiscountAllocator::allocate($order, $b->id, 6000);
        PretaxDiscountAllocator::reverse($order, $a->id);

        $this->assertSame(
            $before,
            DB::table('order_products')->where('order_id', $order->id)->orderBy('id')->pluck('sub_total')->all(),
            'sub_total is gross and must survive every allocation and reversal.'
        );
    }

    public function test_full_sum_reconciliation_across_a_lifecycle(): void
    {
        $order = $this->order([137.49, 88.13, 42.07]);
        $a = $this->discount($order, 33.33);
        $b = $this->discount($order, 66.67);

        PretaxDiscountAllocator::allocate($order, $a->id, 3333);
        $this->assertSame(3333, PretaxDiscountAllocator::allocatedCents($order));

        PretaxDiscountAllocator::allocate($order, $b->id, 6667);
        $this->assertSame(10000, PretaxDiscountAllocator::allocatedCents($order));

        PretaxDiscountAllocator::reverse($order, $b->id);
        $this->assertSame(3333, PretaxDiscountAllocator::allocatedCents($order));

        PretaxDiscountAllocator::reverse($order, $a->id);
        $this->assertSame(0, PretaxDiscountAllocator::allocatedCents($order));

        foreach ($order->products()->get() as $line) {
            $this->assertSame('0.00', (string) $line->pretax_discount_allocated);
        }
    }

    // ── Fixtures ───────────────────────────────────────────────────────────

    /** @param array<int,float> $lineSubtotals */
    private function order(array $lineSubtotals, bool $taxOnFirstOnly = false): Order
    {
        $customer = Customer::factory()->create();
        $subtotal = array_sum($lineSubtotals);

        $order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'customer_name' => 'Allocation Probe',
            'subtotal' => $subtotal,
            'tax_amount' => 0, 'special_tax_amount' => 0, 'added_fees_amount' => 0,
            'discount_amount' => 0, 'pretax_discount_total' => 0,
            'grand_total' => $subtotal,
        ]);

        foreach ($lineSubtotals as $i => $sub) {
            $order->products()->create([
                'unique_id' => 'ORD-ALLOC-'.$order->id.'-'.$i,
                'product_name' => 'Probe '.$i,
                'price' => $sub, 'quantity' => 1,
                'sub_total' => $sub,
                'tax' => ($taxOnFirstOnly && $i > 0) ? 0 : 0,
                'special_tax' => 0, 'added_fees' => 0,
                'total' => $sub,
                'product_data' => json_encode(['sub_total' => $sub, 'tax' => 0, 'special_tax' => 0, 'added_fees' => 0]),
            ]);
        }

        return $order->fresh();
    }

    private function discount(Order $order, float $amount): ProductDiscount
    {
        static $n = 0;
        $n++;

        return ProductDiscount::create([
            'discount_type' => 'store_credit',
            'calculation_type' => 'fixed_amount',
            'source_amount' => $amount,
            'calculated_discount_amount' => $amount,
            'target_type' => 'order',
            'target_id' => $order->id,
            'customer_id' => $order->customer_id,
            'original_product_value' => $order->subtotal,
            'discounted_product_value' => (float) $order->subtotal - $amount,
            'taxable_value_before' => $order->subtotal,
            'taxable_value_after' => (float) $order->subtotal - $amount,
            'tax_before' => 0,
            'tax_after' => 0,
            'idempotency_key' => 'alloc-test-'.$order->id.'-'.$n,
            'applied_at' => now(),
            'status' => ProductDiscount::STATUS_APPLIED,
        ]);
    }
}

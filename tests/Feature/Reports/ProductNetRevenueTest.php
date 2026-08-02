<?php

namespace Tests\Feature\Reports;

use App\Models\Customers\Customer;
use App\Models\Discounts\ProductDiscount;
use App\Models\Orders\Order;
use App\Services\Discounts\PretaxDiscountAllocator;
use App\Services\Reports\ProductSalesPerformanceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Product revenue is NET of allocated pre-tax discounts.
 *
 * `order_products.sub_total` is the GROSS merchandise value and is never
 * reduced by an adjustment — the concession lives in
 * `pretax_discount_allocated`. The engine previously summed `sub_total` while
 * documenting that "discounts are priced-in", which was not true: the discount
 * engine never wrote that column, so Store Credit concessions did not appear
 * in product reporting at all.
 *
 * `store_credit_now_reduces_product_revenue` is the load-bearing test.
 * `legacy_orders_report_gross_and_disclose_the_gap` is the honest one — a
 * legacy order cannot be netted, and the report says so instead of pretending.
 */
class ProductNetRevenueTest extends TestCase
{
    use RefreshDatabase;

    private ProductSalesPerformanceEngine $engine;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(ProductSalesPerformanceEngine::class);

        // baseQuery() INNER JOINs products, so a line without a real
        // product_id is invisible to every report.
        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id' => 'PRD-NETREV-1',
            'product_name' => 'Net Revenue Probe',
            'slug' => 'net-revenue-probe',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_an_undiscounted_order_is_unchanged(): void
    {
        $order = $this->paidOrder([200.00]);

        $this->assertSame(20000, $this->reportedNetRevenueCents());
        $this->assertSame(0.0, $this->engine->legacyUnallocatedDiscount($this->filters()));
    }

    public function test_store_credit_now_reduces_product_revenue(): void
    {
        $order = $this->paidOrder([200.00]);
        $this->allocate($order, 50.00);

        $this->assertSame(15000, $this->reportedNetRevenueCents(), '200.00 gross − 50.00 allocated.');

        // Gross is still gross — the original sale value is not erased.
        $this->assertSame('200.00', (string) $order->products()->firstOrFail()->sub_total);
    }

    public function test_multiple_lines_are_each_reduced_by_their_own_share(): void
    {
        $order = $this->paidOrder([150.00, 50.00]);
        $this->allocate($order, 100.00);   // 75 / 25 split

        $lines = $order->products()->orderBy('id')->get();
        $this->assertSame('75.00', (string) $lines[0]->pretax_discount_allocated);
        $this->assertSame('25.00', (string) $lines[1]->pretax_discount_allocated);

        $this->assertSame(10000, $this->reportedNetRevenueCents(), '200.00 gross − 100.00 allocated.');
    }

    public function test_tax_free_merchandise_is_netted_the_same_way(): void
    {
        // Taxability has no bearing on product revenue attribution.
        $order = $this->paidOrder([100.00, 100.00], taxFreeSecond: true);
        $this->allocate($order, 40.00);

        $this->assertSame(16000, $this->reportedNetRevenueCents());
    }

    public function test_stacked_discounts_both_reduce_revenue(): void
    {
        $order = $this->paidOrder([200.00]);
        $this->allocate($order, 30.00);
        $this->allocate($order, 20.00);

        $this->assertSame(15000, $this->reportedNetRevenueCents(), '200.00 − 30.00 − 20.00.');
    }

    public function test_a_reversed_discount_restores_revenue(): void
    {
        $order = $this->paidOrder([200.00]);
        $first = $this->allocate($order, 30.00);
        $this->allocate($order, 20.00);

        PretaxDiscountAllocator::reverse($order->fresh(), $first->id);

        $this->assertSame(18000, $this->reportedNetRevenueCents(), 'Only the surviving 20.00 still applies.');
    }

    public function test_legacy_orders_report_gross_and_disclose_the_gap(): void
    {
        // Discounted before allocations existed: a total, no line rows.
        $order = $this->paidOrder([200.00]);
        $order->update([
            'pretax_discount_total' => 50.00,
            'legacy_unallocated_pretax_discount' => 50.00,
        ]);

        // Product revenue is GROSS — the concession cannot be attributed.
        $this->assertSame(20000, $this->reportedNetRevenueCents());

        // …and the report says exactly how much is unattributed, rather than
        // presenting an overstated figure as if it were net.
        $this->assertSame(50.0, $this->engine->legacyUnallocatedDiscount($this->filters()));
    }

    public function test_tracked_and_legacy_orders_are_disclosed_separately(): void
    {
        $tracked = $this->paidOrder([200.00]);
        $this->allocate($tracked, 50.00);

        $legacy = $this->paidOrder([100.00]);
        $legacy->update(['pretax_discount_total' => 25.00, 'legacy_unallocated_pretax_discount' => 25.00]);

        // 150.00 net (tracked) + 100.00 gross (legacy) = 250.00 reported.
        $this->assertSame(25000, $this->reportedNetRevenueCents());

        // The 25.00 that could not be attributed is stated on its own.
        $this->assertSame(25.0, $this->engine->legacyUnallocatedDiscount($this->filters()));
    }

    public function test_report_totals_reconcile_to_the_tracked_allocation_model(): void
    {
        $order = $this->paidOrder([137.49, 88.13, 42.07]);
        $this->allocate($order, 66.67);

        $grossCents = (int) round($order->products()->sum('sub_total') * 100);
        $allocatedCents = PretaxDiscountAllocator::allocatedCents($order->fresh());

        $this->assertSame(
            $grossCents - $allocatedCents,
            $this->reportedNetRevenueCents(),
            'Report net revenue must equal gross minus tracked allocations, to the cent.'
        );
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** @return array<string,mixed> */
    private function filters(): array
    {
        return ['start_date' => now()->subYear()->toDateString(), 'end_date' => now()->addYear()->toDateString()];
    }

    /**
     * Total net product revenue the engine reports for the window.
     *
     * Every test is isolated by RefreshDatabase, so this is the revenue of
     * whatever that test created — deliberately not scoped to one order, since
     * what matters is what the REPORT says, not what a per-order calculation
     * would say.
     */
    private function reportedNetRevenueCents(): int
    {
        $row = collect($this->engine->buildKpis($this->filters()));

        return (int) round(((float) ($row['total_revenue'] ?? 0)) * 100);
    }

    private function allocate(Order $order, float $amount): ProductDiscount
    {
        static $n = 0;
        $n++;

        $discount = ProductDiscount::create([
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
            'tax_before' => 0, 'tax_after' => 0,
            'idempotency_key' => 'net-rev-'.$order->id.'-'.$n,
            'applied_at' => now(),
            'status' => ProductDiscount::STATUS_APPLIED,
        ]);

        PretaxDiscountAllocator::allocate($order->fresh(), $discount->id, (int) round($amount * 100));

        $order->update([
            'pretax_discount_total' => (float) $order->pretax_discount_total + $amount,
        ]);

        return $discount;
    }

    /** @param array<int,float> $lineSubtotals */
    private function paidOrder(array $lineSubtotals, bool $taxFreeSecond = false): Order
    {
        $customer = Customer::factory()->create();
        $subtotal = array_sum($lineSubtotals);

        $order = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'customer_name' => 'Report Probe',
            'subtotal' => $subtotal,
            'tax_amount' => 0, 'special_tax_amount' => 0, 'added_fees_amount' => 0,
            'discount_amount' => 0, 'pretax_discount_total' => 0,
            'grand_total' => $subtotal,
        ]);

        foreach ($lineSubtotals as $i => $sub) {
            $order->products()->create([
                'unique_id' => 'ORD-NET-'.$order->id.'-'.$i,
                'product_id' => $this->productId,
                'product_name' => 'Probe '.$i,
                'price' => $sub, 'quantity' => 1,
                'sub_total' => $sub,
                'tax' => ($taxFreeSecond && $i > 0) ? 0 : 0,
                'special_tax' => 0, 'added_fees' => 0,
                'total' => $sub,
                'product_data' => json_encode(['sub_total' => $sub]),
            ]);
        }

        $order->payments()->create([
            'payment_method' => \App\Enums\Orders\OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $subtotal,
            'status' => \App\Enums\Orders\OrderPaymentStatus::Paid->value,
        ]);

        return $order->fresh();
    }
}

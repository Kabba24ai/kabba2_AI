<?php

namespace Tests\Feature\Orders;

use App\Http\DataObjects\GoodwillCalculationFailure;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Services\Orders\GoodwillAdjustmentService;
use App\Services\Orders\HistoricalTaxBasisResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Goodwill Adjustment calculation (FD-002, Truth Table type 17).
 *
 * The worked example from FD-002 is the anchor: a $219.50 order ($200.00 +
 * 9.75%) settled by accepting $185.00 must reduce the basis to $168.56, tax
 * to $16.44, and waive $31.44 — every figure derived through
 * TaxCalculationService, never a formula written here.
 */
class GoodwillCalculationTest extends TestCase
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
            'unique_id' => 'PRD-GW', 'product_name' => 'Goodwill Test',
            'slug' => 'goodwill-test', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeOrder(float $subtotal, float $tax, float $grandTotal, float $discount = 0.0): Order
    {
        return Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Goodwill Customer',
            'subtotal' => $subtotal, 'tax_amount' => $tax,
            'discount_amount' => $discount, 'grand_total' => $grandTotal,
        ]);
    }

    private function addLine(Order $order, float $subTotal, float $tax, array $frozen = []): void
    {
        static $n = 0;
        $n++;

        $order->products()->create([
            'unique_id' => 'ORD-GW-'.$n.'-'.$order->id,
            'product_id' => $this->productId,
            'product_name' => 'Goodwill Test',
            'price' => $subTotal, 'quantity' => 1,
            'sub_total' => $subTotal, 'tax' => $tax, 'total' => $subTotal + $tax,
            // Current columns and frozen snapshot both written, exactly as
            // checkout does. The resolver reads the columns; the snapshot is
            // the original-state record.
            'special_tax' => $frozen['special_tax'] ?? 0,
            'added_fees' => $frozen['added_fees'] ?? 0,
            'product_data' => json_encode($frozen + ['special_tax' => 0, 'added_fees' => 0]),
        ]);
    }

    // ── The FD-002 worked example ──────────────────────────────────────────

    public function test_worked_example_reduces_basis_and_recomputes_tax(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 19.50);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 18500);

        $this->assertTrue($calc->succeeded(), 'Expected the worked example to calculate.');
        $this->assertSame(16856, $calc->revisedBasisCents);
        $this->assertSame(1644, $calc->revisedTaxCents);
        $this->assertSame(18500, $calc->revisedGrandTotalCents);
        $this->assertSame(3144, $calc->goodwillCents);
        $this->assertSame(21950, $calc->originalGrandTotalCents);
    }

    public function test_identity_holds_exactly_in_integer_cents(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 19.50);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 18500);

        $this->assertSame(
            $calc->acceptedCents,
            $calc->revisedBasisCents + $calc->revisedUntaxedMerchandiseCents
                + $calc->revisedTaxCents + $calc->revisedSpecialTaxCents
                + $calc->protectedCents - $calc->discountCents,
        );
    }

    // ── Tax shapes ─────────────────────────────────────────────────────────

    /**
     * Tax-exempt merchandise is REDUCIBLE (FD-002 Amendment 3).
     *
     * The absence of tax says nothing about whether a line is part of the
     * sale. A fully exempt $200.00 order settled at $185.00 waives $15.00,
     * with no tax anywhere — the blended effective rate is zero, so the
     * revised merchandise simply equals the accepted amount.
     */
    public function test_fully_tax_exempt_merchandise_order_is_reducible(): void
    {
        $order = $this->makeOrder(200.00, 0.00, 200.00);
        $this->addLine($order, 200.00, 0.00);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 18500);

        $this->assertTrue($calc->succeeded(), 'Tax-exempt merchandise must be reducible.');
        $this->assertSame(1500, $calc->goodwillCents);
        $this->assertSame(18500, $calc->revisedUntaxedMerchandiseCents);
        $this->assertSame(0, $calc->revisedBasisCents);
        $this->assertSame(0, $calc->revisedTaxCents);
        $this->assertSame(0, $calc->revisedSpecialTaxCents);
        $this->assertSame(18500, $calc->revisedGrandTotalCents);
    }

    public function test_tax_free_product_on_an_otherwise_taxable_order_shares_the_reduction(): void
    {
        // $100 taxable @9.75% ($9.75) + $100 tax-free merchandise = $209.75.
        $order = $this->makeOrder(200.00, 9.75, 209.75);
        $this->addLine($order, 100.00, 9.75);
        $this->addLine($order, 100.00, 0.00);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 19000);

        $this->assertTrue($calc->succeeded());

        // BOTH merchandise buckets shrink — the tax-free line is merchandise,
        // not a protected charge.
        $this->assertLessThan($calc->originalBasisCents, $calc->revisedBasisCents);
        $this->assertLessThan($calc->originalUntaxedMerchandiseCents, $calc->revisedUntaxedMerchandiseCents);

        $this->assertSame(
            19000,
            $calc->revisedBasisCents + $calc->revisedUntaxedMerchandiseCents
                + $calc->revisedTaxCents + $calc->revisedSpecialTaxCents,
        );
    }

    public function test_mixed_taxable_and_untaxed_merchandise_allocates_across_both(): void
    {
        $order = $this->makeOrder(200.00, 9.75, 209.75);
        $this->addLine($order, 100.00, 9.75);
        $this->addLine($order, 100.00, 0.00);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 19000);

        $this->assertTrue($calc->succeeded());
        $this->assertCount(2, $calc->lineAllocations, 'Both merchandise lines must receive a share.');

        foreach ($calc->lineAllocations as $row) {
            $this->assertGreaterThan(0, $row['reduced_by'], 'Every reducible line shares the waiver.');
        }

        $this->assertSame(
            $calc->goodwillCents,
            array_sum(array_column($calc->lineAllocations, 'reduced_by')),
        );
    }

    public function test_untaxed_merchandise_plus_protected_added_fees(): void
    {
        // $150 untaxed merchandise + $10 added fees = $160 grand total.
        $order = $this->makeOrder(150.00, 0.00, 160.00);
        $this->addLine($order, 150.00, 0.00, ['added_fees' => 10.00]);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 14000);

        $this->assertTrue($calc->succeeded());
        $this->assertSame(1000, $calc->protectedCents, 'Added fees stay protected.');

        // The $10 fee survives untouched; only merchandise absorbs the waiver.
        $this->assertSame(13000, $calc->revisedUntaxedMerchandiseCents);
        $this->assertSame(2000, $calc->goodwillCents);
        $this->assertSame(14000, $calc->revisedGrandTotalCents);
    }

    public function test_zero_ordinary_tax_with_nonzero_special_tax(): void
    {
        // $200 untaxed merchandise, but 2% special tax applies ($4.00).
        $order = $this->makeOrder(200.00, 0.00, 204.00);
        $this->addLine($order, 200.00, 0.00, ['special_tax' => 4.00]);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 18500);

        $this->assertTrue($calc->succeeded());
        $this->assertSame(0, $calc->revisedTaxCents, 'Ordinary tax stays zero when the posture was exempt.');
        $this->assertGreaterThan(0, $calc->revisedSpecialTaxCents, 'Special tax still applies to its own basis.');
        $this->assertLessThan($calc->originalSpecialTaxCents, $calc->revisedSpecialTaxCents);
        $this->assertSame(
            18500,
            $calc->revisedUntaxedMerchandiseCents + $calc->revisedBasisCents
                + $calc->revisedTaxCents + $calc->revisedSpecialTaxCents,
        );
    }

    public function test_accepted_total_below_protected_charges_is_rejected(): void
    {
        // $50 merchandise + $40 protected fees = $90. Accepting $30 cannot
        // even cover the fees, which Goodwill may not reduce.
        $order = $this->makeOrder(50.00, 0.00, 90.00);
        $this->addLine($order, 50.00, 0.00, ['added_fees' => 40.00]);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 3000);

        $this->assertFalse($calc->succeeded());
        $this->assertSame(GoodwillCalculationFailure::PaymentBelowNonReducibleFloor, $calc->failure);
    }

    public function test_special_tax_is_recomputed_at_its_own_rate_and_never_blended(): void
    {
        // $200 basis, 9.75% ordinary ($19.50) + 2% special ($4.00) = $223.50.
        $order = $this->makeOrder(200.00, 19.50, 223.50);
        $this->addLine($order, 200.00, 19.50, ['special_tax' => 4.00]);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 18500);

        $this->assertTrue($calc->succeeded());
        $this->assertSame(400, $calc->originalSpecialTaxCents);

        // Special tax must SHRINK with the basis, not survive unchanged.
        $this->assertLessThan($calc->originalSpecialTaxCents, $calc->revisedSpecialTaxCents);
        $this->assertGreaterThan(0, $calc->revisedSpecialTaxCents);

        // And the two taxes stay separate figures that still reconcile.
        $this->assertSame(
            18500,
            $calc->revisedBasisCents + $calc->revisedTaxCents + $calc->revisedSpecialTaxCents,
        );
    }

    // ── Allocation ─────────────────────────────────────────────────────────

    public function test_multi_line_allocation_sums_exactly_to_the_waived_amount(): void
    {
        // Three uneven lines so naive per-line rounding would drift.
        $order = $this->makeOrder(300.00, 29.25, 329.25);
        $this->addLine($order, 100.01, 9.75);
        $this->addLine($order, 100.01, 9.75);
        $this->addLine($order, 99.98, 9.75);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 28000);

        $this->assertTrue($calc->succeeded());
        $this->assertCount(3, $calc->lineAllocations);
        $this->assertSame(
            $calc->goodwillCents,
            array_sum(array_column($calc->lineAllocations, 'reduced_by')),
            'Allocated cents must sum exactly to the waived amount.'
        );
        $this->assertSame(
            $calc->revisedTaxCents,
            array_sum(array_column($calc->lineAllocations, 'tax_after')),
        );
    }

    public function test_allocation_is_deterministic(): void
    {
        $order = $this->makeOrder(300.00, 29.25, 329.25);
        $this->addLine($order, 100.01, 9.75);
        $this->addLine($order, 100.01, 9.75);
        $this->addLine($order, 99.98, 9.75);
        $order = $order->fresh();

        $a = GoodwillAdjustmentService::calculate($order, 28000);
        $b = GoodwillAdjustmentService::calculate($order, 28000);

        $this->assertSame($a->lineAllocations, $b->lineAllocations);
    }

    // ── Boundaries ─────────────────────────────────────────────────────────

    public function test_one_cent_accepted_is_valid_and_reconciles(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 19.50);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 1);

        $this->assertTrue($calc->succeeded());
        $this->assertSame(1, $calc->revisedGrandTotalCents);
        $this->assertSame(1, $calc->revisedBasisCents + $calc->revisedTaxCents);
    }

    // ── Refusals ───────────────────────────────────────────────────────────

    public function test_zero_collected_is_refused_as_a_write_off(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 19.50);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 0);

        $this->assertFalse($calc->succeeded());
        $this->assertSame(GoodwillCalculationFailure::NoPaymentReceived, $calc->failure);
    }

    public function test_payment_meeting_the_total_leaves_nothing_to_waive(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 19.50);

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 21950);

        $this->assertFalse($calc->succeeded());
        $this->assertSame(GoodwillCalculationFailure::NothingToWaive, $calc->failure);
    }

    public function test_unreconstructable_basis_refuses_with_the_underlying_reason(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50); // no lines, not an extension

        $calc = GoodwillAdjustmentService::calculate($order->fresh(), 18500);

        $this->assertFalse($calc->succeeded());
        $this->assertSame(GoodwillCalculationFailure::BasisUnreconstructable, $calc->failure);
        $this->assertNotNull($calc->basisFailure, 'The underlying basis failure must be surfaced, not swallowed.');
    }

    public function test_calculation_is_read_only(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 19.50);

        $before = DB::table('orders')->where('id', $order->id)->first();
        $beforeLines = DB::table('order_products')->where('order_id', $order->id)->get()->toArray();

        GoodwillAdjustmentService::calculate($order->fresh(), 18500);

        $this->assertEquals($before, DB::table('orders')->where('id', $order->id)->first());
        $this->assertEquals($beforeLines, DB::table('order_products')->where('order_id', $order->id)->get()->toArray());
        $this->assertSame(0, DB::table('order_goodwill_adjustments')->count());
    }

    public function test_tax_free_extension_child_is_reducible_merchandise(): void
    {
        // Extension children carry no order_products; the resolver reconstructs
        // them from the linked charge. A tax-free extension is still
        // merchandise, so Goodwill may reduce it.
        Order::create([
            'order_number' => 'ORD-8100', 'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id, 'customer_name' => 'Ext Customer',
            'subtotal' => 500.00, 'tax_amount' => 0.00, 'grand_total' => 500.00,
        ]);

        $child = Order::create([
            'order_number' => 'ORD-8100-A', 'reference_order_number' => 'ORD-8100',
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id, 'customer_name' => 'Ext Customer',
            'subtotal' => 200.00, 'tax_amount' => 0.00, 'grand_total' => 200.00,
            'is_tax_exempt' => 'Yes',
        ]);

        DB::table('billing_charges')->insert([
            'unique_id' => 'BC-EXT-GW-'.$child->id,
            'billing_charge_type' => \App\Enums\Billing\BillingChargeType::Extension->value,
            'customer_id' => $this->customer->id,
            'child_order_id' => $child->id,
            'amount' => 200.00, 'tax_amount' => 0.00, 'tax_type' => 'free',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $calc = GoodwillAdjustmentService::calculate($child->fresh(), 18500);

        $this->assertTrue($calc->succeeded(), 'A tax-free extension is reducible merchandise.');
        $this->assertSame(1500, $calc->goodwillCents);
        $this->assertSame(18500, $calc->revisedUntaxedMerchandiseCents);
        $this->assertSame(0, $calc->revisedTaxCents);
        // No order_products exist, so there is no line to attribute a share to.
        $this->assertSame([], $calc->lineAllocations);
    }

    public function test_every_failure_reason_has_a_message(): void
    {
        foreach (GoodwillCalculationFailure::cases() as $case) {
            $this->assertNotSame('', trim($case->message()));
        }
    }
}

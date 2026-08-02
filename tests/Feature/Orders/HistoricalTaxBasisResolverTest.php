<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\HistoricalTaxBasisFailure;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Services\Orders\HistoricalTaxBasisResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * HistoricalTaxBasisResolver — reconstruction and rejection.
 *
 * The defect this resolver replaces is subtle precisely because the wrong
 * answer looks reasonable: `tax_amount / subtotal` on a mixed order returns
 * a real number in a believable range. `mixed_taxable_and_tax_free_derives_
 * the_true_rate_not_the_subtotal_rate` is therefore the load-bearing test —
 * it asserts the resolver returns 9.75% where the old formula returned
 * 4.875%, and it would pass just as happily against the defect if it only
 * checked "a rate came back".
 */
class HistoricalTaxBasisResolverTest extends TestCase
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
            'unique_id'    => 'PRD-TEST-1',
            'product_name' => 'Test Excavator',
            'slug'         => 'test-excavator',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    private function makeOrder(float $subtotal, float $tax, float $grandTotal, float $discount = 0.0): Order
    {
        return Order::create([
            'order_date'      => now()->format('Y-m-d'),
            'customer_id'     => $this->customer->id,
            'customer_name'   => 'Test Customer',
            'subtotal'        => $subtotal,
            'tax_amount'      => $tax,
            'discount_amount' => $discount,
            'grand_total'     => $grandTotal,
        ]);
    }

    /**
     * @param  array<string,mixed>|null  $frozen  null writes NULL product_data (the legacy case).
     *
     * Special tax and added fees are written to BOTH the current columns and
     * the frozen snapshot, which is what checkout does — at creation the two
     * agree, and the column is what the resolver reads. Tests that need them
     * to DIVERGE (proving which one is authoritative) set the column
     * explicitly via $columnOverrides.
     *
     * @param  array{special_tax?:float, added_fees?:float}  $columnOverrides
     */
    private function addLine(
        Order $order,
        float $subTotal,
        float $tax,
        ?array $frozen = [],
        array $columnOverrides = [],
    ): void {
        static $n = 0;
        $n++;

        $order->products()->create([
            'unique_id'    => 'ORD-SCH-TEST-'.$n,
            'product_id'   => $this->productId,
            'product_name' => 'Test Excavator',
            'price'        => $subTotal,
            'quantity'     => 1,
            'sub_total'    => $subTotal,
            'tax'          => $tax,
            'special_tax'  => $columnOverrides['special_tax'] ?? ($frozen['special_tax'] ?? 0),
            'added_fees'   => $columnOverrides['added_fees'] ?? ($frozen['added_fees'] ?? 0),
            'total'        => $subTotal + $tax,
            'product_data' => $frozen === null ? null : json_encode($frozen),
        ]);
    }

    // ── Successful reconstruction ──────────────────────────────────────────

    public function test_fully_taxable_single_line_derives_the_exact_rate(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 19.50);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertTrue($basis->succeeded(), 'Expected a clean reconstruction.');
        $this->assertSame(20000, $basis->ordinaryBasisCents);
        $this->assertSame(1950, $basis->ordinaryTaxCents);
        $this->assertSame(0, $basis->untaxedMerchandiseBasisCents);
        $this->assertEqualsWithDelta(0.0975, $basis->ordinaryRate(), 0.0000001);
    }

    public function test_mixed_taxable_and_tax_free_derives_the_true_rate_not_the_subtotal_rate(): void
    {
        // $100 taxable at 9.75% = $9.75 tax; $100 tax-free. Subtotal $200.
        $order = $this->makeOrder(200.00, 9.75, 209.75);
        $this->addLine($order, 100.00, 9.75);   // taxable
        $this->addLine($order, 100.00, 0.00);   // is_tax_free_item

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertTrue($basis->succeeded());

        // The denominator must be the taxable basis only.
        $this->assertSame(10000, $basis->ordinaryBasisCents);
        $this->assertSame(10000, $basis->untaxedMerchandiseBasisCents);
        $this->assertEqualsWithDelta(0.0975, $basis->ordinaryRate(), 0.0000001);

        // The defect being replaced: tax_amount / subtotal = 9.75/200 = 4.875%.
        $defectiveRate = 9.75 / 200.00;
        $this->assertEqualsWithDelta(0.04875, $defectiveRate, 0.0000001);
        $this->assertNotEqualsWithDelta($defectiveRate, $basis->ordinaryRate(), 0.0001);
    }

    public function test_zero_tax_order_resolves_with_a_zero_rate(): void
    {
        $order = $this->makeOrder(200.00, 0.00, 200.00);
        $this->addLine($order, 200.00, 0.00);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertTrue($basis->succeeded());
        $this->assertSame(0, $basis->ordinaryTaxCents);
        $this->assertSame(0.0, $basis->ordinaryRate());
        $this->assertSame(20000, $basis->untaxedMerchandiseBasisCents);
    }

    public function test_special_tax_and_added_fees_are_separated_and_never_blended(): void
    {
        // $200 taxable @9.75% = $19.50 ordinary; special tax $4.00; fees $6.00.
        $order = $this->makeOrder(200.00, 19.50, 229.50);
        $this->addLine($order, 200.00, 19.50, ['special_tax' => 4.00, 'added_fees' => 6.00]);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertTrue($basis->succeeded());
        $this->assertSame(400, $basis->specialTaxCents);
        $this->assertSame(600, $basis->addedFeesCents);
        $this->assertSame(1950, $basis->ordinaryTaxCents);

        // Separate rates, never summed.
        $this->assertEqualsWithDelta(0.0975, $basis->ordinaryRate(), 0.0000001);
        $this->assertEqualsWithDelta(0.02, $basis->specialRate(), 0.0000001);
    }

    public function test_special_tax_is_read_from_the_current_column_not_the_frozen_snapshot(): void
    {
        // The two are deliberately made to disagree. The column says 4.00 and
        // the order's grand total agrees with the column; the snapshot still
        // holds the original 9.00. Only the column can be the live value —
        // sourcing it from the snapshot is exactly what made an adjusted order
        // permanently unreconcilable, which is why these columns exist.
        $order = $this->makeOrder(200.00, 19.50, 223.50);
        $this->addLine($order, 200.00, 19.50, ['special_tax' => 9.00], ['special_tax' => 4.00]);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertTrue($basis->succeeded(), 'Reconciliation must follow the current column.');
        $this->assertSame(400, $basis->specialTaxCents);
    }

    public function test_added_fees_are_read_from_the_current_column_not_the_frozen_snapshot(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 225.50);
        $this->addLine($order, 200.00, 19.50, ['added_fees' => 11.00], ['added_fees' => 6.00]);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertTrue($basis->succeeded());
        $this->assertSame(600, $basis->addedFeesCents);
    }

    public function test_multi_line_same_rate_reconciles_exactly(): void
    {
        // 12.34 + 87.66 = 100.00 basis @ 9.75% -> 1.20 + 8.55 = 9.75
        $order = $this->makeOrder(100.00, 9.75, 109.75);
        $this->addLine($order, 12.34, 1.20);
        $this->addLine($order, 87.66, 8.55);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertTrue($basis->succeeded(), 'Per-line rounding must not be mistaken for mixed rates.');
        $this->assertSame(10000, $basis->ordinaryBasisCents);
        $this->assertSame(975, $basis->ordinaryTaxCents);
    }

    // ── Mandatory rejections ───────────────────────────────────────────────

    public function test_legacy_order_with_no_lines_is_rejected(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::NoLineData, $basis->failure);
    }

    public function test_line_tax_that_does_not_reconcile_with_order_tax_is_rejected(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 18.00); // disagrees with stored 19.50

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::UnreconciledTax, $basis->failure);
    }

    public function test_line_subtotal_that_does_not_reconcile_is_rejected(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 150.00, 19.50); // subtotal disagrees

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::UnreconciledSubtotal, $basis->failure);
    }

    public function test_zero_taxable_basis_with_stored_tax_is_rejected(): void
    {
        // Every line tax-free, yet the order claims tax: denominator would be 0.
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 0.00);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertFalse($basis->succeeded());
        // Reconciliation catches it first; either verdict is a rejection, never a guessed rate.
        $this->assertContains($basis->failure, [
            HistoricalTaxBasisFailure::UnreconciledTax,
            HistoricalTaxBasisFailure::ZeroBasisWithTax,
        ]);
    }

    public function test_mixed_tax_rates_across_lines_are_rejected(): void
    {
        // 100.00 @ 9.75% = 9.75 ; 100.00 @ 5% = 5.00 -> no single rate.
        $order = $this->makeOrder(200.00, 14.75, 214.75);
        $this->addLine($order, 100.00, 9.75);
        $this->addLine($order, 100.00, 5.00);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::MixedTaxRates, $basis->failure);
    }

    public function test_tax_recorded_against_no_basis_is_rejected(): void
    {
        $order = $this->makeOrder(0.00, 5.00, 5.00);
        $this->addLine($order, 0.00, 5.00);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::UnexplainedTaxOverride, $basis->failure);
    }

    public function test_missing_frozen_product_data_is_rejected(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 19.50, null); // legacy row, no snapshot

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::FrozenDataUnavailable, $basis->failure);
    }

    public function test_stored_totals_that_do_not_reconcile_are_rejected(): void
    {
        // Lines agree with subtotal and tax, but grand_total is impossible.
        $order = $this->makeOrder(200.00, 19.50, 500.00);
        $this->addLine($order, 200.00, 19.50);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::UnreconciledGrandTotal, $basis->failure);
    }

    // ── Guarantees ─────────────────────────────────────────────────────────

    public function test_resolving_is_read_only_and_deterministic(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 19.50);

        $before = DB::table('orders')->where('id', $order->id)->first();
        $beforeLines = DB::table('order_products')->where('order_id', $order->id)->get()->toArray();

        $first  = HistoricalTaxBasisResolver::resolve($order->fresh());
        $second = HistoricalTaxBasisResolver::resolve($order->fresh());

        $after = DB::table('orders')->where('id', $order->id)->first();
        $afterLines = DB::table('order_products')->where('order_id', $order->id)->get()->toArray();

        $this->assertEquals($before, $after, 'Resolver must not mutate the order.');
        $this->assertEquals($beforeLines, $afterLines, 'Resolver must not normalize line data.');
        $this->assertSame($first->ordinaryBasisCents, $second->ordinaryBasisCents);
        $this->assertSame($first->ordinaryTaxCents, $second->ordinaryTaxCents);
    }

    public function test_every_failure_reason_has_an_operator_message(): void
    {
        foreach (HistoricalTaxBasisFailure::cases() as $case) {
            $this->assertNotSame('', trim($case->message()), $case->name.' has no message.');
        }
    }
}

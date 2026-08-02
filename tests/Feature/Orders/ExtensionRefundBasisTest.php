<?php

namespace Tests\Feature\Orders;

use App\Enums\Billing\BillingChargeType;
use App\Enums\Orders\HistoricalTaxBasisFailure;
use App\Enums\Orders\HistoricalTaxBasisSource;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Services\Orders\HistoricalTaxBasisResolver;
use App\Services\Orders\PaymentAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Extension child orders and the historical tax basis.
 *
 * Extension children own no order_products rows by design
 * (Extension\StoreController creates them with totals only; IndexController
 * documents the same fact), yet they are independently payable, appear in
 * the orders index, render on the ordinary Order Details page, and have no
 * refund-path guard — so they reach the Standard refund path.
 *
 * The line-based resolver therefore rejected them, which regressed a case
 * that previously worked: an extension has exactly one tax posture, so
 * orders.subtotal genuinely WAS its taxable basis and the old
 * tax_amount / subtotal formula happened to be correct there. Two named
 * modes restore that, without reintroducing a general fallback —
 * `extension_order_level_mode_is_not_a_general_lineless_fallback` is the
 * test that holds that line.
 */
class ExtensionRefundBasisTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        HistoricalTaxBasisResolver::flushSchemaMemo();
        $this->customer = Customer::factory()->create();
    }

    private function makeParent(string $orderNumber = 'ORD-9000'): Order
    {
        return Order::create([
            'order_number'  => $orderNumber,
            'order_date'    => now()->format('Y-m-d'),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Extension Customer',
            'subtotal'      => 500.00, 'tax_amount' => 48.75, 'grand_total' => 548.75,
        ]);
    }

    /** An extension child exactly as Extension\StoreController builds one: totals, no lines. */
    private function makeExtensionChild(
        float $base,
        float $tax,
        string $parentNumber = 'ORD-9000',
        string $suffix = 'A',
        bool $exempt = false,
        float $discount = 0.0,
    ): Order {
        return Order::create([
            'order_number'           => $parentNumber.'-'.$suffix,
            'reference_order_number' => $parentNumber,
            'order_date'             => now()->format('Y-m-d'),
            'customer_id'            => $this->customer->id,
            'customer_name'          => 'Extension Customer',
            'subtotal'               => $base,
            'tax_amount'             => $tax,
            'discount_amount'        => $discount,
            'grand_total'            => $base + $tax - $discount,
            'is_tax_exempt'          => $exempt ? 'Yes' : 'No',
        ]);
    }

    private function addExtensionCharge(Order $child, float $amount, float $tax, string $taxType, ?string $uid = null): void
    {
        static $n = 0;
        $n++;

        DB::table('billing_charges')->insert([
            'unique_id'           => $uid ?? 'BC-EXT-'.$n.'-'.$child->id,
            'billing_charge_type' => BillingChargeType::Extension->value,
            'customer_id'         => $this->customer->id,
            'child_order_id'      => $child->id,
            'amount'              => $amount,
            'tax_amount'          => $tax,
            'tax_type'            => $taxType,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    // ── Mode 1: extension billing charge ───────────────────────────────────

    public function test_extension_child_with_a_valid_linked_charge_resolves_from_the_charge(): void
    {
        $this->makeParent();
        $child = $this->makeExtensionChild(100.00, 9.75);
        $this->addExtensionCharge($child, 100.00, 9.75, 'add');

        $basis = HistoricalTaxBasisResolver::resolve($child->fresh());

        $this->assertTrue($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisSource::ExtensionBillingCharge, $basis->source);
        $this->assertSame(10000, $basis->ordinaryBasisCents);
        $this->assertSame(975, $basis->ordinaryTaxCents);
        $this->assertEqualsWithDelta(0.0975, $basis->ordinaryRate(), 0.0000001);
    }

    public function test_tax_free_extension_with_a_valid_linked_charge_resolves_with_zero_basis(): void
    {
        $this->makeParent();
        $child = $this->makeExtensionChild(100.00, 0.00, exempt: true);
        $this->addExtensionCharge($child, 100.00, 0.00, 'free');

        $basis = HistoricalTaxBasisResolver::resolve($child->fresh());

        $this->assertTrue($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisSource::ExtensionBillingCharge, $basis->source);
        $this->assertSame(0, $basis->ordinaryBasisCents);
        $this->assertSame(0, $basis->ordinaryTaxCents);
        $this->assertSame(10000, $basis->untaxedMerchandiseBasisCents);
        $this->assertSame(0.0, $basis->ordinaryRate());
    }

    public function test_charge_that_disagrees_with_the_order_is_rejected_and_does_not_fall_through(): void
    {
        $this->makeParent();
        $child = $this->makeExtensionChild(100.00, 9.75);
        $this->addExtensionCharge($child, 250.00, 9.75, 'add'); // contradicts the order

        $basis = HistoricalTaxBasisResolver::resolve($child->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::ExtensionChargeMismatch, $basis->failure);
        // Must NOT have silently degraded to the weaker order-level mode.
        $this->assertNull($basis->source);
    }

    public function test_multiple_conflicting_extension_charges_are_rejected(): void
    {
        $this->makeParent();
        $child = $this->makeExtensionChild(100.00, 9.75);
        $this->addExtensionCharge($child, 100.00, 9.75, 'add');
        $this->addExtensionCharge($child, 100.00, 9.75, 'add');

        $basis = HistoricalTaxBasisResolver::resolve($child->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::ConflictingExtensionCharges, $basis->failure);
    }

    // ── Mode 2: extension order-level fallback ─────────────────────────────

    public function test_missing_charge_from_the_best_effort_bridge_resolves_at_order_level(): void
    {
        // The BillingEngine bridge is wrapped in try/catch-and-log, so a
        // charge-less extension is a real shape, not a hypothetical one.
        $this->makeParent();
        $child = $this->makeExtensionChild(100.00, 9.75);

        $basis = HistoricalTaxBasisResolver::resolve($child->fresh());

        $this->assertTrue($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisSource::ExtensionOrderLevel, $basis->source);
        $this->assertSame(10000, $basis->ordinaryBasisCents);
        $this->assertEqualsWithDelta(0.0975, $basis->ordinaryRate(), 0.0000001);
    }

    // ── The line that must not be crossed ──────────────────────────────────

    public function test_extension_order_level_mode_is_not_a_general_lineless_fallback(): void
    {
        // Same totals, same absence of lines — but no extension relationship.
        $anonymous = Order::create([
            'order_number'  => 'ORD-7777',
            'order_date'    => now()->format('Y-m-d'),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Anonymous',
            'subtotal'      => 100.00, 'tax_amount' => 9.75, 'grand_total' => 109.75,
        ]);

        $basis = HistoricalTaxBasisResolver::resolve($anonymous->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::NoLineData, $basis->failure);
        $this->assertNull($basis->source);
    }

    public function test_lineless_extension_with_ambiguous_discount_state_is_rejected(): void
    {
        $this->makeParent();
        $child = $this->makeExtensionChild(100.00, 9.75, discount: 10.00);

        $basis = HistoricalTaxBasisResolver::resolve($child->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::ExtensionInvariantsUnproven, $basis->failure);
    }

    public function test_lineless_extension_whose_totals_hide_an_unaccounted_component_is_rejected(): void
    {
        // grand_total exceeds subtotal + tax: something (special tax, a fee)
        // is in there that the order-level mode cannot account for.
        $this->makeParent();
        $child = Order::create([
            'order_number'           => 'ORD-9000-B',
            'reference_order_number' => 'ORD-9000',
            'order_date'             => now()->format('Y-m-d'),
            'customer_id'            => $this->customer->id,
            'customer_name'          => 'Extension Customer',
            'subtotal'   => 100.00, 'tax_amount' => 9.75, 'grand_total' => 125.00,
            'is_tax_exempt' => 'No',
        ]);

        $basis = HistoricalTaxBasisResolver::resolve($child->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::ExtensionInvariantsUnproven, $basis->failure);
    }

    public function test_tax_exempt_extension_carrying_tax_is_rejected(): void
    {
        $this->makeParent();
        $child = $this->makeExtensionChild(100.00, 9.75, exempt: true);

        $basis = HistoricalTaxBasisResolver::resolve($child->fresh());

        $this->assertFalse($basis->succeeded());
        $this->assertSame(HistoricalTaxBasisFailure::ExtensionInvariantsUnproven, $basis->failure);
    }

    // ── The regression this whole audit was about ──────────────────────────

    public function test_paid_extension_standard_refund_matches_its_pre_commit_2_result(): void
    {
        // Before Commit 2 this returned 9.75, because an extension has one tax
        // posture and orders.subtotal genuinely was its taxable basis. Commit 2
        // made it throw. Both extension modes must restore the original value.
        $this->makeParent();

        $withCharge = $this->makeExtensionChild(100.00, 9.75, suffix: 'A');
        $this->addExtensionCharge($withCharge, 100.00, 9.75, 'add');

        $withoutCharge = $this->makeExtensionChild(100.00, 9.75, suffix: 'B');

        $legacyExpected = round(109.75 - (109.75 / (1 + (9.75 / 100.00))), 2);
        $this->assertEqualsWithDelta(9.75, $legacyExpected, 0.01);

        $this->assertEqualsWithDelta(
            $legacyExpected,
            PaymentAllocationService::proportionalTaxRefund($withCharge->fresh(), 109.75),
            0.01,
            'Charge-backed extension refund must match the pre-Commit-2 result.'
        );

        $this->assertEqualsWithDelta(
            $legacyExpected,
            PaymentAllocationService::proportionalTaxRefund($withoutCharge->fresh(), 109.75),
            0.01,
            'Order-level extension refund must match the pre-Commit-2 result.'
        );
    }
}

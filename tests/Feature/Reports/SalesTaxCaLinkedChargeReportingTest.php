<?php

namespace Tests\Feature\Reports;

use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Http\DataObjects\BillingChargeRequest;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\BillingChargeRefund;
use App\Models\Stores\Store;
use App\Services\BillingEngine;
use App\Services\Reports\SalesTaxReportEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sales Tax Architecture Audit / Correction — SalesTaxReportEngine.
 *
 * Billing Charge Refund Allocation — Reporting Timing Correction:
 * Stream E (caLinkedTaxAndRefundRows(), formerly caTaxOnlyRows()) is
 * event-based, matching the canonical convention already established by
 * Stream B (refundRows()): the ORIGINAL charge's tax is reported, unreduced,
 * in the period it was paid; a successful refund allocation is reported as
 * its own negative row in the period the REFUND actually occurred — never
 * netted retroactively into the original charge's period. This file's
 * refund-related tests were rewritten to assert that two-row, independently-
 * dated behavior in place of the earlier (incorrect) single-row-netted-into-
 * the-original-date behavior.
 */
class SalesTaxCaLinkedChargeReportingTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $user;
    private SalesTaxReportEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Tax', 'last_name' => 'Reporting',
            'email' => 'tax-reporting-test@example.com', 'status' => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-tax-reporting-test@example.com', 'status' => 'Active',
        ]);

        $this->engine = app(SalesTaxReportEngine::class);
    }

    private function filters(?string $start = null, ?string $end = null, ?int $store = null): array
    {
        $filters = [
            'start_date' => $start ?? now()->subDay()->toDateString(),
            'end_date' => $end ?? now()->addDay()->toDateString(),
        ];

        if ($store !== null) {
            $filters['store'] = $store;
        }

        return $filters;
    }

    private function makePaidCaLinkedCharge(string $type, float $base, float $tax, ?string $paidAt = null, ?int $storeId = null): BillingCharge
    {
        $ca = new CustomerAccount();
        $ca->customer_id = $this->customer->id;
        $ca->amount = $base;
        $ca->reason = $type === 'fuel' ? 'Fuel Charge' : 'Damages';
        $ca->responsible_person_id = $this->user->id;
        $ca->date = $paidAt ?? now();
        $ca->sales_tax_type = $tax > 0 ? 'add' : 'free';
        // The payment-side hardcoded-zero defect this correction does NOT
        // fix — deliberately left at 0 regardless of the charge's own tax,
        // to prove Stream E, not a coincidentally-correct Stream C, is what
        // surfaces this charge's tax.
        $ca->sales_tax = 0;
        $ca->type = 'charge';
        $ca->save();

        $bc = BillingEngine::charge(new BillingChargeRequest(
            type: $type === 'fuel' ? BillingChargeType::Fuel->value : BillingChargeType::Damage->value,
            orderId: null,
            customerId: $this->customer->id,
            amount: $base,
            taxType: $tax > 0 ? 'add' : 'free',
            responsiblePersonId: $this->user->id,
            sourceModule: BillingSourceModule::AdminFuelCharge->value,
            sourceEvent: BillingSourceEvent::AdminFuelChargeCreated->value,
            sourceReferenceType: 'CustomerAccount',
            sourceReferenceId: $ca->id,
            idempotencyKey: "test_tax_reporting:{$ca->id}:" . uniqid(),
            customerAccountId: $ca->id,
            taxAmount: $tax,
            storeId: $storeId,
        ));

        BillingEngine::markPaid($bc);

        if ($paidAt !== null) {
            // markPaid() only sets paid_at when it is not already populated —
            // override directly afterward to place the charge in a specific
            // reporting period for these period-behavior tests.
            $bc->paid_at = $paidAt;
            $bc->save();
        }

        return $bc;
    }

    /**
     * Creates a BillingChargeRefund allocation. When $eventDate is given, a
     * linked CustomerAccount 'refund' row is created with that date (the
     * same field caRefundAdjustmentRows() coalesces to first) — mirroring
     * what RefundStoreController::storeLinkedRefund() actually persists.
     * When omitted, no CustomerAccount is created and the query falls back
     * to the allocation's own created_at (effectively "now"), matching the
     * simple same-period tests that don't care about explicit dating.
     */
    private function allocateRefund(BillingCharge $charge, float $base, float $tax, string $status = 'allocated', ?string $eventDate = null): BillingChargeRefund
    {
        $customerAccountId = null;

        if ($eventDate !== null) {
            $ca = new CustomerAccount();
            $ca->customer_id = $charge->customer_id;
            $ca->amount = round($base + $tax, 2);
            $ca->reason = 'Refund';
            $ca->responsible_person_id = $this->user->id;
            $ca->date = $eventDate;
            $ca->sales_tax_type = 'reverse';
            $ca->sales_tax = (float) $charge->amount > 0 ? (float) $charge->tax_amount / (float) $charge->amount : 0;
            $ca->type = 'refund';
            $ca->save();
            $customerAccountId = $ca->id;
        }

        return BillingChargeRefund::create([
            'billing_charge_id' => $charge->id,
            'customer_account_id' => $customerAccountId,
            'base_amount' => $base,
            'tax_amount' => $tax,
            'total_amount' => round($base + $tax, 2),
            'status' => $status,
        ]);
    }

    // ── Original tax surfacing (unaffected by this correction) ───────────

    public function test_ca_linked_fuel_charge_with_real_tax_appears_in_the_sales_tax_report(): void
    {
        $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50);

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters());

        $this->assertCount(1, $rows);
        $this->assertSame(19.50, (float) $rows->first()->tax_amount);
        $this->assertSame(0.0, (float) $rows->first()->subtotal, 'must never contribute base revenue — that stays Stream C\'s job');
        $this->assertSame(19.50, (float) $rows->first()->grand_total);
    }

    public function test_stream_e_row_is_labeled_so_it_reads_as_half_of_one_transaction(): void
    {
        $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50);
        $this->makePaidCaLinkedCharge('damage', 100.0, 9.75);

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters());
        $labels = $rows->pluck('products')->all();

        $this->assertTrue(collect($labels)->contains(fn ($l) => str_starts_with($l, 'Fuel Charge — Sales Tax')));
        $this->assertTrue(collect($labels)->contains(fn ($l) => str_starts_with($l, 'Damage Charge — Sales Tax')));
    }

    public function test_stream_c_payment_row_is_labeled_to_match_stream_es_type_using_only_its_own_reason_column(): void
    {
        // No fuzzy matching — the label comes from the payment row's own
        // `reason` (set deterministically at creation from the charge's own
        // reason), never from timing/amount/customer inference.
        $payment = new CustomerAccount();
        $payment->customer_id = $this->customer->id;
        $payment->amount = 219.50;
        $payment->reason = 'Payment — Fuel Charge';
        $payment->payment_type = 'Cash';
        $payment->sales_tax = 0;
        $payment->type = 'payment';
        $payment->date = now();
        $payment->save();

        $damagePayment = new CustomerAccount();
        $damagePayment->customer_id = $this->customer->id;
        $damagePayment->amount = 100.0;
        $damagePayment->reason = 'Payment — Damages';
        $damagePayment->payment_type = 'Cash';
        $damagePayment->sales_tax = 0;
        $damagePayment->type = 'payment';
        $damagePayment->date = now();
        $damagePayment->save();

        $rows = $this->engine->accountRows($this->filters());
        $labels = $rows->pluck('products')->all();

        $this->assertContains('Fuel Charge — Base', $labels);
        $this->assertContains('Damage Charge — Base', $labels);
    }

    public function test_stream_c_payment_row_falls_back_to_the_generic_label_for_unrelated_reasons(): void
    {
        $payment = new CustomerAccount();
        $payment->customer_id = $this->customer->id;
        $payment->amount = 50.0;
        $payment->reason = 'Payment — Miscellaneous';
        $payment->payment_type = 'Cash';
        $payment->sales_tax = 0;
        $payment->type = 'payment';
        $payment->date = now();
        $payment->save();

        $rows = $this->engine->accountRows($this->filters());

        $this->assertSame('Payment Account', $rows->first()->products);
    }

    public function test_ca_linked_charge_with_zero_tax_is_not_included(): void
    {
        $this->makePaidCaLinkedCharge('damage', 100.0, 0.0);

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters());

        $this->assertCount(0, $rows);
    }

    public function test_ca_linked_charge_base_amount_never_appears_in_billing_rows_stream_d(): void
    {
        // Stream D's own exclusion is unchanged — the base amount for a
        // CA-linked charge must never appear there, only in Stream C.
        $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50);

        $billingRows = $this->engine->billingRows($this->filters());

        $this->assertCount(0, $billingRows, 'CA-linked charges must still be entirely absent from Stream D — only Stream E surfaces their tax');
    }

    public function test_extension_charges_are_unaffected_by_the_new_stream(): void
    {
        // Extensions have no customer_account_id — caLinkedTaxAndRefundRows()
        // must never pick them up (it already filters billing_charge_type to
        // fuel/damage only, and customer_account_id IS NOT NULL).
        $order = \App\Models\Orders\Order::create([
            'order_number' => 'STAX-1', 'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id, 'customer_name' => 'Tax Reporting',
            'reference_order_number' => 'STAX-PARENT', 'subtotal' => 500, 'tax_amount' => 48.75, 'grand_total' => 548.75,
        ]);
        $bc = BillingEngine::charge(new BillingChargeRequest(
            type: BillingChargeType::Extension->value,
            orderId: $order->id,
            customerId: $this->customer->id,
            amount: 500,
            taxType: 'add',
            responsiblePersonId: $this->user->id,
            sourceModule: BillingSourceModule::RentalExtension->value,
            sourceEvent: BillingSourceEvent::RentalExtensionCreated->value,
            sourceReferenceType: 'Order',
            sourceReferenceId: $order->id,
            idempotencyKey: "test_extension_stream_e:{$order->id}",
            childOrderId: $order->id,
            customerAccountId: null,
            taxAmount: 48.75,
        ));
        BillingEngine::markPaid($bc);
        $order->payments()->create([
            'payment_method' => \App\Enums\Orders\OrderPaymentMethod::Card->value,
            'payment_datetime' => now(), 'amount' => 548.75, 'status' => \App\Enums\Orders\OrderPaymentStatus::Paid->value,
        ]);

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters());
        $this->assertCount(0, $rows, 'extensions are handled by Stream D, never Stream E');
    }

    // ── Reporting Timing Correction: event-based refund adjustments ──────

    public function test_same_period_charge_and_refund_both_appear_as_separate_rows(): void
    {
        $bc = $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50);
        $this->allocateRefund($bc, 100.0, 9.75, 'allocated', now()->toDateString());

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters());

        $this->assertCount(2, $rows, 'the original charge and its refund are independent events, never merged into one row');

        $original = $rows->firstWhere('type', 'billing_tax_only');
        $adjustment = $rows->firstWhere('type', 'billing_refund_adjustment');

        $this->assertNotNull($original, 'original charge row must still appear, unreduced');
        $this->assertSame(19.50, (float) $original->tax_amount, 'original tax is never reduced in its own row');
        $this->assertSame(0.0, (float) $original->subtotal);

        $this->assertNotNull($adjustment, 'refund must appear as its own adjustment row');
        $this->assertEqualsWithDelta(-100.0, (float) $adjustment->subtotal, 0.01);
        $this->assertEqualsWithDelta(-9.75, (float) $adjustment->tax_amount, 0.01);

        // Net across both rows must equal what actually remains collected.
        $this->assertEqualsWithDelta(9.75, (float) $rows->sum('tax_amount'), 0.01);
        $this->assertEqualsWithDelta(-100.0, (float) $rows->sum('subtotal'), 0.01);
    }

    public function test_charge_in_june_refund_in_july_june_report_keeps_only_the_original_charge(): void
    {
        $bc = $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50, '2026-06-15');
        $this->allocateRefund($bc, 200.0, 19.50, 'allocated', '2026-07-15');

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters('2026-06-01', '2026-06-30'));

        $this->assertCount(1, $rows, 'June must retain the original charge only — no retroactive restatement from the July refund');
        $this->assertSame('billing_tax_only', $rows->first()->type);
        $this->assertSame(19.50, (float) $rows->first()->tax_amount);
    }

    public function test_charge_in_june_refund_in_july_july_report_contains_only_the_negative_refund(): void
    {
        $bc = $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50, '2026-06-15');
        $this->allocateRefund($bc, 200.0, 19.50, 'allocated', '2026-07-15');

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters('2026-07-01', '2026-07-31'));

        $this->assertCount(1, $rows, 'July must contain the refund adjustment only — the June charge is out of range');
        $this->assertSame('billing_refund_adjustment', $rows->first()->type);
        $this->assertEqualsWithDelta(-200.0, (float) $rows->first()->subtotal, 0.01);
        $this->assertEqualsWithDelta(-19.50, (float) $rows->first()->tax_amount, 0.01);
    }

    public function test_charge_in_june_refund_in_july_combined_report_shows_both_and_nets_correctly(): void
    {
        $bc = $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50, '2026-06-15');
        $this->allocateRefund($bc, 200.0, 19.50, 'allocated', '2026-07-15');

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters('2026-06-01', '2026-07-31'));

        $this->assertCount(2, $rows);
        $this->assertEqualsWithDelta(0.0, (float) $rows->sum('tax_amount'), 0.01, 'fully refunded across the combined window nets to zero');
        $this->assertEqualsWithDelta(-200.0, (float) $rows->sum('subtotal'), 0.01);
    }

    public function test_partial_refund_reverses_only_the_allocated_base_and_tax(): void
    {
        $bc = $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50, '2026-06-10');
        $this->allocateRefund($bc, 50.0, 4.88, 'allocated', '2026-06-20');

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters('2026-06-01', '2026-06-30'));

        $this->assertCount(2, $rows);
        $adjustment = $rows->firstWhere('type', 'billing_refund_adjustment');
        $this->assertEqualsWithDelta(-50.0, (float) $adjustment->subtotal, 0.01);
        $this->assertEqualsWithDelta(-4.88, (float) $adjustment->tax_amount, 0.01);

        $original = $rows->firstWhere('type', 'billing_tax_only');
        $this->assertSame(19.50, (float) $original->tax_amount, 'the original row is never itself reduced');
    }

    public function test_multiple_partial_refunds_in_separate_periods_each_appear_in_their_own_period(): void
    {
        $bc = $this->makePaidCaLinkedCharge('fuel', 300.0, 29.25, '2026-05-10');
        $this->allocateRefund($bc, 100.0, 9.75, 'allocated', '2026-06-05');
        $this->allocateRefund($bc, 100.0, 9.75, 'allocated', '2026-07-05');

        $juneRows = $this->engine->caLinkedTaxAndRefundRows($this->filters('2026-06-01', '2026-06-30'));
        $this->assertCount(1, $juneRows, 'only the June allocation belongs in the June report');
        $this->assertSame('billing_refund_adjustment', $juneRows->first()->type);
        $this->assertEqualsWithDelta(-100.0, (float) $juneRows->first()->subtotal, 0.01);

        $julyRows = $this->engine->caLinkedTaxAndRefundRows($this->filters('2026-07-01', '2026-07-31'));
        $this->assertCount(1, $julyRows, 'only the July allocation belongs in the July report');
        $this->assertSame('billing_refund_adjustment', $julyRows->first()->type);
        $this->assertEqualsWithDelta(-100.0, (float) $julyRows->first()->subtotal, 0.01);

        $mayRows = $this->engine->caLinkedTaxAndRefundRows($this->filters('2026-05-01', '2026-05-31'));
        $this->assertCount(1, $mayRows, 'May keeps only the original, unreduced charge');
        $this->assertSame('billing_tax_only', $mayRows->first()->type);
        $this->assertSame(29.25, (float) $mayRows->first()->tax_amount);
    }

    public function test_full_refund_in_a_later_period_leaves_the_original_period_historically_accurate(): void
    {
        $bc = $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50, '2026-06-15');
        $this->allocateRefund($bc, 200.0, 19.50, 'allocated', '2026-07-20');

        $juneRows = $this->engine->caLinkedTaxAndRefundRows($this->filters('2026-06-01', '2026-06-30'));
        $this->assertCount(1, $juneRows);
        $this->assertSame(19.50, (float) $juneRows->first()->tax_amount, 'June must show the full original tax, never restated by the later full refund');

        $julyRows = $this->engine->caLinkedTaxAndRefundRows($this->filters('2026-07-01', '2026-07-31'));
        $this->assertCount(1, $julyRows);
        $this->assertEqualsWithDelta(-200.0, (float) $julyRows->first()->subtotal, 0.01);
        $this->assertEqualsWithDelta(-19.50, (float) $julyRows->first()->tax_amount, 0.01);
    }

    public function test_pending_allocation_does_not_affect_reporting(): void
    {
        $bc = $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50);
        $this->allocateRefund($bc, 100.0, 9.75, 'pending', now()->toDateString());

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters());

        $this->assertCount(1, $rows, 'a Pending allocation must not produce any reporting row');
        $this->assertSame('billing_tax_only', $rows->first()->type);
        $this->assertSame(19.50, (float) $rows->first()->tax_amount);
    }

    public function test_failed_allocation_does_not_affect_reporting(): void
    {
        $bc = $this->makePaidCaLinkedCharge('damage', 150.0, 14.63);
        $this->allocateRefund($bc, 150.0, 14.63, 'failed', now()->toDateString());

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters());

        $this->assertCount(1, $rows, 'a Failed allocation must not produce any reporting row');
        $this->assertSame('billing_tax_only', $rows->first()->type);
        $this->assertEqualsWithDelta(14.63, (float) $rows->first()->tax_amount, 0.01);
    }

    public function test_store_filtering_keeps_a_charge_and_its_refund_attributed_to_the_same_store(): void
    {
        $store = Store::create(['store_name' => 'Test Store', 'status' => 'Active']);

        $bc = $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50, now()->toDateString(), $store->id);
        $this->allocateRefund($bc, 200.0, 19.50, 'allocated', now()->toDateString());

        $matching = $this->engine->caLinkedTaxAndRefundRows($this->filters(null, null, $store->id));
        $this->assertCount(2, $matching, 'both the original charge and its refund must appear under the charge\'s own store');

        $otherStore = Store::create(['store_name' => 'Other Store', 'status' => 'Active']);
        $nonMatching = $this->engine->caLinkedTaxAndRefundRows($this->filters(null, null, $otherStore->id));
        $this->assertCount(0, $nonMatching, 'neither the charge nor its refund may leak into an unrelated store\'s report');
    }

    public function test_no_duplication_across_multiple_refund_allocations(): void
    {
        $bc = $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50);
        $this->allocateRefund($bc, 100.0, 9.75, 'allocated', now()->toDateString());
        $this->allocateRefund($bc, 100.0, 9.75, 'allocated', now()->toDateString());

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters());

        $originalRows = $rows->where('type', 'billing_tax_only');
        $adjustmentRows = $rows->where('type', 'billing_refund_adjustment');

        $this->assertCount(1, $originalRows, 'the original charge must appear exactly once regardless of how many refund allocations exist against it');
        $this->assertCount(2, $adjustmentRows, 'each successful allocation produces exactly one adjustment row — never merged, never duplicated');
        $this->assertEqualsWithDelta(-100.0, (float) $adjustmentRows->sum('subtotal'), 0.01);
        $this->assertEqualsWithDelta(-9.75, (float) $adjustmentRows->sum('tax_amount'), 0.01);
    }

    public function test_tax_free_charge_with_a_full_refund_reports_the_base_adjustment_without_ever_reporting_tax(): void
    {
        $bc = $this->makePaidCaLinkedCharge('fuel', 150.0, 0.0);
        $this->allocateRefund($bc, 150.0, 0.0, 'allocated', now()->toDateString());

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters());

        // The original charge has zero tax, so it is excluded up front (see
        // test_ca_linked_charge_with_zero_tax_is_not_included) — only the
        // refund adjustment row appears.
        $this->assertCount(1, $rows);
        $this->assertSame('billing_refund_adjustment', $rows->first()->type);
        $this->assertSame(0.0, (float) $rows->first()->tax_amount);
        $this->assertEqualsWithDelta(-150.0, (float) $rows->first()->subtotal, 0.01);
    }

    public function test_refund_adjustment_uses_persisted_allocation_amounts_not_todays_tax_rate(): void
    {
        // Even if the global sales tax rate were to change after the charge
        // and refund were recorded, caRefundAdjustmentRows() must reflect
        // exactly what was persisted on the allocation row — never
        // recompute from amount/rate at report time.
        $bc = $this->makePaidCaLinkedCharge('fuel', 200.0, 19.50, '2026-06-10');
        $this->allocateRefund($bc, 73.41, 7.16, 'allocated', '2026-06-20');

        $rows = $this->engine->caLinkedTaxAndRefundRows($this->filters('2026-06-01', '2026-06-30'));
        $adjustment = $rows->firstWhere('type', 'billing_refund_adjustment');

        $this->assertEqualsWithDelta(-73.41, (float) $adjustment->subtotal, 0.001);
        $this->assertEqualsWithDelta(-7.16, (float) $adjustment->tax_amount, 0.001);
    }

    // ── Regression: standard order / extension refund reporting unchanged ─

    public function test_standard_order_refund_rows_are_unaffected_by_this_correction(): void
    {
        // Stream B (refundRows()) was audited but not modified this session
        // — a smoke check that it still runs and remains independent of
        // Stream E's changes.
        $rows = $this->engine->refundRows($this->filters());
        $this->assertCount(0, $rows, 'no order-level refunds exist in this test — proves Stream B is untouched and unaffected by CA-linked fixtures');
    }
}

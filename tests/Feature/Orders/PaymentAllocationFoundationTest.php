<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\RefundCalculationType;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCredit;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Models\Orders\OrderPaymentRefundAllocation;
use App\Services\AuthorizeNetService;
use App\Services\CustomerCreditService;
use App\Services\Orders\PaymentAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesOrderFixtureLines;
use Tests\TestCase;

/**
 * Phase 3B — Refund Allocation Foundation.
 *
 * WRITTEN BUT NOT EXECUTED in this environment: this sandbox has no MySQL
 * server (phpunit.xml's default DB connection is MySQL; RefreshDatabase
 * fails with "Connection refused" here regardless of what a Feature test
 * touches — the same documented, pre-existing sandbox limitation as every
 * prior Feature test in this codebase, including Phase 3A's
 * PaymentCorrectnessFoundationTest.php). Requires a real MySQL test
 * database to run. Do not treat this file as passing until it has
 * actually been executed against one.
 *
 * Covers all 21 scenarios listed in the Phase 3B mission's Testing
 * Requirements section — numbered in matching order below.
 */
class PaymentAllocationFoundationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesOrderFixtureLines;

    private User $admin;

    private User $employee;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-p3b-test@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Sam', 'last_name' => 'Clerk',
            'email' => 'sam-p3b-test@example.com', 'status' => 'Active',
        ]);

        $this->customer = Customer::factory()->create();

        $this->actingAs($this->admin);
    }

    private function makeOrder(float $grandTotal, ?float $subtotal = null, float $taxAmount = 0.0): Order
    {
        $order = Order::create([
            'order_date' => now()->format('Y-m-d'), 'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->full_name ?? 'Test Customer',
            'subtotal' => $subtotal ?? $grandTotal, 'tax_amount' => $taxAmount, 'grand_total' => $grandTotal,
        ]);

        $this->addFixtureLine($order, $subtotal ?? $grandTotal, $taxAmount);

        return $order->fresh();
    }


    private function makeSettledPayment(Order $order, float $amount, array $overrides = []): OrderPayment
    {
        return $order->payments()->create(array_merge([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => OrderPaymentStatus::Paid->value,
        ], $overrides));
    }

    private function refund(Order $order, array $overrides = [])
    {
        return $this->putJson(
            route('admin.order-management.orders.refund-payment', $order->unique_id),
            array_merge([
                'amount' => 100, 'payment_type' => 'Cash', 'reason' => 'billing_error',
                'processed_by' => $this->employee->id, 'employee_code' => $this->employee->employee_code,
                'idempotency_token' => (string) Str::uuid(),
            ], $overrides)
        );
    }

    // ── 1-2: single-payment refund creates one allocation, correctly targeted ──

    public function test_1_2_single_payment_refund_creates_one_correctly_targeted_allocation(): void
    {
        $order = $this->makeOrder(1000.0);
        $original = $this->makeSettledPayment($order, 1000.0);

        $this->refund($order, ['amount' => 100])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $this->assertCount(1, $refundRow->refundAllocations);
        $this->assertSame($original->id, $refundRow->refundAllocations->first()->original_order_payment_id);
    }

    // ── 3: second refund creates another allocation against the same original ──

    public function test_3_second_refund_creates_another_allocation_against_the_same_original(): void
    {
        $order = $this->makeOrder(1000.0);
        $original = $this->makeSettledPayment($order, 1000.0);

        $this->refund($order, ['amount' => 100])->assertOk();
        $this->refund($order, ['amount' => 100])->assertOk();

        $allocations = OrderPaymentRefundAllocation::where('original_order_payment_id', $original->id)->get();
        $this->assertCount(2, $allocations);
    }

    // ── 4: a refund never points to another refund row ──────────────

    public function test_4_a_refund_allocation_never_targets_another_refund_row(): void
    {
        $order = $this->makeOrder(1000.0);
        $this->makeSettledPayment($order, 1000.0);

        $this->refund($order, ['amount' => 100])->assertOk();
        $this->refund($order, ['amount' => 100])->assertOk();

        $refundIds = $order->payments()->refund()->pluck('id');
        $allocatedOriginalIds = OrderPaymentRefundAllocation::whereIn(
            'refund_order_payment_id',
            $order->payments()->refund()->pluck('id')
        )->pluck('original_order_payment_id');

        $this->assertEmpty($allocatedOriginalIds->intersect($refundIds));
    }

    // ── 5-6: parent pointer derivation ───────────────────────────────

    public function test_5_parent_pointer_is_derived_from_the_single_allocation(): void
    {
        $order = $this->makeOrder(1000.0);
        $original = $this->makeSettledPayment($order, 1000.0);

        $this->refund($order, ['amount' => 100])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $this->assertSame($original->id, $refundRow->parent_order_payment_id);
    }

    public function test_6_parent_pointer_is_null_once_a_second_allocation_exists(): void
    {
        // Today's controller flow only ever creates one allocation per
        // refund; this exercises the service directly to prove
        // syncParentPointer() degrades correctly once a future
        // multi-source refund creates a second allocation row.
        $order = $this->makeOrder(1000.0);
        $originalA = $this->makeSettledPayment($order, 400.0);
        $originalB = $this->makeSettledPayment($order, 600.0, ['payment_method' => OrderPaymentMethod::Card->value]);
        $refundRow = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'status' => OrderPaymentStatus::PartialRefund->value,
            'refund_amount' => 100.0, 'tax_refunded' => 0.0,
        ]);

        PaymentAllocationService::allocateSingleSource($refundRow, $originalA, 50.0, 50.0, 0.0);
        $this->assertSame($originalA->id, $refundRow->fresh()->parent_order_payment_id);

        PaymentAllocationService::allocateSingleSource($refundRow, $originalB, 50.0, 50.0, 0.0);
        $this->assertNull($refundRow->fresh()->parent_order_payment_id);
    }

    // ── 7-9: remaining refundable via allocation totals ──────────────

    public function test_7_remaining_refundable_uses_allocation_totals(): void
    {
        $order = $this->makeOrder(500.0);
        $original = $this->makeSettledPayment($order, 500.0);

        $this->refund($order, ['amount' => 200])->assertOk();

        $this->assertSame(300.0, PaymentAllocationService::remainingRefundable($original->fresh()));
    }

    public function test_8_pending_allocation_reserves_balance(): void
    {
        $order = $this->makeOrder(500.0);
        $original = $this->makeSettledPayment($order, 500.0);
        $refundRow = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'status' => OrderPaymentStatus::Pending->value,
        ]);

        PaymentAllocationService::allocateSingleSource(
            $refundRow, $original, 150.0, 150.0, 0.0,
            status: OrderPaymentRefundAllocationStatus::Pending,
        );

        $this->assertSame(350.0, PaymentAllocationService::remainingRefundable($original->fresh()));
    }

    public function test_9_failed_allocation_releases_balance(): void
    {
        $order = $this->makeOrder(500.0);
        $original = $this->makeSettledPayment($order, 500.0);
        $refundRow = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'status' => OrderPaymentStatus::Failed->value,
        ]);

        PaymentAllocationService::allocateSingleSource(
            $refundRow, $original, 150.0, 150.0, 0.0,
            status: OrderPaymentRefundAllocationStatus::Failed,
            failureReason: 'Gateway declined',
        );

        $this->assertSame(500.0, PaymentAllocationService::remainingRefundable($original->fresh()));
    }

    // ── 10-13: base/tax split and fee retention on the allocation row ──

    public function test_10_standard_refund_allocation_base_and_tax_split_is_correct(): void
    {
        $order = $this->makeOrder(1097.50, subtotal: 1000.0, taxAmount: 97.50);
        $original = $this->makeSettledPayment($order, 1097.50);

        $this->refund($order, ['amount' => 219.50])->assertOk();

        $allocation = $original->fresh()->receivedRefundAllocations->first();
        $this->assertNotNull($allocation);
        $this->assertEqualsWithDelta(219.50, (float) $allocation->allocated_amount, 0.01);
        $this->assertEqualsWithDelta(
            (float) $allocation->allocated_base_amount + (float) $allocation->allocated_tax_amount,
            (float) $allocation->allocated_amount,
            0.001
        );
    }

    public function test_11_sales_tax_only_allocation_stores_zero_base_and_full_tax(): void
    {
        $order = $this->makeOrder(1097.50, subtotal: 1000.0, taxAmount: 97.50);
        $this->makeSettledPayment($order, 1097.50);

        $this->refund($order, [
            'payment_type' => 'Cash', 'refund_calculation_type' => 'sales_tax_only',
        ])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $allocation = $refundRow->refundAllocations->first();

        $this->assertSame(0.0, (float) $allocation->allocated_base_amount);
        $this->assertEqualsWithDelta((float) $allocation->allocated_amount, (float) $allocation->allocated_tax_amount, 0.001);
    }

    public function test_12_card_processing_fee_retained_allocation_stores_the_fee(): void
    {
        Setting::updateOrCreate(
            ['setting_type' => 'Product Settings', 'setting_name' => 'credit_card_processing_fee'],
            ['setting_title' => 'Credit Card Processing Fee', 'value_type' => 'number', 'setting_value' => 3.00]
        );

        $order = $this->makeOrder(1000.0);
        $this->makeSettledPayment($order, 1000.0, [
            'payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-CC-FEE-ALLOC-1',
        ]);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'success']);
        });

        $this->refund($order, [
            'payment_type' => 'CreditCard', 'refund_calculation_type' => 'card_processing_fee_retained',
        ])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $allocation = $refundRow->refundAllocations->first();

        $this->assertNotNull($allocation->processing_fee_retained);
        $this->assertEqualsWithDelta((float) $refundRow->cc_fee_retained, (float) $allocation->processing_fee_retained, 0.001);
    }

    public function test_13_allocation_totals_equal_refund_row_totals(): void
    {
        $order = $this->makeOrder(1097.50, subtotal: 1000.0, taxAmount: 97.50);
        $this->makeSettledPayment($order, 1097.50);

        $this->refund($order, ['amount' => 219.50])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $allocation = $refundRow->refundAllocations->first();

        $this->assertEqualsWithDelta((float) $refundRow->refund_amount, (float) $allocation->allocated_amount, 0.001);
        $this->assertEqualsWithDelta((float) $refundRow->tax_refunded, (float) $allocation->allocated_tax_amount, 0.001);
    }

    // ── 14-16: historical backfill ────────────────────────────────────

    public function test_14_historical_unambiguous_refund_backfills_correctly(): void
    {
        $order = $this->makeOrder(1000.0);
        $original = $this->makeSettledPayment($order, 1000.0);

        // Simulates a legacy refund row recorded before Phase 3B existed —
        // created directly, bypassing the controller, with no allocation.
        $legacyRefund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'parent_order_payment_id' => $original->id,
            'status' => OrderPaymentStatus::PartialRefund->value,
            'refund_amount' => 150.0, 'tax_refunded' => 0.0,
            'gateway_refund_id' => 'LEGACY-REF-1',
        ]);

        $report = PaymentAllocationService::backfill();

        $this->assertSame(1, $report['newly_allocated']);
        $this->assertSame(0, $report['already_allocated']);
        $this->assertSame(0, $report['skipped_ambiguous']);
        $this->assertEmpty($report['errors']);

        $allocation = $legacyRefund->fresh()->refundAllocations->first();
        $this->assertNotNull($allocation);
        $this->assertSame($original->id, $allocation->original_order_payment_id);
        $this->assertEqualsWithDelta(150.0, (float) $allocation->allocated_amount, 0.001);
        $this->assertSame('LEGACY-REF-1', $allocation->gateway_transaction_id);
    }

    public function test_15_backfill_is_idempotent(): void
    {
        $order = $this->makeOrder(1000.0);
        $original = $this->makeSettledPayment($order, 1000.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'parent_order_payment_id' => $original->id,
            'status' => OrderPaymentStatus::PartialRefund->value,
            'refund_amount' => 150.0, 'tax_refunded' => 0.0,
        ]);

        $firstRun = PaymentAllocationService::backfill();
        $secondRun = PaymentAllocationService::backfill();

        // The audit-friendly shape this test exists to prove: a healthy
        // rerun shows newly_allocated drop to 0 while already_allocated
        // absorbs exactly what the first run allocated.
        $this->assertSame(1, $firstRun['newly_allocated']);
        $this->assertSame(0, $firstRun['already_allocated']);
        $this->assertSame(0, $secondRun['newly_allocated']);
        $this->assertSame(1, $secondRun['already_allocated']);
        $this->assertSame(1, OrderPaymentRefundAllocation::count());
    }

    public function test_16_ambiguous_historical_refund_is_skipped_and_reported(): void
    {
        $order = $this->makeOrder(1000.0);
        $this->makeSettledPayment($order, 600.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-A']);
        $this->makeSettledPayment($order, 400.0);

        // No parent_order_payment_id — genuinely ambiguous legacy data on a
        // multi-payment order (this is exactly the shape Phase 3A's fix
        // prevents going forward, but historical rows like this can exist).
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'status' => OrderPaymentStatus::PartialRefund->value,
            'refund_amount' => 100.0, 'tax_refunded' => 0.0,
        ]);

        $report = PaymentAllocationService::backfill();

        $this->assertSame(0, $report['newly_allocated']);
        $this->assertSame(1, $report['skipped_ambiguous']);
        $this->assertSame(0, OrderPaymentRefundAllocation::count());
    }

    // ── 17: legacy allocation warning does not throw ──────────────────

    public function test_17_attribution_state_does_not_throw_for_an_ambiguous_refund(): void
    {
        $order = $this->makeOrder(1000.0);
        $this->makeSettledPayment($order, 600.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-B']);
        $this->makeSettledPayment($order, 400.0);
        $ambiguousRefund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'status' => OrderPaymentStatus::PartialRefund->value,
            'refund_amount' => 100.0, 'tax_refunded' => 0.0,
        ]);

        $state = PaymentAllocationService::attributionState($ambiguousRefund);

        $this->assertSame(PaymentAllocationService::STATE_AMBIGUOUS, $state);
    }

    // ── 18-19: Store Credit linkage and balance correctness ──────────

    public function test_18_store_credit_redemption_links_to_order_payment_id(): void
    {
        CustomerCredit::create([
            'customer_id' => $this->customer->id, 'type' => 'grant', 'amount' => 500.0,
            'reason' => 'Test grant', 'unique_id' => (string) Str::uuid(),
        ]);

        $order = $this->makeOrder(300.0);

        $this->putJson(route('admin.order-management.orders.receive-payment', $order->unique_id), [
            'payment_type' => 'StoreCredit', 'responsible_person' => $this->employee->id,
        ])->assertOk();

        $payment = $order->payments()->first();
        $redemption = CustomerCredit::where('type', CustomerCreditService::TYPE_REDEMPTION)
            ->where('customer_id', $this->customer->id)
            ->first();

        $this->assertNotNull($redemption);
        $this->assertSame($payment->id, $redemption->order_payment_id);
    }

    public function test_19_store_credit_balance_is_still_decremented_exactly_once(): void
    {
        CustomerCredit::create([
            'customer_id' => $this->customer->id, 'type' => 'grant', 'amount' => 500.0,
            'reason' => 'Test grant', 'unique_id' => (string) Str::uuid(),
        ]);

        $order = $this->makeOrder(300.0);

        $this->putJson(route('admin.order-management.orders.receive-payment', $order->unique_id), [
            'payment_type' => 'StoreCredit', 'responsible_person' => $this->employee->id,
        ])->assertOk();

        $this->assertSame(200.0, CustomerCreditService::remainingBalance($this->customer->id));
    }

    // ── 20-21: existing workflows remain green ────────────────────────

    public function test_20_existing_single_payment_standard_refund_still_works(): void
    {
        $order = $this->makeOrder(1097.50, subtotal: 1000.0, taxAmount: 97.50);
        $this->makeSettledPayment($order, 1097.50);

        $this->refund($order, ['amount' => 200])->assertOk()->assertJson(['success' => true]);
    }

    public function test_21_phase_3a_remaining_refundable_formula_is_unchanged_for_never_refunded_payments(): void
    {
        // Phase 3A's PaymentCorrectnessFoundationTest.php is the primary
        // regression suite for Phase 3A behavior and must be re-run
        // alongside this file, not duplicated here. This test only proves
        // the specific formula Phase 3B modified — remainingRefundableForPayment()
        // now delegating to PaymentAllocationService — still returns the
        // exact same figure it did in Phase 3A for a payment with no
        // refund/allocation activity at all.
        $order = $this->makeOrder(500.0);
        $original = $this->makeSettledPayment($order, 500.0);

        $this->assertSame(500.0, $order->remainingRefundableForPayment($original));
    }

    // ── Payment Architecture Finalization — Sales Tax Refund Write-Path
    //    Correction: resolveRequestedTotal()'s SalesTaxOnly branch was a
    //    raw, allocation-unaware sum('tax_refunded') — the same defect
    //    already fixed for the refund modal's display-only figure
    //    (RefundModalTaxDisplayTest.php), except THIS branch actually
    //    drives eligibility and the previewed/submitted/executed refund
    //    amount. Now delegates to totalSuccessfulRefundedTax(), the exact
    //    same canonical figure the display uses, so the two can never
    //    disagree. Every fixture below deliberately sets the legacy
    //    tax_refunded column to a value DIFFERENT from the real allocation
    //    data where both are present, so a passing assertion proves the
    //    allocation-aware branch — not a coincidental fallback — drove the
    //    result. ──────────────────────────────────────────────────────

    /**
     * Creates one refund payment row with one refund allocation against
     * $original, for the given tax amount/status. Mirrors the exact
     * fixture shape used in RefundModalTaxDisplayTest.php and
     * BillingRevenueAttributionServiceTest.php.
     */
    private function makeTaxOnlyAllocation(
        Order $order,
        OrderPayment $original,
        float $tax,
        OrderPaymentRefundAllocationStatus $status,
        float $legacyTaxRefunded = 0.0,
        ?\Carbon\Carbon $when = null
    ): OrderPayment {
        $when ??= now();

        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => $when, 'refunded_at' => $when, 'amount' => 0,
            'refund_amount' => 0, 'tax_refunded' => $legacyTaxRefunded,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $original->id,
            'allocated_amount' => $tax, 'allocated_base_amount' => 0, 'allocated_tax_amount' => $tax,
            'processing_fee_retained' => 0, 'status' => $status->value,
        ]);

        return $refund;
    }

    public function test_stwp_1_no_prior_refund_returns_the_full_original_tax(): void
    {
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $this->makeSettledPayment($order, 1100.0);

        [$total, $error] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertSame(100.0, $total);
        $this->assertNull($error);
    }

    public function test_stwp_2_one_completed_refund_reduces_the_remaining_tax(): void
    {
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $payment = $this->makeSettledPayment($order, 1100.0);
        $this->makeTaxOnlyAllocation($order, $payment, 30.0, OrderPaymentRefundAllocationStatus::Allocated);

        [$total, $error] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertSame(70.0, $total);
        $this->assertNull($error);
    }

    public function test_stwp_3_multiple_completed_refunds_sum_across_separate_events(): void
    {
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $payment = $this->makeSettledPayment($order, 1100.0);
        $this->makeTaxOnlyAllocation($order, $payment, 20.0, OrderPaymentRefundAllocationStatus::Allocated);
        $this->makeTaxOnlyAllocation($order, $payment, 15.0, OrderPaymentRefundAllocationStatus::Allocated);

        [$total, ] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertSame(65.0, $total, '100 - (20 + 15)');
    }

    public function test_stwp_4_multi_source_refund_with_all_allocations_completed(): void
    {
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $payment1 = $this->makeSettledPayment($order, 600.0, ['payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now()->subMinute()]);
        $payment2 = $this->makeSettledPayment($order, 500.0, ['payment_method' => OrderPaymentMethod::Card->value]);

        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0, 'status' => OrderPaymentStatus::PartialRefund->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment1->id,
            'allocated_amount' => 10.0, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 10.0,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment2->id,
            'allocated_amount' => 15.0, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 15.0,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        [$total, ] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertSame(75.0, $total, '100 - (10 + 15) across two sources in one refund event');
    }

    public function test_stwp_5_multi_source_refund_with_one_completed_and_one_failed_allocation(): void
    {
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $payment = $this->makeSettledPayment($order, 1100.0);

        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0, 'status' => OrderPaymentStatus::PartialRefund->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 20.0, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 20.0,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 999.0, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 999.0,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Failed->value,
        ]);

        [$total, ] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertSame(80.0, $total, 'only the 20 Allocated row counts, not the 999 Failed attempt');
    }

    public function test_stwp_6_pending_allocation_does_not_reduce_refundable_tax(): void
    {
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $payment = $this->makeSettledPayment($order, 1100.0);
        $this->makeTaxOnlyAllocation($order, $payment, 40.0, OrderPaymentRefundAllocationStatus::Pending);

        [$total, $error] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertSame(100.0, $total, 'a still-pending attempt has not actually moved any money yet');
        $this->assertNull($error);
    }

    public function test_stwp_7_failed_allocation_does_not_reduce_refundable_tax(): void
    {
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $payment = $this->makeSettledPayment($order, 1100.0);
        $this->makeTaxOnlyAllocation($order, $payment, 40.0, OrderPaymentRefundAllocationStatus::Failed);

        [$total, ] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertSame(100.0, $total);
    }

    public function test_stwp_8_backfilled_historical_allocation_reduces_refundable_tax_correctly(): void
    {
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $payment = $this->makeSettledPayment($order, 1100.0, ['payment_datetime' => now()->subMonths(6)]);
        // payments:backfill-allocations reconstructs a real Allocated row —
        // no different in shape from one created live by
        // RefundPaymentController. Legacy tax_refunded left stale (999) to
        // prove the allocation branch, not the fallback, is used.
        $this->makeTaxOnlyAllocation($order, $payment, 25.0, OrderPaymentRefundAllocationStatus::Allocated, legacyTaxRefunded: 999.0, when: now()->subMonths(6));

        [$total, ] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertSame(75.0, $total);
    }

    public function test_stwp_9_legacy_refund_row_with_zero_allocations_uses_the_raw_fallback(): void
    {
        // Genuinely never backfilled — zero allocation rows exist, so the
        // documented legacy fallback (not the allocation branch) is the
        // correct, intentional path.
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $this->makeSettledPayment($order, 1100.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0, 'refund_amount' => 0, 'tax_refunded' => 45.0,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        [$total, ] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertSame(55.0, $total);
    }

    public function test_stwp_10_allocations_take_precedence_over_a_stale_legacy_tax_refunded_value(): void
    {
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $payment = $this->makeSettledPayment($order, 1100.0);
        // The refund row's own legacy tax_refunded (999) would imply
        // everything was refunded — the allocation (30) is what's real.
        $this->makeTaxOnlyAllocation($order, $payment, 30.0, OrderPaymentRefundAllocationStatus::Allocated, legacyTaxRefunded: 999.0);

        [$total, $error] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertSame(70.0, $total);
        $this->assertNull($error);
    }

    public function test_stwp_11_remaining_refundable_tax_never_goes_negative(): void
    {
        // A data anomaly (over-allocation) must still clamp to zero, not
        // report a negative "remaining" figure.
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $payment = $this->makeSettledPayment($order, 1100.0);
        $this->makeTaxOnlyAllocation($order, $payment, 150.0, OrderPaymentRefundAllocationStatus::Allocated);

        [$total, $error] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertNull($total);
        $this->assertSame('No refundable sales tax remains for this order.', $error);
    }

    public function test_stwp_12_preview_and_execution_resolve_to_the_identical_amount(): void
    {
        // RefundPaymentPreviewController and RefundPaymentController call
        // resolveRequestedTotal() identically (see this mission's data-flow
        // trace) — against the same order state, the two calls must never
        // diverge, since there is no second formula anywhere in either
        // controller.
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $payment = $this->makeSettledPayment($order, 1100.0);
        $this->makeTaxOnlyAllocation($order, $payment, 30.0, OrderPaymentRefundAllocationStatus::Allocated);

        $previewResult = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);
        $executionResult = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertSame($previewResult, $executionResult);
        $this->assertSame(70.0, $previewResult[0]);
    }

    public function test_stwp_13_eligibility_becomes_false_once_all_tax_has_been_successfully_refunded(): void
    {
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $payment = $this->makeSettledPayment($order, 1100.0);
        $this->makeTaxOnlyAllocation($order, $payment, 100.0, OrderPaymentRefundAllocationStatus::Allocated);

        [$total, $error] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertNull($total);
        $this->assertSame('No refundable sales tax remains for this order.', $error);
    }

    public function test_stwp_14_a_failed_or_pending_attempt_alone_does_not_make_the_order_ineligible(): void
    {
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $payment = $this->makeSettledPayment($order, 1100.0);
        // If this Failed row (for the FULL tax amount) were counted, the
        // order would incorrectly read as fully refunded/ineligible.
        $this->makeTaxOnlyAllocation($order, $payment, 100.0, OrderPaymentRefundAllocationStatus::Failed);

        [$total, $error] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::SalesTaxOnly, 0.0);

        $this->assertSame(100.0, $total);
        $this->assertNull($error);
    }

    public function test_stwp_15_card_fee_retained_and_standard_calc_types_are_unaffected(): void
    {
        $order = $this->makeOrder(1100.0, subtotal: 1000.0, taxAmount: 100.0);
        $this->makeSettledPayment($order, 1100.0);

        [$ccTotal, $ccError] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::CardProcessingFeeRetained, 0.0);
        $this->assertNull($ccTotal);
        $this->assertNull($ccError);

        [$stdTotal, $stdError] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::Standard, 50.0);
        $this->assertSame(50.0, $stdTotal);
        $this->assertNull($stdError);

        [$zeroTotal, $zeroError] = PaymentAllocationService::resolveRequestedTotal($order, RefundCalculationType::Standard, 0.0);
        $this->assertNull($zeroTotal);
        $this->assertSame('Refund amount must be greater than zero.', $zeroError);
    }
}

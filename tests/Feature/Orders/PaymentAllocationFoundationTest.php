<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\OrderPaymentStatus;
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
        return Order::create([
            'order_date' => now()->format('Y-m-d'), 'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->full_name ?? 'Test Customer',
            'subtotal' => $subtotal ?? $grandTotal, 'tax_amount' => $taxAmount, 'grand_total' => $grandTotal,
        ]);
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
}

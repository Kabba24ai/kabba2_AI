<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\RefundCalculationType;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Services\Orders\PaymentAllocationService;
use App\Services\ReceiptService;
use App\Services\Reports\PaymentReconciliationLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesOrderFixtureLines;
use Tests\TestCase;

/**
 * Phase 3D — Refund Project Completion and Release Hardening.
 *
 * WRITTEN BUT NOT EXECUTED in this environment: this sandbox has no
 * reachable MySQL server — the same standing, documented limitation as
 * every other Feature test in this codebase (see MultiSourceRefundTest.php's
 * docblock). Requires a real MySQL test database to run.
 *
 * Covers the genuinely new pieces built in this phase: the two order-wide
 * aggregates (totalFeesRetained/totalRequestedRefund), the
 * payments:audit command, the server-authoritative confirmation preview
 * endpoint, ReceiptService::refundDetails(), the Reconciliation Ledger's
 * Stream A/B fixes, and the Customer factory fix that unblocks
 * Customer::factory() across this whole test suite.
 */
class RefundReportingAndAuditTest extends TestCase
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
            'email' => 'gary-p3d-test@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Sam', 'last_name' => 'Clerk',
            'email' => 'sam-p3d-test@example.com', 'status' => 'Active',
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

        // Customer::orders() is morphMany('created_by'), NOT a customer_id
        // relation — Customer::paid_sales and its siblings traverse it, so
        // fixture orders must carry the morph link a real customer-created
        // order would have or those accessors see an empty set. Assigned
        // directly (not via Order::create) because created_by_type/
        // created_by_id are not in Order::$fillable and mass assignment
        // silently drops them.
        $order->created_by_type = Customer::class;
        $order->created_by_id = $this->customer->id;
        $order->save();

        return $order;
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

    // ── Customer factory fix ────────────────────────────────────────────

    public function test_customer_factory_now_works(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'Factory']);

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'first_name' => 'Factory']);
    }

    // ── Customer::paid_sales refund-consumer fix ────────────────────────

    /**
     * Final Phase — Refund Consumer Cleanup: getPaidSalesAttribute() used
     * to join each order to only its single highest-id order_payments row
     * (no status filter on the subquery, so it could even resolve to the
     * refund row itself) and sum the WHOLE order's grand_total whenever
     * that one row's status was Paid/Refunded/Partial Refund — a refunded
     * order counted its full original price as "paid." Now sums
     * Order::net_paid, which a refund correctly reduces.
     */
    public function test_customer_paid_sales_reflects_a_refund_not_the_full_order_total(): void
    {
        $order = $this->makeOrder(1000.0);
        $original = $this->makeSettledPayment($order, 1000.0);

        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(), 'refunded_at' => now(),
            'status' => OrderPaymentStatus::PartialRefund->value, 'refund_amount' => 400, 'tax_refunded' => 0,
        ]);
        PaymentAllocationService::allocateSingleSource($refund, $original, 400.0, 400.0, 0.0, null, null, OrderPaymentRefundAllocationStatus::Allocated);

        // Full order grand_total is $1000; $400 was refunded — paid_sales
        // must reflect the net $600 actually still with the business, not
        // the order's full $1000 face value.
        $this->assertEqualsWithDelta(600.0, $this->customer->paid_sales, 0.001);
    }

    // ── totalFeesRetained() / totalRequestedRefund() ────────────────────

    public function test_total_fees_retained_sums_only_allocated_fee_retained_allocations(): void
    {
        $order = $this->makeOrder(1000.0);
        $original = $this->makeSettledPayment($order, 1000.0, [
            'payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-FEE',
        ]);

        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(), 'refunded_at' => now(),
            'status' => OrderPaymentStatus::Refund->value, 'refund_amount' => 470, 'tax_refunded' => 0,
            'refund_calculation_type' => RefundCalculationType::CardProcessingFeeRetained->value,
        ]);

        PaymentAllocationService::allocateSingleSource(
            $refund, $original, 500.0, 470.0, 0.0, 30.0, null, OrderPaymentRefundAllocationStatus::Allocated
        );

        $this->assertEqualsWithDelta(30.0, PaymentAllocationService::totalFeesRetained($order), 0.001);
    }

    public function test_total_requested_refund_includes_failed_but_excludes_superseded(): void
    {
        $order = $this->makeOrder(1000.0);
        $a = $this->makeSettledPayment($order, 600.0);
        $b = $this->makeSettledPayment($order, 400.0);

        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(), 'refunded_at' => now(),
            'status' => OrderPaymentStatus::PartialRefund->value, 'refund_amount' => 300, 'tax_refunded' => 0,
            'idempotency_token' => (string) Str::uuid(),
        ]);

        PaymentAllocationService::allocateSingleSource($refund, $a, 300.0, 300.0, 0.0, null, null, OrderPaymentRefundAllocationStatus::Allocated);
        PaymentAllocationService::allocateSingleSource($refund, $b, 200.0, 200.0, 0.0, null, null, OrderPaymentRefundAllocationStatus::Failed, 'Declined');

        // Superseding the failed $b allocation should drop it out of the
        // "requested" total — that money's intent has moved to whatever
        // replaces it, not still outstanding against $b.
        PaymentAllocationService::supersedeAbandonedFailures($refund, [$a->id]);

        $this->assertEqualsWithDelta(300.0, PaymentAllocationService::totalRequestedRefund($order), 0.001);
    }

    // ── payments:audit ───────────────────────────────────────────────────

    public function test_audit_command_reports_clean_on_a_healthy_allocation(): void
    {
        $order = $this->makeOrder(500.0);
        $original = $this->makeSettledPayment($order, 500.0);
        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(), 'refunded_at' => now(),
            'status' => OrderPaymentStatus::Refund->value, 'refund_amount' => 500, 'tax_refunded' => 0,
        ]);
        PaymentAllocationService::allocateSingleSource($refund, $original, 500.0, 500.0, 0.0, null, null, OrderPaymentRefundAllocationStatus::Allocated);

        $exitCode = Artisan::call('payments:audit');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('No integrity failures found.', Artisan::output());
    }

    public function test_audit_command_detects_a_refund_exceeding_its_original_payment(): void
    {
        $order = $this->makeOrder(500.0);
        $original = $this->makeSettledPayment($order, 500.0);
        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(), 'refunded_at' => now(),
            'status' => OrderPaymentStatus::Refund->value, 'refund_amount' => 500, 'tax_refunded' => 0,
        ]);

        // Deliberately corrupt: allocate MORE than the original payment
        // ever collected — allocateSingleSource() itself doesn't forbid
        // this (that guard lives in validateAllocationSet(), one layer up,
        // which this test bypasses on purpose to exercise the audit).
        \App\Models\Orders\OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $original->id,
            'allocated_amount' => 999, 'allocated_base_amount' => 999, 'allocated_tax_amount' => 0,
            'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        $exitCode = Artisan::call('payments:audit');

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Integrity failures found.', Artisan::output());
    }

    public function test_audit_detects_a_refund_marked_completed_with_a_failed_allocation(): void
    {
        $order = $this->makeOrder(500.0);
        $a = $this->makeSettledPayment($order, 300.0);
        $b = $this->makeSettledPayment($order, 200.0);

        // Deliberately corrupt: refund_operation_status hand-set to
        // Completed while a Failed allocation still exists under it —
        // syncRefundOperationOutcome() itself would never produce this
        // combination; this simulates a hand-edited/bypassed row.
        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(), 'refunded_at' => now(),
            'status' => OrderPaymentStatus::PartialRefund->value, 'refund_amount' => 300, 'tax_refunded' => 0,
            'refund_operation_status' => \App\Enums\Orders\RefundOperationStatus::Completed->value,
        ]);
        PaymentAllocationService::allocateSingleSource($refund, $a, 300.0, 300.0, 0.0, null, null, OrderPaymentRefundAllocationStatus::Allocated);
        PaymentAllocationService::allocateSingleSource($refund, $b, 100.0, 100.0, 0.0, null, null, OrderPaymentRefundAllocationStatus::Failed, 'Declined');

        $exitCode = Artisan::call('payments:audit', ['--detail' => true]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Operation Status Disagreements', Artisan::output());
    }

    public function test_audit_detects_fee_retained_exceeding_lifetime_max(): void
    {
        $order = $this->makeOrder(1000.0);
        $original = $this->makeSettledPayment($order, 1000.0, [
            'payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-CAP',
        ]);
        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(), 'refunded_at' => now(),
            'status' => OrderPaymentStatus::Refund->value, 'refund_amount' => 900, 'tax_refunded' => 0,
            'refund_calculation_type' => RefundCalculationType::CardProcessingFeeRetained->value,
        ]);

        // A 3% cap on a $1000 card payment is $30 — deliberately retain
        // $200, far beyond any configured percentage, to exercise the check
        // regardless of what's actually configured in this environment.
        PaymentAllocationService::allocateSingleSource($refund, $original, 1000.0, 800.0, 0.0, 200.0, null, OrderPaymentRefundAllocationStatus::Allocated);

        \App\Models\Configurations\Setting::updateOrCreate(
            ['setting_type' => 'Product Settings', 'setting_name' => 'credit_card_processing_fee'],
            ['setting_title' => 'Credit Card Processing Fee', 'value_type' => 'number', 'setting_value' => 3.00]
        );

        $exitCode = Artisan::call('payments:audit');

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Integrity failures found.', Artisan::output());
    }

    // ── Void history consolidation ──────────────────────────────────────

    public function test_void_history_description_comes_from_the_presenter(): void
    {
        $order = $this->makeOrder(500.0);
        $payment = $this->makeSettledPayment($order, 500.0, [
            'payment_method' => OrderPaymentMethod::Card->value,
            'transaction_id' => 'TXN-VOID',
            'processed_by_name' => 'Sam Clerk',
            'processed_reason_label' => 'Customer Request',
        ]);

        $description = \App\Services\PaymentDescriptionPresenter::voidHistoryDescription($payment, 'Gary Admin');

        $this->assertSame(
            'Payment of $500.00 voided by Gary Admin. Processed by Sam Clerk (Employee ID verified). Reason: Customer Request.',
            $description
        );
    }

    // ── Server-authoritative confirmation preview ───────────────────────

    public function test_preview_endpoint_returns_authoritative_values_without_writing_anything(): void
    {
        $order = $this->makeOrder(1000.0);
        $original = $this->makeSettledPayment($order, 1000.0);

        $response = $this->postJson(
            route('admin.order-management.orders.refund-payment.preview', $order->unique_id),
            ['amount' => 400, 'payment_type' => 'Cash']
        );

        $response->assertOk();
        // Numeric-tolerant on purpose: PHP's shortest-form JSON encoding
        // (serialize_precision=-1) emits a whole-dollar float as `1000`,
        // which decodes as an int — a strict assertJsonPath(..., 1000.0)
        // can therefore never pass for whole-dollar amounts.
        $this->assertEqualsWithDelta(1000.0, $response->json('rows.0.original_amount'), 0.001);
        $this->assertEqualsWithDelta(1000.0, $response->json('rows.0.remaining_before'), 0.001);
        $this->assertEqualsWithDelta(400.0, $response->json('rows.0.refund_now'), 0.001);
        $this->assertEqualsWithDelta(600.0, $response->json('rows.0.remaining_after'), 0.001);

        // No allocation row and no refund row were created by the preview.
        $this->assertSame(0, $order->payments()->refund()->count());
    }

    public function test_preview_endpoint_rejects_an_amount_exceeding_remaining_balance(): void
    {
        $order = $this->makeOrder(200.0);
        $this->makeSettledPayment($order, 200.0);

        $response = $this->postJson(
            route('admin.order-management.orders.refund-payment.preview', $order->unique_id),
            ['amount' => 500, 'payment_type' => 'Cash']
        );

        $response->assertStatus(422);
    }

    // ── ReceiptService::refundDetails() ──────────────────────────────────

    public function test_receipt_refund_details_excludes_failed_allocations(): void
    {
        $order = $this->makeOrder(1000.0);
        $a = $this->makeSettledPayment($order, 600.0);
        $b = $this->makeSettledPayment($order, 400.0);

        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(), 'refunded_at' => now(),
            'status' => OrderPaymentStatus::PartialRefund->value, 'refund_amount' => 300, 'tax_refunded' => 0,
        ]);
        PaymentAllocationService::allocateSingleSource($refund, $a, 200.0, 200.0, 0.0, null, null, OrderPaymentRefundAllocationStatus::Allocated);
        PaymentAllocationService::allocateSingleSource($refund, $b, 100.0, 100.0, 0.0, null, null, OrderPaymentRefundAllocationStatus::Failed, 'Declined');

        $lines = ReceiptService::refundDetails($order);

        $this->assertCount(1, $lines);
        $this->assertEqualsWithDelta(200.0, $lines[0]['amount'], 0.001);
    }

    // ── PaymentReconciliationLedger Stream A/B ──────────────────────────

    public function test_ledger_stream_a_attributes_each_payment_method_its_own_amount(): void
    {
        $order = $this->makeOrder(1000.0, 1000.0, 0.0);
        // The ledger's order stream admits only orders that OWN
        // order_products rows — SalesReportingService::baseQuery() starts
        // FROM order_products joined to orders, so a bare order with
        // payments alone can never qualify (same admission rule every real
        // order satisfies; mirrors SalesTaxSplitPaymentReportingTest's
        // fixture shape).
        $product = \App\Models\ProductManagement\Product::create([
            'product_name' => 'Ledger Stream A Product',
            'slug'         => 'ledger-stream-a-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        \App\Models\Orders\OrderProduct::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => 'Ledger Stream A Product',
            'price'        => 1000,
            'quantity'     => 1,
            'sub_total'    => 1000,
            'tax'          => 0,
            'total'        => 1000,
            'product_data' => ['product_type' => 'Rental'],
        ]);
        $this->makeSettledPayment($order, 600.0, ['payment_method' => OrderPaymentMethod::Card->value]);
        $this->makeSettledPayment($order, 400.0, ['payment_method' => OrderPaymentMethod::Cash->value]);

        $ledger = app(PaymentReconciliationLedger::class);
        $rows = $ledger->rows([
            'date_range' => 'custom',
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'payment_status' => 'paid',
        ]);

        $orderRows = $rows->where('stream', 'order');

        $this->assertCount(2, $orderRows);
        $this->assertEqualsWithDelta(1000.0, $orderRows->sum('grand_total'), 0.001);
        $this->assertEqualsWithDelta(600.0, $orderRows->firstWhere('payment_method_key', 'Card')?->grand_total, 0.001);
        $this->assertEqualsWithDelta(400.0, $orderRows->firstWhere('payment_method_key', 'Cash')?->grand_total, 0.001);
    }

    public function test_ledger_stream_b_splits_a_multi_source_refund_by_original_payment_method(): void
    {
        $order = $this->makeOrder(1000.0, 1000.0, 0.0);
        $a = $this->makeSettledPayment($order, 600.0, ['payment_method' => OrderPaymentMethod::Card->value]);
        $b = $this->makeSettledPayment($order, 400.0, ['payment_method' => OrderPaymentMethod::Cash->value]);

        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(), 'refunded_at' => now(),
            'status' => OrderPaymentStatus::PartialRefund->value, 'refund_amount' => 300, 'tax_refunded' => 0,
        ]);
        PaymentAllocationService::allocateSingleSource($refund, $a, 200.0, 200.0, 0.0, null, null, OrderPaymentRefundAllocationStatus::Allocated);
        PaymentAllocationService::allocateSingleSource($refund, $b, 100.0, 100.0, 0.0, null, null, OrderPaymentRefundAllocationStatus::Allocated);

        $ledger = app(PaymentReconciliationLedger::class);
        $rows = $ledger->rows([
            'date_range' => 'custom',
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'payment_status' => 'paid',
        ]);

        $refundRows = $rows->where('stream', 'refund');

        $this->assertCount(2, $refundRows);
        $this->assertEqualsWithDelta(-300.0, $refundRows->sum('grand_total'), 0.001);
        $this->assertEqualsWithDelta(-200.0, $refundRows->firstWhere('payment_method_key', 'Card')?->grand_total, 0.001);
        $this->assertEqualsWithDelta(-100.0, $refundRows->firstWhere('payment_method_key', 'Cash')?->grand_total, 0.001);
    }
}

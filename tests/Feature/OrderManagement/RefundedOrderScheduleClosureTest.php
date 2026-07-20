<?php

namespace Tests\Feature\OrderManagement;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderHistory;
use App\Models\Orders\OrderPayment;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Services\AuthorizeNetService;
use App\Services\Orders\OrderPaymentSummary;
use App\Services\Orders\RefundedOrderScheduleCloser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Refunded/Voided Orders — automatic Schedule/Dispatch closure.
 *
 * Business rule: an order fully refunded or voided BEFORE delivery must stop
 * being treated as upcoming work (its delivery schedule closes exactly like
 * the manual "Close as Completed" action); an order refunded AFTER delivery
 * must stay operationally visible — the refund is financial activity only
 * and the equipment may still be in the field.
 *
 * The end-to-end tests run through the REAL refund endpoint (cash refunds
 * skip the gateway) and the REAL void endpoint (gateway mocked the same way
 * RefundVoidProcessedByTest does); the state-matrix tests call the closer
 * service directly with production-shaped payment/allocation fixtures.
 */
class RefundedOrderScheduleClosureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Terminal', 'last_name' => 'Admin',
            'email' => 'closure-admin@test.local', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Jane', 'last_name' => 'Counter',
            'email' => 'closure-employee@test.local', 'status' => 'Active',
        ]);

        $this->product = Product::create([
            'product_name' => 'Closure Test Excavator',
            'slug'         => 'closure-test-excavator-' . uniqid(),
            'product_type' => 'Rental',
        ]);

        $this->actingAs($this->admin);
    }

    // ── Fixtures ─────────────────────────────────────────────────────────

    private function makeOrder(float $grandTotal = 100, array $overrides = []): Order
    {
        static $n = 9600;
        $n++;

        return Order::create(array_merge([
            'order_number'  => (string) $n,
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Closure Customer',
            'grand_total'   => $grandTotal,
        ], $overrides));
    }

    /** Pending, undelivered rental schedule row — the shape Schedule/Dispatch list. */
    private function makeScheduleRow(Order $order, array $overrides = []): OrderProduct
    {
        return OrderProduct::create(array_merge([
            'order_id'                => $order->id,
            'product_id'              => $this->product->id,
            'product_name'            => $this->product->product_name,
            'price'                   => 100,
            'quantity'                => 1,
            'total'                   => 100,
            'delivery_date'           => now()->format('Y-m-d'), // today: inside every Dispatch range mode
            'delivery_time'           => '09:00',
            'delivery_transport_mode' => 'Truck',
            'pickup_transport_mode'   => 'Truck',
            'delivery_status'         => 'Pending',
            'pickup_status'           => 'Pending',
            'product_data'            => [
                'product_type'                => 'Rental',
                'product_variant'             => 'daily',
                'product_rental_items_prices' => [],
                'product_option_items'        => [],
            ],
        ], $overrides));
    }

    private function payCash(Order $order, float $amount): OrderPayment
    {
        return $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => $amount,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);
    }

    /**
     * Production-shaped refund fixture: refund row + Phase 3B allocation —
     * exactly what RefundPaymentController leaves behind on success.
     */
    private function makeRefundRow(
        Order $order,
        OrderPayment $original,
        float $amount,
        string $rowStatus = 'Refunded',
        string $allocationStatus = 'allocated',
        float $feeRetained = 0.0,
    ): OrderPayment {
        $refund = $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 0,
            'status'           => $rowStatus,
            'refund_amount'    => $amount,
            'refunded_at'      => now(),
        ]);

        // Fee-retained shape mirrors RefundPaymentController: allocated_amount
        // records GROSS; the net actually returned = gross − fee.
        $refund->refundAllocations()->create([
            'original_order_payment_id' => $original->id,
            'allocated_amount'          => $amount,
            'allocated_base_amount'     => $amount,
            'allocated_tax_amount'      => 0,
            'processing_fee_retained'   => $feeRetained > 0 ? $feeRetained : null,
            'status'                    => OrderPaymentRefundAllocationStatus::from($allocationStatus)->value,
        ]);

        return $refund;
    }

    private function refundViaEndpoint(Order $order, float $amount): \Illuminate\Testing\TestResponse
    {
        return $this->putJson(
            route('admin.order-management.orders.refund-payment', $order->unique_id),
            [
                'amount'        => $amount,
                'payment_type'  => 'Cash',
                'reason'        => 'billing_error',
                'processed_by'  => $this->employee->id,
                'employee_code' => $this->employee->employee_code,
            ]
        );
    }

    private function autoCloseHistory(Order $order)
    {
        return OrderHistory::where('order_id', $order->id)
            ->where('action', 'schedule_auto_closed')
            ->get();
    }

    private function scheduleHtml(array $params = []): string
    {
        return $this->get(
            route('admin.order-management.schedules.index', $params),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()->json('html');
    }

    private function dispatchHtml(array $params = []): string
    {
        return $this->get(
            route('admin.order-management.dispatch.index', $params),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()->json('html');
    }

    // ── 1. Full refund before delivery (end-to-end through the modal path) ──

    public function test_full_refund_before_delivery_closes_the_schedule(): void
    {
        $order = $this->makeOrder(100);
        $row = $this->makeScheduleRow($order);
        $this->payCash($order, 100);

        $this->refundViaEndpoint($order, 100)->assertOk()->assertJson(['success' => true]);

        $row->refresh();
        // Identical writes to the manual "Close as Completed" action
        $this->assertSame('Close as Completed', $row->delivery_status);
        $this->assertSame('Completed', $row->pickup_status);
        $this->assertTrue((bool) $row->is_delivered);
        $this->assertTrue((bool) $row->is_returned);

        // System-attributed audit entry, linked to the refund record —
        // never presented as a manual employee selection
        $history = $this->autoCloseHistory($order);
        $this->assertCount(1, $history);
        $entry = $history->first();
        $this->assertSame('System', $entry->action_by->value);
        $this->assertNull($entry->user_id);
        $this->assertStringContainsString('automatically closed as completed', $entry->description);
        $this->assertStringContainsString('fully refunded before delivery', $entry->description);
        $refundRow = $order->payments()->where('status', OrderPaymentStatus::Refund)->firstOrFail();
        $this->assertEquals($refundRow->id, $entry->order_payment_id);
        $this->assertEquals($this->admin->id, json_decode($entry->extras)->initiated_by_user_id);

        // Refund financials themselves are untouched by the closure
        $this->assertEquals(100, (float) $refundRow->refund_amount);
    }

    public function test_closed_order_disappears_from_active_schedule_and_dispatch(): void
    {
        $order = $this->makeOrder(100);
        $this->makeScheduleRow($order);
        $this->payCash($order, 100);

        // Visible as actionable work before the refund
        $this->assertStringContainsString($order->order_number, $this->scheduleHtml(['schedule_type' => ['Delivery']]));
        $this->assertStringContainsString($order->order_number, $this->dispatchHtml());

        $this->refundViaEndpoint($order, 100)->assertOk();

        // Gone from the actionable Schedule subset and the default Dispatch view
        $this->assertStringNotContainsString($order->order_number, $this->scheduleHtml(['schedule_type' => ['Delivery']]));
        $this->assertStringNotContainsString($order->order_number, $this->dispatchHtml());
        // Gone from Dispatch even with the date-range views
        $this->assertStringNotContainsString($order->order_number, $this->dispatchHtml(['date_filter' => 'today']));
    }

    // ── 2. Void (end-to-end, gateway mocked) — cancellation is EXPLICIT ──

    /** Card payment + working gateway mock; returns [$order, $row, $payment]. */
    private function makeVoidableOrder(string $txn): array
    {
        $order = $this->makeOrder(100);
        $row = $this->makeScheduleRow($order);
        $payment = $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount'           => 100,
            'status'           => OrderPaymentStatus::Paid->value,
            'transaction_id'   => $txn,
        ]);

        return [$order, $row, $payment];
    }

    private function mockVoidableGateway(string $txn, string $gatewayStatus = 'capturedPendingSettlement', bool $expectVoidCall = true): void
    {
        $this->mock(AuthorizeNetService::class, function ($mock) use ($txn, $gatewayStatus, $expectVoidCall) {
            $mock->shouldReceive('getTransactionDetails')
                ->once()->with($txn)
                ->andReturn((object) ['status' => $gatewayStatus]);
            if ($expectVoidCall) {
                $mock->shouldReceive('voidOrder')
                    ->once()->andReturn(['status' => 'success']);
            }
        });
    }

    private function voidViaEndpoint(Order $order, OrderPayment $payment, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->putJson(
            route('admin.order-management.orders.void-payment', $order->unique_id),
            array_merge([
                'order_payment_id' => $payment->id,
                'reason'           => 'billing_error',
                'processed_by'     => $this->employee->id,
                'employee_code'    => $this->employee->employee_code,
            ], $overrides)
        );
    }

    public function test_void_with_explicit_cancel_closes_the_schedule(): void
    {
        [$order, $row, $payment] = $this->makeVoidableOrder('TXN-CLOSURE-1');
        $this->mockVoidableGateway('TXN-CLOSURE-1');

        $this->voidViaEndpoint($order, $payment, ['cancel_order' => true])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(OrderPaymentStatus::Voided, $payment->refresh()->status);

        $row->refresh();
        $this->assertSame('Close as Completed', $row->delivery_status);
        $this->assertSame('Completed', $row->pickup_status);

        $entry = $this->autoCloseHistory($order)->firstOrFail();
        $this->assertSame('System', $entry->action_by->value);
        $this->assertStringContainsString('voided before delivery', $entry->description);
        $this->assertStringContainsString('explicitly cancelled', $entry->description);
        $this->assertEquals($payment->id, $entry->order_payment_id);
    }

    public function test_void_without_cancel_hides_the_rental_until_the_recharge_settles(): void
    {
        // Schedule Financial-Closure Alignment (2026-07-20) — SUPERSEDES the
        // original "plain void stays visible" rule: a voided order with NO
        // settled replacement payment is financially inactive and leaves
        // the actionable Schedule/Dispatch views. The ROW is untouched
        // (still Pending, never auto-closed), so the moment the recharge
        // settles the rental reappears everywhere automatically — the
        // void-then-recharge correction workflow still completes normally.
        [$order, $row, $payment] = $this->makeVoidableOrder('TXN-CLOSURE-2');
        $this->mockVoidableGateway('TXN-CLOSURE-2');

        $this->voidViaEndpoint($order, $payment) // no cancel_order
            ->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(OrderPaymentStatus::Voided, $payment->refresh()->status);

        $row->refresh();
        $this->assertSame('Pending', $row->delivery_status);
        $this->assertFalse((bool) $row->is_delivered);
        $this->assertCount(0, $this->autoCloseHistory($order));

        // Voided-out (nothing settled) → hidden from the actionable views
        \App\Services\Orders\OrderFinancialActivity::flushMemo();
        $this->assertStringNotContainsString($order->order_number, $this->scheduleHtml(['schedule_type' => ['Delivery']]));
        $this->assertStringNotContainsString($order->order_number, $this->dispatchHtml());

        // Recharge settles → the untouched Pending row is active work again
        $this->payCash($order, 100);
        \App\Services\Orders\OrderFinancialActivity::flushMemo();
        $this->assertStringContainsString($order->order_number, $this->scheduleHtml(['schedule_type' => ['Delivery']]));
        $this->assertStringContainsString($order->order_number, $this->dispatchHtml());
    }

    public function test_void_then_recharge_leaves_delivery_to_proceed_normally(): void
    {
        [$order, $row, $payment] = $this->makeVoidableOrder('TXN-CLOSURE-3');
        $this->mockVoidableGateway('TXN-CLOSURE-3');

        $this->voidViaEndpoint($order, $payment)->assertOk();

        // Recharge: fresh settled payment replaces the voided one
        $this->payCash($order, 100);

        $row->refresh();
        $this->assertSame('Pending', $row->delivery_status);
        $this->assertStringContainsString($order->order_number, $this->scheduleHtml(['schedule_type' => ['Delivery']]));
        $this->assertStringContainsString($order->order_number, $this->dispatchHtml());

        // …and the delivery can complete normally afterwards. Since Phase 4,
        // "normally" includes the Queue Line release rule: this row is queue-
        // managed (Pending rental due today), so a machine must be staged and
        // its fuel verified before any surface may record the release.
        $unit = Equipment::create([
            'equipment_name' => 'Closure Test Unit', 'equipment_id' => 'EQP-CLOSURE-3',
            'brand' => 'TestBrand', 'current_status' => 'available',
            'assigned_product_id' => $row->product_id,
        ]);
        $row->softAssignment()->create([
            'equipment_id' => $unit->id, 'order_id' => $order->id, 'assigned_by' => $this->admin->id,
        ]);
        \App\Services\QueueLine\QueueFuelVerificationService::verify(
            orderProduct: $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']),
            expected: $unit,
            performedBy: $this->admin,
            actor: $this->admin,
            source: \App\Models\Orders\QueueLineFuelVerification::SOURCE_WEB,
        );

        $this->withoutMiddleware()->putJson(
            route('admin.order-management.orders.update-product-schedule', [$order->unique_id, $row->unique_id]),
            ['type' => 'delivery', 'delivery_status' => 'Completed']
        )->assertOk();
        $this->assertSame('Completed', $row->refresh()->delivery_status);
    }

    public function test_gateway_reconciliation_void_respects_the_explicit_cancel_flag(): void
    {
        // Gateway already shows voided but our row is still Paid (prior void
        // succeeded at the gateway, DB update failed) — the reconciliation
        // exit must honor the disposition exactly like the fresh-void exit.
        [$order, $row, $payment] = $this->makeVoidableOrder('TXN-CLOSURE-4');
        $this->mockVoidableGateway('TXN-CLOSURE-4', 'voided', expectVoidCall: false);

        $this->voidViaEndpoint($order, $payment, ['cancel_order' => true])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(OrderPaymentStatus::Voided, $payment->refresh()->status);
        $this->assertSame('Close as Completed', $row->refresh()->delivery_status);
        $this->assertCount(1, $this->autoCloseHistory($order));
    }

    // ── 3. Partial refund never closes ───────────────────────────────────

    public function test_partial_refund_does_not_close_the_schedule(): void
    {
        $order = $this->makeOrder(100);
        $row = $this->makeScheduleRow($order);
        $this->payCash($order, 100);

        $this->refundViaEndpoint($order, 40)->assertOk()->assertJson(['success' => true]);

        $row->refresh();
        $this->assertSame('Pending', $row->delivery_status);
        $this->assertSame('Pending', $row->pickup_status);
        $this->assertFalse((bool) $row->is_delivered);
        $this->assertCount(0, $this->autoCloseHistory($order));
    }

    // ── 3b. Full Amount Less Card Processing Fee (approved business rule):
    //        financially PARTIAL, operationally equivalent to REFUND_FULL ──

    public function test_full_less_processing_fee_refund_closes_the_undelivered_schedule(): void
    {
        $order = $this->makeOrder(100);
        $row = $this->makeScheduleRow($order);
        $original = $this->payCash($order, 100);

        // Gross $100 allocated, $3 fee intentionally retained → net $97 returned
        $refund = $this->makeRefundRow($order, $original, 100, feeRetained: 3.00);

        // Financial classification MUST stay partial — the two concepts
        // remain distinct for reporting/receipts/history.
        $summary = OrderPaymentSummary::for($order->fresh());
        $this->assertSame(OrderPaymentSummary::REFUND_PARTIAL, $summary->refundStatus);
        $this->assertTrue(RefundedOrderScheduleCloser::isOperationallyCompleteRefund($order->fresh()));

        $closed = RefundedOrderScheduleCloser::afterFullRefund($order->fresh(), $refund, $this->admin);

        // Operational state matches REFUND_FULL closure exactly
        $this->assertSame(1, $closed);
        $row->refresh();
        $this->assertSame('Close as Completed', $row->delivery_status);
        $this->assertTrue((bool) $row->is_delivered);
        $this->assertTrue((bool) $row->is_returned);
        $this->assertSame('Completed', $row->pickup_status);

        $history = $this->autoCloseHistory($order);
        $this->assertCount(1, $history);
        $this->assertStringContainsString('less the retained card processing fee', $history->first()->description);
        $this->assertTrue(json_decode($history->first()->extras, true)['refund_less_processing_fee']);
    }

    public function test_full_less_processing_fee_refund_after_delivery_leaves_the_schedule_active(): void
    {
        $order = $this->makeOrder(100);
        $row = $this->makeScheduleRow($order, [
            'delivery_status' => 'Completed',
            'is_delivered'    => true,
        ]);
        $original = $this->payCash($order, 100);
        $refund = $this->makeRefundRow($order, $original, 100, feeRetained: 3.00);

        $closed = RefundedOrderScheduleCloser::afterFullRefund($order->fresh(), $refund, $this->admin);

        // Delivered equipment protection is identical to today's REFUND_FULL behavior
        $this->assertSame(0, $closed);
        $row->refresh();
        $this->assertSame('Completed', $row->delivery_status);
        $this->assertSame('Pending', $row->pickup_status);
        $this->assertFalse((bool) $row->is_returned);
        $this->assertCount(0, $this->autoCloseHistory($order));
    }

    public function test_partial_fee_retained_refund_is_not_operationally_complete(): void
    {
        // $50 gross refunded with $3 fee retained on a $100 order: money is
        // still legitimately held beyond the fee — never closes.
        $order = $this->makeOrder(100);
        $row = $this->makeScheduleRow($order);
        $original = $this->payCash($order, 100);
        $refund = $this->makeRefundRow($order, $original, 50, feeRetained: 3.00);

        $this->assertFalse(RefundedOrderScheduleCloser::isOperationallyCompleteRefund($order->fresh()));
        $this->assertSame(0, RefundedOrderScheduleCloser::afterFullRefund($order->fresh(), $refund, $this->admin));
        $this->assertSame('Pending', $row->refresh()->delivery_status);
    }

    public function test_standard_full_refund_history_wording_is_unchanged(): void
    {
        // Scenario 3 guard: strict REFUND_FULL keeps its exact behavior AND
        // its exact audit wording — the fee-retained variant never leaks in.
        $order = $this->makeOrder(100);
        $this->makeScheduleRow($order);
        $original = $this->payCash($order, 100);
        $refund = $this->makeRefundRow($order, $original, 100);

        $this->assertSame(1, RefundedOrderScheduleCloser::afterFullRefund($order->fresh(), $refund, $this->admin));

        $history = $this->autoCloseHistory($order)->first();
        $this->assertStringContainsString('fully refunded before delivery', $history->description);
        $this->assertStringNotContainsString('processing fee', $history->description);
        $this->assertFalse(json_decode($history->extras, true)['refund_less_processing_fee']);
    }

    // ── 4. Full refund AFTER delivery: equipment tracking preserved ─────

    public function test_full_refund_after_delivery_leaves_the_schedule_active(): void
    {
        $order = $this->makeOrder(100);
        $row = $this->makeScheduleRow($order, [
            'delivery_status' => 'Completed',
            'is_delivered'    => true,
        ]);
        $this->payCash($order, 100);

        $this->refundViaEndpoint($order, 100)->assertOk()->assertJson(['success' => true]);

        $row->refresh();
        // Delivered equipment stays operationally visible through return
        $this->assertSame('Completed', $row->delivery_status);
        $this->assertSame('Pending', $row->pickup_status);
        $this->assertFalse((bool) $row->is_returned);
        $this->assertCount(0, $this->autoCloseHistory($order));

        // Still on the actionable Return subset of the Schedule page
        $this->assertStringContainsString(
            $order->order_number,
            $this->scheduleHtml(['schedule_type' => ['Return']])
        );
    }

    public function test_driver_arrival_evidence_blocks_the_auto_close(): void
    {
        // The strictest safety case: status still Pending, but the driver
        // app recorded arrival — equipment may be on a truck at the site.
        $order = $this->makeOrder(100);
        $row = $this->makeScheduleRow($order, ['delivery_arrived_at' => now()]);
        $original = $this->payCash($order, 100);
        $refund = $this->makeRefundRow($order, $original, 100);

        $closed = RefundedOrderScheduleCloser::afterFullRefund($order, $refund, $this->admin);

        $this->assertSame(0, $closed);
        $this->assertSame('Pending', $row->refresh()->delivery_status);
        $this->assertCount(0, $this->autoCloseHistory($order));
    }

    // ── 5. Failed refund: nothing moves ──────────────────────────────────

    public function test_failed_refund_changes_no_schedule_state(): void
    {
        $order = $this->makeOrder(100);
        $row = $this->makeScheduleRow($order);
        $original = $this->payCash($order, 100);
        // A refund attempt where nothing succeeded: row Failed, allocation Failed
        $failedRefund = $this->makeRefundRow($order, $original, 100, 'Failed', 'failed');

        $closed = RefundedOrderScheduleCloser::afterFullRefund($order, $failedRefund, $this->admin);

        $this->assertSame(0, $closed);
        $this->assertSame('Pending', $row->refresh()->delivery_status);
        $this->assertCount(0, $this->autoCloseHistory($order));
    }

    // ── 6. Idempotency ───────────────────────────────────────────────────

    public function test_reprocessing_the_same_refund_event_is_idempotent(): void
    {
        $order = $this->makeOrder(100);
        $this->makeScheduleRow($order);
        $original = $this->payCash($order, 100);
        $refund = $this->makeRefundRow($order, $original, 100);

        $first = RefundedOrderScheduleCloser::afterFullRefund($order, $refund, $this->admin);
        $second = RefundedOrderScheduleCloser::afterFullRefund($order, $refund, $this->admin);
        $third = RefundedOrderScheduleCloser::afterFullRefund($order, $refund, $this->admin);

        $this->assertSame(1, $first);
        $this->assertSame(0, $second);
        $this->assertSame(0, $third);
        $this->assertCount(1, $this->autoCloseHistory($order));
    }

    public function test_manually_closed_order_gets_no_duplicate_transition(): void
    {
        $order = $this->makeOrder(100);
        $this->makeScheduleRow($order, [
            'delivery_status' => 'Close as Completed',
            'pickup_status'   => 'Completed',
            'is_delivered'    => true,
            'is_returned'     => true,
        ]);
        $original = $this->payCash($order, 100);
        $refund = $this->makeRefundRow($order, $original, 100);

        $closed = RefundedOrderScheduleCloser::afterFullRefund($order, $refund, $this->admin);

        $this->assertSame(0, $closed);
        $this->assertCount(0, $this->autoCloseHistory($order));
    }

    // ── 7. No schedule rows: financial path unaffected ───────────────────

    public function test_order_without_schedule_rows_is_a_safe_no_op(): void
    {
        $order = $this->makeOrder(100); // no order products at all
        $original = $this->payCash($order, 100);
        $refund = $this->makeRefundRow($order, $original, 100);

        $closed = RefundedOrderScheduleCloser::afterFullRefund($order, $refund, $this->admin);

        $this->assertSame(0, $closed);
        $this->assertCount(0, $this->autoCloseHistory($order));
    }

    // ── 8. Mixed delivery state: per-row scope ───────────────────────────

    public function test_mixed_order_closes_only_the_undelivered_rows(): void
    {
        $order = $this->makeOrder(200);
        $delivered = $this->makeScheduleRow($order, [
            'delivery_status' => 'Completed',
            'is_delivered'    => true,
        ]);
        $undelivered = $this->makeScheduleRow($order);
        $original = $this->payCash($order, 200);
        $refund = $this->makeRefundRow($order, $original, 200);

        $closed = RefundedOrderScheduleCloser::afterFullRefund($order, $refund, $this->admin);

        $this->assertSame(1, $closed);
        // Delivered leg untouched — equipment still tracked through return
        $this->assertSame('Completed', $delivered->refresh()->delivery_status);
        $this->assertSame('Pending', $delivered->pickup_status);
        // Undelivered leg closed
        $this->assertSame('Close as Completed', $undelivered->refresh()->delivery_status);

        $extras = json_decode($this->autoCloseHistory($order)->firstOrFail()->extras);
        $this->assertSame([$undelivered->id], $extras->closed_order_product_ids);
    }

    // ── 9. Equipment release mirrors the manual action ───────────────────

    public function test_auto_close_releases_equipment_exactly_like_the_manual_action(): void
    {
        $equipment = Equipment::create([
            'equipment_name' => 'Closure Skid Steer',
            'equipment_id'   => 'EQP-CLOSURE-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'available',
        ]);

        $order = $this->makeOrder(100);
        $row = $this->makeScheduleRow($order, ['equipment_id' => $equipment->id]);
        $original = $this->payCash($order, 100);
        $refund = $this->makeRefundRow($order, $original, 100);

        $closed = RefundedOrderScheduleCloser::afterFullRefund($order, $refund, $this->employee);

        $this->assertSame(1, $closed);
        $equipment->refresh();
        $this->assertSame('maintenance', $equipment->current_status->value);
        $this->assertNull($equipment->current_order_id);
        $this->assertNull($equipment->current_order_product_id);

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'available',
            'to_status'    => 'maintenance',
            'changed_by'   => $this->employee->id,
        ]);
        $this->assertSame(1, EquipmentStatusLog::where('equipment_id', $equipment->id)->count());
    }

    // ── 10. Void disposition semantics ───────────────────────────────────

    public function test_pod_order_void_without_cancel_stays_active(): void
    {
        // Void used as a payment correction on an order that still expects
        // POD collection — no cancel flag, so nothing closes.
        [$order, $row, $payment] = $this->makeVoidableOrder('TXN-CLOSURE-POD');
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value,
            'payment_datetime' => now(), 'amount' => 100,
            'status' => OrderPaymentStatus::Pending->value,
        ]);
        $this->mockVoidableGateway('TXN-CLOSURE-POD');

        $this->voidViaEndpoint($order, $payment)->assertOk();

        $this->assertSame('Pending', $row->refresh()->delivery_status);
        $this->assertCount(0, $this->autoCloseHistory($order));
    }

    public function test_explicit_cancel_closes_even_when_other_settled_money_remains(): void
    {
        // Intent is explicit — a still-settled second payment (to be
        // refunded separately) does not block the operational cancellation.
        [$order, $row, $payment] = $this->makeVoidableOrder('TXN-CLOSURE-MIX');
        $this->payCash($order, 100);
        $this->mockVoidableGateway('TXN-CLOSURE-MIX');

        $this->voidViaEndpoint($order, $payment, ['cancel_order' => true])->assertOk();

        $this->assertSame('Close as Completed', $row->refresh()->delivery_status);
        $this->assertCount(1, $this->autoCloseHistory($order));
    }

    public function test_void_after_delivery_does_not_close(): void
    {
        $order = $this->makeOrder(100);
        $row = $this->makeScheduleRow($order, [
            'delivery_status' => 'Completed',
            'is_delivered'    => true,
        ]);
        $voided = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(), 'amount' => 100,
            'status' => OrderPaymentStatus::Voided->value, 'voided_at' => now(),
        ]);

        $closed = RefundedOrderScheduleCloser::afterVoid($order, $voided, $this->admin);

        $this->assertSame(0, $closed);
        $this->assertSame('Completed', $row->refresh()->delivery_status);
        $this->assertSame('Pending', $row->pickup_status);
    }

    // ── 11. Canonical delivered test ─────────────────────────────────────

    public function test_has_been_delivered_recognizes_every_evidence_source(): void
    {
        $order = $this->makeOrder();

        $this->assertFalse($this->makeScheduleRow($order)->hasBeenDelivered());
        $this->assertTrue($this->makeScheduleRow($order, ['delivery_status' => 'Completed'])->hasBeenDelivered());
        $this->assertTrue($this->makeScheduleRow($order, ['is_delivered' => true])->hasBeenDelivered());
        $this->assertTrue($this->makeScheduleRow($order, ['delivery_is_delivered' => true])->hasBeenDelivered());
        $this->assertTrue($this->makeScheduleRow($order, ['delivery_arrived_at' => now()])->hasBeenDelivered());
        // Reschedule/Pending with no other evidence: not delivered
        $this->assertFalse($this->makeScheduleRow($order, ['delivery_status' => 'Reschedule'])->hasBeenDelivered());
    }

    // ── 12. Manual Close as Completed unchanged ──────────────────────────

    public function test_manual_close_as_completed_still_works_unchanged(): void
    {
        $order = $this->makeOrder(100);
        $row = $this->makeScheduleRow($order);

        $this->withoutMiddleware()->putJson(
            route('admin.order-management.orders.update-product-schedule', [$order->unique_id, $row->unique_id]),
            ['type' => 'delivery', 'delivery_status' => 'Close as Completed']
        )->assertOk();

        $row->refresh();
        $this->assertSame('Close as Completed', $row->delivery_status);
        $this->assertSame('Completed', $row->pickup_status);
        $this->assertTrue((bool) $row->is_delivered);
        $this->assertTrue((bool) $row->is_returned);
        // Manual action writes the employee-attributed history, not the system entry
        $this->assertCount(0, $this->autoCloseHistory($order));
    }
}

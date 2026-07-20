<?php

namespace Tests\Feature\OrderManagement;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use App\Services\Orders\OrderFinancialActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Schedule Financial-Closure Alignment (2026-07-20).
 *
 * RefundedOrderScheduleCloser closes undelivered rows at refund/void time;
 * rows recorded BEFORE that engine shipped can still read Pending while
 * their order's money is conclusively gone. Schedule (and Dispatch) must
 * not present those as active work — the same neutral
 * OrderFinancialActivity rule Queue Line already consumes.
 *
 * Active orders must be unaffected: partial refunds, void-then-recharge,
 * unpaid / pending / partially-paid orders all stay visible.
 */
class ScheduleFinancialClosureAlignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $store;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Sched', 'last_name' => 'Admin',
            'email' => 'sched-admin@test.local', 'status' => 'Active',
        ]);
        $this->store = Store::create(['store_name' => 'Align Store', 'status' => 'Active']);
        $this->product = Product::create([
            'product_name' => 'Align Excavator', 'slug' => 'align-exc-' . uniqid(), 'product_type' => 'Rental',
        ]);

        $this->actingAs($this->admin);
    }

    // ── Fixtures (production-shaped, mirroring the queue suites) ─────────

    private function makeOrder(array $overrides = []): Order
    {
        static $n = 8800;
        $n++;

        return Order::create(array_merge([
            'order_number' => (string) $n,
            'order_date' => now()->format('Y-m-d'),
            'customer_name' => 'Align Customer',
            'grand_total' => 100,
        ], $overrides));
    }

    private function makeRow(?Order $order = null, array $overrides = []): OrderProduct
    {
        $order ??= $this->makeOrder();

        return OrderProduct::create(array_merge([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->product_name,
            'price' => 100, 'quantity' => 1, 'total' => 100,
            'delivery_date' => now()->format('Y-m-d'),
            'delivery_time' => '09:00',
            'delivery_status' => 'Pending',
            'pickup_status' => 'Pending',
            'delivery_transport_mode' => 'Truck',
            'pickup_transport_mode' => 'Truck',
            'delivery_store_id' => $this->store->id,
            'pickup_store_id' => $this->store->id,
            'product_data' => [
                'product_type' => 'Rental', 'product_variant' => 'daily',
                'product_rental_items_prices' => [], 'product_option_items' => [],
            ],
        ], $overrides));
    }

    private function pay(Order $order, float $amount, string $status = 'Paid'): OrderPayment
    {
        return $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => $status,
        ]);
    }

    private function refund(Order $order, OrderPayment $original, float $gross, float $feeRetained = 0.0): OrderPayment
    {
        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 0,
            'status' => 'Refunded',
            'refund_amount' => $gross,
            'refunded_at' => now(),
        ]);

        $refund->refundAllocations()->create([
            'original_order_payment_id' => $original->id,
            'allocated_amount' => $gross,
            'allocated_base_amount' => $gross,
            'allocated_tax_amount' => 0,
            'processing_fee_retained' => $feeRetained > 0 ? $feeRetained : null,
            'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        return $refund;
    }

    /** Schedule AJAX list — the same endpoint the page, its filters, and Schedule Assignment's table use. */
    private function schedule(array $params = []): array
    {
        $response = $this->get(
            route('admin.order-management.schedules.index', $params),
            ['X-Requested-With' => 'XMLHttpRequest'],
        );

        $response->assertOk();

        return $response->json();
    }

    private function dispatch(array $params = []): array
    {
        $response = $this->get(
            route('admin.order-management.dispatch.index', $params),
            ['X-Requested-With' => 'XMLHttpRequest'],
        );

        $response->assertOk();

        return $response->json();
    }

    // ── Exclusions: financially inactive legacy Pending rows ─────────────

    public function test_fully_refunded_legacy_pending_row_is_absent_from_schedule(): void
    {
        $active = $this->makeRow();
        $refunded = $this->makeRow();
        $this->refund($refunded->order->fresh(), $this->pay($refunded->order, 100), 100);

        $json = $this->schedule();

        $this->assertStringContainsString($active->order->order_number, $json['html']);
        $this->assertStringNotContainsString($refunded->order->order_number, $json['html']);
        $this->assertSame(1, $json['total'], 'the visible rows and the total count must agree');
    }

    public function test_fee_retained_operationally_complete_refund_row_is_absent(): void
    {
        $row = $this->makeRow();
        $this->refund($row->order->fresh(), $this->pay($row->order, 100), 100, feeRetained: 3.00);

        $this->assertFalse(OrderFinancialActivity::isActive($row->order->fresh()));

        $json = $this->schedule();
        $this->assertStringNotContainsString($row->order->order_number, $json['html']);
        $this->assertSame(0, $json['total']);
    }

    public function test_voided_with_no_settled_replacement_payment_is_absent(): void
    {
        $row = $this->makeRow();
        $this->pay($row->order, 100, 'Voided');

        $json = $this->schedule();
        $this->assertStringNotContainsString($row->order->order_number, $json['html']);
        $this->assertSame(0, $json['total']);
    }

    // ── Still visible: active orders in every payment shape ──────────────

    public function test_void_then_recharge_and_partial_refund_remain_visible(): void
    {
        $recharge = $this->makeRow();
        $this->pay($recharge->order, 100, 'Voided');
        $this->pay($recharge->order->fresh(), 100, 'Paid');

        $partial = $this->makeRow();
        $this->refund($partial->order->fresh(), $this->pay($partial->order, 100), 40);

        $json = $this->schedule();
        $this->assertStringContainsString($recharge->order->order_number, $json['html']);
        $this->assertStringContainsString($partial->order->order_number, $json['html']);
        $this->assertSame(2, $json['total']);
    }

    public function test_pending_partially_paid_and_paid_active_orders_remain_visible(): void
    {
        $unpaid = $this->makeRow();

        $pending = $this->makeRow();
        $this->pay($pending->order, 100, 'Pending');

        $partiallyPaid = $this->makeRow($this->makeOrder(['grand_total' => 200]));
        $this->pay($partiallyPaid->order, 100, 'Partial Payment');

        $paid = $this->makeRow();
        $this->pay($paid->order, 100, 'Paid');

        $json = $this->schedule();
        foreach ([$unpaid, $pending, $partiallyPaid, $paid] as $row) {
            $this->assertStringContainsString($row->order->order_number, $json['html']);
        }
        $this->assertSame(4, $json['total']);
    }

    // ── Filters, search, counts ───────────────────────────────────────────

    public function test_date_filters_stay_consistent_and_never_reintroduce_inactive_rows(): void
    {
        $active = $this->makeRow();
        $voided = $this->makeRow();
        $this->pay($voided->order, 100, 'Voided');

        foreach (['today', 'week', 'month'] as $dateFilter) {
            $json = $this->schedule(['date_filter' => $dateFilter]);
            $this->assertStringContainsString($active->order->order_number, $json['html'], "date_filter={$dateFilter}");
            $this->assertStringNotContainsString($voided->order->order_number, $json['html'], "date_filter={$dateFilter}");
            $this->assertSame(1, $json['total'], "date_filter={$dateFilter}");
        }
    }

    public function test_store_and_product_filters_do_not_reintroduce_excluded_rows(): void
    {
        $active = $this->makeRow();
        $voided = $this->makeRow();
        $this->pay($voided->order, 100, 'Voided');

        $byStore = $this->schedule(['store_location' => [$this->store->id]]);
        $this->assertStringContainsString($active->order->order_number, $byStore['html']);
        $this->assertStringNotContainsString($voided->order->order_number, $byStore['html']);

        $byProduct = $this->schedule(['product' => $this->product->id]);
        $this->assertStringContainsString($active->order->order_number, $byProduct['html']);
        $this->assertStringNotContainsString($voided->order->order_number, $byProduct['html']);
    }

    public function test_search_cannot_surface_a_financially_inactive_row(): void
    {
        $voided = $this->makeRow();
        $this->pay($voided->order, 100, 'Voided');

        $json = $this->schedule(['order_number' => $voided->order->order_number]);
        $this->assertSame(0, $json['total']);
        // Row markers link via the order's unique_id (the searched number
        // legitimately echoes back inside pagination query strings).
        $this->assertStringNotContainsString($voided->order->unique_id, $json['html']);
    }

    public function test_schedule_assignment_table_obeys_the_same_rule(): void
    {
        // The Schedule Assignment work list is the same endpoint's
        // unassigned_equipment branch.
        $active = $this->makeRow();
        $voided = $this->makeRow();
        $this->pay($voided->order, 100, 'Voided');

        $json = $this->schedule(['unassigned_equipment' => '1']);
        $this->assertStringContainsString($active->order->order_number, $json['html']);
        $this->assertStringNotContainsString($voided->order->order_number, $json['html']);
    }

    public function test_closed_rows_on_active_orders_behave_exactly_as_before(): void
    {
        // Manual 'Close as Completed' on a financially ACTIVE order — the
        // financial rule must not touch it: still listed in the default view
        // (history), exactly as before this alignment.
        $closed = $this->makeRow(null, [
            'delivery_status' => 'Close as Completed',
            'is_delivered' => true, 'is_returned' => true, 'pickup_status' => 'Completed',
        ]);
        $this->pay($closed->order, 100, 'Paid');

        $json = $this->schedule();
        $this->assertStringContainsString($closed->order->order_number, $json['html']);
        $this->assertSame(1, $json['total']);
    }

    public function test_reschedule_badge_count_agrees_with_the_financial_rule(): void
    {
        $activeReschedule = $this->makeRow(null, ['delivery_status' => 'Reschedule']);
        $voidedReschedule = $this->makeRow(null, ['delivery_status' => 'Reschedule']);
        $this->pay($voidedReschedule->order, 100, 'Voided');

        $html = $this->get(route('admin.order-management.schedules.index'))->getContent();

        // The page passes the count to the view; with one active + one
        // voided reschedule order the badge must read 1.
        $this->assertNotNull($activeReschedule);
        $this->assertMatchesRegularExpression('/Rescheduled?[^0-9]*\(?1\)?/i', $html);
    }

    // ── Dispatch parity ───────────────────────────────────────────────────

    public function test_dispatch_list_excludes_inactive_orders_and_keeps_active_ones(): void
    {
        $active = $this->makeRow();
        $voided = $this->makeRow();
        $this->pay($voided->order, 100, 'Voided');
        $refunded = $this->makeRow();
        $this->refund($refunded->order->fresh(), $this->pay($refunded->order, 100), 100);

        $json = $this->dispatch();
        $this->assertStringContainsString($active->order->order_number, $json['html']);
        $this->assertStringNotContainsString($voided->order->order_number, $json['html']);
        $this->assertStringNotContainsString($refunded->order->order_number, $json['html']);
    }
}

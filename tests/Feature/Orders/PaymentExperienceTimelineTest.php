<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderPaymentMethod;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderHistory;
use App\Services\CustomerCreditService;
use App\Services\PaymentDescriptionPresenter;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2 payment experience & receipt modernization, controller-level
 * integration:
 *   - Every payment-related order history row is linked back to the exact
 *     OrderPayment it describes (order_payment_id), not just a free-text
 *     sentence.
 *   - A settled Store Credit payment logs as StoreCreditApplied, not a
 *     generic OrderPaid entry.
 *   - The receipt shows a live payment method (or terms, if still pending)
 *     instead of nothing at all.
 *   - The customer-facing order view shows a status badge for every
 *     status, not just Pending/Paid/Failed.
 */
class PaymentExperienceTimelineTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $admin;
    private User $employee;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Riley', 'last_name' => 'Ledger',
            'email' => 'riley.ledger@example.com', 'status' => 'Active',
        ]);

        $this->admin = User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-timeline-test@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Sam', 'last_name' => 'Clerk',
            'email' => 'sam-timeline-test@example.com', 'status' => 'Active',
        ]);

        $this->order = Order::create([
            'customer_id'   => $this->customer->id,
            'customer_name' => $this->customer->full_name,
            'subtotal'      => 200.0,
            'tax_amount'    => 0.0,
            'grand_total'   => 200.0,
        ]);

        $this->actingAs($this->admin);
    }

    private function receivePayment(array $overrides = [])
    {
        return $this->putJson(
            route('admin.order-management.orders.receive-payment', $this->order->unique_id),
            array_merge([
                'payment_type'        => 'Cash',
                'responsible_person'  => $this->employee->id,
            ], $overrides)
        );
    }

    // ── order_payment_id is populated ────────────────────────────────────

    public function test_receiving_a_cash_payment_links_the_history_entry_to_the_payment_row(): void
    {
        $this->receivePayment(['payment_type' => 'Cash'])->assertOk();

        $payment = $this->order->payments()->latest('id')->firstOrFail();
        $history = OrderHistory::where('order_id', $this->order->id)
            ->where('action', OrderHistoryAction::OrderPaid->value)
            ->firstOrFail();

        $this->assertSame($payment->id, $history->order_payment_id);
        $this->assertSame($payment->id, $history->orderPayment->id);
    }

    public function test_a_store_credit_payment_logs_store_credit_applied_not_order_paid(): void
    {
        CustomerCreditService::createFinancialCredit(
            customerId: $this->customer->id, amount: 300.0, reason: 'Test grant',
        );

        $this->receivePayment(['payment_type' => 'StoreCredit'])->assertOk();

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $this->order->id,
            'action'   => OrderHistoryAction::StoreCreditApplied->value,
        ]);
        $this->assertDatabaseMissing('order_histories', [
            'order_id' => $this->order->id,
            'action'   => OrderHistoryAction::OrderPaid->value,
        ]);
    }

    // ── timelineEntry() renders correctly for a real, persisted row ──────

    public function test_timeline_entry_reads_correctly_off_a_real_persisted_history_row(): void
    {
        $this->receivePayment(['payment_type' => 'Cash'])->assertOk();

        $history = OrderHistory::where('order_id', $this->order->id)
            ->where('action', OrderHistoryAction::OrderPaid->value)
            ->with('orderPayment')
            ->firstOrFail();

        $entry = PaymentDescriptionPresenter::timelineEntry($history);

        $this->assertSame('Payment received', $entry['headline']);
        $this->assertSame('Cash', $entry['method']);
        $this->assertSame(200.0, $entry['amount']);
        $this->assertSame('pos', $entry['sign']);
    }

    // ── Receipt shows a live payment method / terms ──────────────────────

    public function test_receipt_shows_the_live_payment_method_after_a_cash_payment(): void
    {
        $this->receivePayment(['payment_type' => 'Cash'])->assertOk();
        $this->order->refresh();

        $this->assertSame('Cash', ReceiptService::currentPaymentMethodLabel($this->order));
    }

    public function test_receipt_shows_no_method_and_pay_on_delivery_terms_for_an_unpaid_cod_order(): void
    {
        $codOrder = Order::create([
            'customer_id'   => $this->customer->id,
            'customer_name' => $this->customer->full_name,
            'subtotal'      => 100.0,
            'tax_amount'    => 0.0,
            'grand_total'   => 100.0,
        ]);
        $codOrder->payments()->create([
            'payment_method'   => OrderPaymentMethod::COD->value,
            'payment_datetime' => now(),
            'amount'           => 0.0,
            'status'           => \App\Enums\Orders\OrderPaymentStatus::Pending->value,
        ]);
        $codOrder->refresh();

        $this->assertNull(ReceiptService::currentPaymentMethodLabel($codOrder));
        $this->assertSame('Pay on Delivery', PaymentDescriptionPresenter::termsLabel($codOrder));
    }

    public function test_payment_method_breakdown_groups_by_method_without_enabling_split_payment_ui(): void
    {
        $this->receivePayment(['payment_type' => 'Cash'])->assertOk();

        $breakdown = ReceiptService::paymentMethodBreakdown($this->order);

        $this->assertCount(1, $breakdown);
        $this->assertSame('Cash', $breakdown[0]['method']);
        $this->assertSame(200.0, $breakdown[0]['amount']);
    }

    // ── Customer-facing order view shows a badge for every status ───────

    public function test_customer_order_view_shows_a_badge_for_partially_paid_not_nothing(): void
    {
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount'           => 50.0,
            'status'           => \App\Enums\Orders\OrderPaymentStatus::PartialPayment->value,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('front.customer.dashboard.order.view', $this->order->unique_id));

        $response->assertOk();
        $response->assertSee('Partially Paid');
    }

    // ── Payment Details modal / Order Details page renders without error ─

    public function test_order_details_page_renders_the_payment_details_button_and_modal(): void
    {
        $this->receivePayment(['payment_type' => 'Cash'])->assertOk();

        $response = $this->get(route('admin.order-management.orders.edit', $this->order->unique_id));

        $response->assertOk();
        $response->assertSee('id="paymentDetailsBtn"', false);
        $response->assertSee('id="paymentDetailsModal"', false);
        $response->assertSee('Payment Details');
    }
}

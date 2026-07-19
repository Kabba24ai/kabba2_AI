<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Customers\Receipt;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The receipt's "Payment Status" line must always reflect the order's
 * CURRENT financial state (ReceiptService::currentPaymentStatusLabel),
 * not whatever was true when the receipt row was first created. Bug:
 * a receipt generated for a POD order while still unpaid, then downloaded
 * again after the payment completed, kept showing "Pending" because
 * ReceiptService::getOrCreateReceipt() returned the pre-existing stored
 * row verbatim and the view read Receipt::payment_status (a value frozen
 * at creation time) instead of asking the order's canonical balance state.
 */
class ReceiptPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Joseph',
            'last_name'  => 'Miller',
            'email'      => 'joseph.miller@example.com',
            'status'     => 'Active',
        ]);

        $this->admin = User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-receipt-test@example.com', 'status' => 'Active',
        ]);

        $this->actingAs($this->admin);
    }

    private function makeOrder(float $grandTotal = 100.0, float $subtotal = 100.0, float $taxAmount = 0.0): Order
    {
        return Order::create([
            'customer_id'   => $this->customer->id,
            'customer_name' => $this->customer->full_name,
            'subtotal'      => $subtotal,
            'tax_amount'    => $taxAmount,
            'grand_total'   => $grandTotal,
        ]);
    }

    // ── 1. POD created but unpaid ───────────────────────────────────────

    public function test_pod_order_created_unpaid_shows_pending(): void
    {
        $order = $this->makeOrder(642.04);

        // No payment row at all yet — exactly the state right after a
        // POD/"Pay at Front Desk" order is placed.
        $this->assertSame('Pending', ReceiptService::currentPaymentStatusLabel($order));
    }

    // ── 2. POD later paid in full (order #3167's exact scenario) ───────

    public function test_pod_order_later_paid_in_full_shows_paid_in_full(): void
    {
        // Mirrors order #3167: Subtotal $585.00, Tax $57.04, Total $642.04.
        $order = $this->makeOrder(642.04, 585.00, 57.04);

        // Receipt generated while still unpaid — this is the stored row
        // that used to poison every later download.
        $firstReceipt = ReceiptService::getOrCreateReceipt($order);
        $this->assertSame('pending', $firstReceipt->payment_status);
        $this->assertSame('Pending', ReceiptService::currentPaymentStatusLabel($order));

        // Payment completes via Pay at Front Desk (Cash method) — records a
        // Paid payment; the payment method itself is irrelevant to status.
        $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value, // Cash = "Pay at Front Desk" label
            'payment_datetime' => now(),
            'amount'           => 642.04,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);
        $order->refresh();

        // Same receipt row (get-or-create finds the existing one) —
        // the *display* status must now be live, not the frozen snapshot.
        $secondReceipt = ReceiptService::getOrCreateReceipt($order);
        $this->assertSame($firstReceipt->id, $secondReceipt->id, 'must reuse the existing receipt row, not create a second one');
        $this->assertSame('Paid in Full', ReceiptService::currentPaymentStatusLabel($order));

        // Totals on the receipt are untouched by the fix.
        $this->assertEquals(585.00, (float) $secondReceipt->subtotal);
        $this->assertEquals(57.04, (float) $secondReceipt->sales_tax);
        $this->assertEquals(642.04, (float) $secondReceipt->total);
    }

    // ── 3. Standard immediately paid order ──────────────────────────────

    public function test_standard_immediately_paid_order_shows_paid_in_full(): void
    {
        $order = $this->makeOrder(200.0);

        $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount'           => 200.0,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);
        $order->refresh();

        $this->assertSame('Paid in Full', ReceiptService::currentPaymentStatusLabel($order));
    }

    // ── 4. Partially paid order ─────────────────────────────────────────

    public function test_partially_paid_order_does_not_show_paid_in_full(): void
    {
        $order = $this->makeOrder(200.0);

        $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 75.0,
            'status'           => OrderPaymentStatus::PartialPayment->value,
        ]);
        $order->refresh();

        $status = ReceiptService::currentPaymentStatusLabel($order);
        $this->assertNotSame('Paid in Full', $status);
        $this->assertSame('Partially Paid', $status);
    }

    // ── 5. Refunded / voided follow canonical rules ─────────────────────

    public function test_fully_refunded_order_shows_paid_in_full_and_fully_refunded(): void
    {
        // Payment Architecture Finalization (Phase 4A): currentPaymentStatusLabel()
        // now delegates to PaymentDescriptionPresenter::orderStatusLabel(),
        // the same canonical label every other screen shows — a fully
        // refunded order reads "Paid in Full · Fully Refunded", not a bare
        // "Refunded" that erases the fact it was ever collected.
        $order = $this->makeOrder(150.0);

        $paid = $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount'           => 150.0,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        $order->payments()->create([
            'parent_order_payment_id' => $paid->id,
            'payment_method'          => OrderPaymentMethod::Card->value,
            'payment_datetime'        => now(),
            'refunded_at'             => now(),
            'status'                  => OrderPaymentStatus::Refund->value,
            'refund_amount'           => 150.0,
        ]);
        $order->refresh();

        $status = ReceiptService::currentPaymentStatusLabel($order);
        $this->assertNotSame('Paid in Full', $status, 'must not read as if nothing was refunded');
        $this->assertSame('Paid in Full · Fully Refunded', $status);
    }

    public function test_partially_refunded_order_shows_paid_in_full_and_partially_refunded(): void
    {
        $order = $this->makeOrder(150.0);

        $paid = $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount'           => 150.0,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        $order->payments()->create([
            'parent_order_payment_id' => $paid->id,
            'payment_method'          => OrderPaymentMethod::Card->value,
            'payment_datetime'        => now(),
            'refunded_at'             => now(),
            'status'                  => OrderPaymentStatus::PartialRefund->value,
            'refund_amount'           => 50.0,
        ]);
        $order->refresh();

        $status = ReceiptService::currentPaymentStatusLabel($order);
        $this->assertNotSame('Paid in Full', $status, 'must not read as if nothing was refunded');
        $this->assertSame('Paid in Full · Partially Refunded', $status);
    }

    // ── Status precedence: a resolved failed attempt must not haunt the receipt ──

    public function test_failed_card_attempt_with_no_successful_payment_shows_failed(): void
    {
        $order = $this->makeOrder(100.0);

        $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount'           => 0,
            'status'           => OrderPaymentStatus::Failed->value,
        ]);
        $order->refresh();

        $this->assertSame('Failed', ReceiptService::currentPaymentStatusLabel($order));
    }

    public function test_failed_card_attempt_followed_by_successful_payment_shows_paid_in_full_not_failed(): void
    {
        // The exact precedence bug already fixed on the Order Details
        // header, now verified for receipts too: an earlier failed
        // attempt must stop driving the status the moment a later payment
        // genuinely satisfies the order.
        $order = $this->makeOrder(500.0);

        $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => now()->subMinute(),
            'amount'           => 0,
            'status'           => OrderPaymentStatus::Failed->value,
        ]);
        $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 500.0,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);
        $order->refresh();

        $this->assertSame('Paid in Full', ReceiptService::currentPaymentStatusLabel($order));

        // And the stored creation-time snapshot must agree too.
        $receipt = ReceiptService::getOrCreateReceipt($order);
        $this->assertSame('paid', $receipt->payment_status);
    }

    public function test_voided_payment_does_not_show_paid_in_full(): void
    {
        $order = $this->makeOrder(150.0);

        $payment = $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount'           => 150.0,
            'status'           => OrderPaymentStatus::Paid->value,
            'transaction_id'   => 'TXN-VOID-TEST',
        ]);

        // Void is an in-place reversal of the same row (no new row) —
        // matches VoidPaymentController's actual behavior.
        $payment->update(['status' => OrderPaymentStatus::Voided->value, 'voided_at' => now()]);
        $order->refresh();

        $this->assertNotSame('Paid in Full', ReceiptService::currentPaymentStatusLabel($order));
        // Intentional behavioral change (voided-receipt business decision):
        // customers need printable proof a charge was cancelled, so a
        // voided, otherwise-unpaid order now reads 'Voided' — previously
        // this asserted 'Pending' because OrderPaymentSummary was blind to
        // Voided rows. Full voided-presentation coverage lives in
        // ReceiptVoidedPresentationTest.
        $this->assertSame('Voided', ReceiptService::currentPaymentStatusLabel($order));
    }

    // ── 6. Printed and emailed receipts share the same logic ───────────

    public function test_receipt_order_relation_resolves_for_the_email_pathway(): void
    {
        // SendReceiptEmailListener renders the receipt via $receipt->order —
        // this was silently null before Receipt::order() existed.
        $order = $this->makeOrder(300.0);
        $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 300.0,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        $receipt = ReceiptService::getOrCreateReceipt($order);
        $reloaded = Receipt::with('order')->findOrFail($receipt->id);

        $this->assertNotNull($reloaded->order);
        $this->assertSame($order->id, $reloaded->order->id);

        // The exact call SendReceiptEmailListener makes to build the PDF
        // view-model — proves the emailed receipt sees the same live status
        // as the printed one, since both call the same service with the
        // same (now correctly resolved) $order.
        $this->assertSame('Paid in Full', ReceiptService::currentPaymentStatusLabel($reloaded->order));
    }

    public function test_receipt_download_route_renders_successfully_for_a_paid_order(): void
    {
        $order = $this->makeOrder(642.04, 585.00, 57.04);
        $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value, // Cash = "Pay at Front Desk" label
            'payment_datetime' => now(),
            'amount'           => 642.04,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        $response = $this->get(route('admin.order-management.orders.receipt-download', $order->unique_id));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}

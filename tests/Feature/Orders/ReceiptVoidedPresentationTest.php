<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\AuthorizeNetService;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Voided-receipt business requirement: a customer whose charge was voided
 * often wants printable proof of the cancellation. The receipt for a
 * voided, otherwise-unpaid order must read exactly "Payment Status:
 * Voided" and stay intentionally minimal — no Payment Method line, no
 * method label, no multiple-method breakdown, no Payment Terms — because
 * the voided attempt's method is not how the order was paid (it wasn't
 * paid at all).
 *
 * Void status is sourced from OrderPaymentSummary::voidedPayments — the
 * actual Voided rows stamped in place by VoidPaymentController — never
 * inferred from a Failed/declined attempt (charge never succeeded;
 * nothing existed to cancel) and never conflated with a refund (money
 * settled and was returned; the original method stays on that receipt).
 * The voided fixture here goes through the real production void path
 * (PUT void-payment + gateway verification), not hand-set statuses.
 */
class ReceiptVoidedPresentationTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $admin;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Neutral name on purpose: it appears verbatim in the receipt's
        // Bill To section, so it must never collide with a status word a
        // negative assertion targets ("Voided Receipt" here originally
        // made every bare 'Voided' NotContains assertion fail on the
        // customer name, not the payment status).
        $this->customer = Customer::create([
            'first_name' => 'Jordan', 'last_name' => 'Baker',
            'email' => 'voided-receipt@example.com', 'status' => 'Active',
        ]);

        $this->admin = User::create([
            'first_name' => 'Gary', 'last_name' => 'Terminal',
            'email' => 'voided-receipt-admin@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'John', 'last_name' => 'Smith',
            'email' => 'voided-receipt-employee@example.com', 'status' => 'Active',
        ]);

        $this->actingAs($this->admin);
    }

    private function makeOrder(float $grandTotal): Order
    {
        return Order::create([
            'customer_id' => $this->customer->id,
            'customer_name' => 'Jordan Baker',
            'subtotal' => $grandTotal,
            'tax_amount' => 0,
            'grand_total' => $grandTotal,
        ]);
    }

    /**
     * Voids the given card payment through the REAL production path —
     * VoidRequest validation (verified employee + structured reason),
     * VoidPaymentController's server-side eligibility checks, and the
     * gateway pre-void status verification — with only the Authorize.net
     * boundary mocked, exactly as RefundVoidProcessedByTest does.
     */
    private function voidViaProductionPath(Order $order, int $paymentId, string $transactionId): void
    {
        $this->mock(AuthorizeNetService::class, function ($mock) use ($transactionId) {
            $mock->shouldReceive('getTransactionDetails')
                ->once()->with($transactionId)
                ->andReturn((object) ['status' => 'capturedPendingSettlement']);
            $mock->shouldReceive('voidOrder')
                ->once()->andReturn(['status' => 'success']);
        });

        $this->putJson(
            route('admin.order-management.orders.void-payment', $order->unique_id),
            [
                'reason' => 'billing_error',
                'processed_by' => $this->employee->id,
                'employee_code' => $this->employee->employee_code,
                'order_payment_id' => $paymentId,
            ]
        )->assertOk()->assertJson(['success' => true]);
    }

    private function voidedOrder(float $amount = 300.0): Order
    {
        $order = $this->makeOrder($amount);

        $payment = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => OrderPaymentStatus::Paid->value,
            'transaction_id' => 'TXN-VOID-RECEIPT-1',
        ]);

        $this->voidViaProductionPath($order, $payment->id, 'TXN-VOID-RECEIPT-1');

        return $order->fresh();
    }

    private function renderReceiptHtml(Order $order): string
    {
        $receipt = ReceiptService::getOrCreateReceipt($order);

        $this->assertNotNull($receipt, 'receipt must still be generatable — proof-of-void is the whole point');

        return view('admin.order_management.orders.print_receipt', [
            'receipt' => $receipt,
            'order' => $order->fresh(),
            'customer' => $this->customer,
            'sales_tax' => \App\Helpers\ConfigurationHelper::getSettings(null, 'sales_tax'),
        ])->render();
    }

    // ── The voided receipt itself ──────────────────────────────────────

    public function test_voided_order_reads_voided_via_the_production_void_path(): void
    {
        $order = $this->voidedOrder();

        $this->assertSame('Voided', ReceiptService::currentPaymentStatusLabel($order));
        $this->assertStringContainsString('Payment Status: Voided', $this->renderReceiptHtml($order));
    }

    public function test_voided_receipt_shows_no_payment_method_line_or_original_method(): void
    {
        $html = $this->renderReceiptHtml($this->voidedOrder());

        $this->assertStringNotContainsString('Payment Method:', $html);
        $this->assertStringNotContainsString('Credit / Debit Card', $html, 'the voided attempt\'s method must not be presented');
        $this->assertStringNotContainsString('Payment Terms:', $html, 'voided receipts are intentionally minimal');
    }

    public function test_voided_receipt_shows_no_multiple_method_breakdown(): void
    {
        $html = $this->renderReceiptHtml($this->voidedOrder());

        $this->assertStringNotContainsString('Multiple Methods', $html);
        $this->assertStringNotContainsString('Credit / Debit Card: $', $html);
    }

    // ── What must NOT read as Voided ───────────────────────────────────

    public function test_failed_attempt_is_not_presented_as_voided(): void
    {
        // A declined charge never succeeded — there was nothing to cancel.
        $order = $this->makeOrder(200.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => 0,
            'status' => OrderPaymentStatus::Failed->value,
        ]);

        $this->assertSame('Failed', ReceiptService::currentPaymentStatusLabel($order));

        $html = $this->renderReceiptHtml($order);

        // Scoped to the status line — a bare 'Voided' substring would also
        // match unrelated receipt content (it originally matched the
        // fixture customer's name in Bill To).
        $this->assertStringContainsString('Payment Status: Failed', $html);
        $this->assertStringNotContainsString('Payment Status: Voided', $html);
    }

    public function test_voided_attempt_alongside_settled_payment_does_not_suppress_anything(): void
    {
        // Suppression is keyed on the ORDER-level Voided state, not the mere
        // existence of a voided row: once other money genuinely settles,
        // the receipt reads the paid state and keeps its method line.
        $order = $this->makeOrder(300.0);

        $voided = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => 300.0,
            'status' => OrderPaymentStatus::Paid->value,
            'transaction_id' => 'TXN-VOID-RECEIPT-2',
        ]);
        $this->voidViaProductionPath($order, $voided->id, 'TXN-VOID-RECEIPT-2');

        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 300.0,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        $html = $this->renderReceiptHtml($order->fresh());

        $this->assertStringContainsString('Payment Status: Paid in Full', $html);
        $this->assertStringContainsString('Payment Method: Cash', $html);
        // Status-line scoped (see test_failed_attempt above for why).
        $this->assertStringNotContainsString('Payment Status: Voided', $html);
    }

    public function test_refunded_receipt_keeps_canonical_refund_status_and_original_method(): void
    {
        // A refund is not a void: money settled and was later returned, so
        // the receipt keeps the compound refund status AND the original
        // successful method.
        $order = $this->makeOrder(400.0);
        $paid = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 400.0,
            'status' => OrderPaymentStatus::Paid->value,
        ]);
        $order->payments()->create([
            'parent_order_payment_id' => $paid->id,
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'refunded_at' => now(),
            'status' => OrderPaymentStatus::Refund->value,
            'refund_amount' => 400.0,
        ]);

        $html = $this->renderReceiptHtml($order->fresh());

        $this->assertStringContainsString('Payment Status: Paid in Full · Fully Refunded', $html);
        $this->assertStringContainsString('Payment Method: Cash', $html);
        // Status-line scoped (see test_failed_attempt above for why).
        $this->assertStringNotContainsString('Payment Status: Voided', $html);
    }
}

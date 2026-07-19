<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Architecture Finalization (Phase 4A) — ReceiptService::
 * currentPaymentMethodLabel() previously read $order->lastPaidPayment ??
 * $order->lastPayment (a single row) to decide the receipt's "Payment
 * Method" line. On a split-payment order this silently showed only
 * whichever method was entered most recently — never "Multiple Methods,"
 * the treatment every other screen already uses. Now sourced from
 * OrderPaymentSummary::paymentMethodsUsed, and print_receipt.blade.php
 * shows an itemized per-method breakdown (ReceiptService::
 * paymentMethodBreakdown(), previously written but never wired to a view)
 * whenever more than one method was actually used.
 */
class ReceiptPaymentMethodPresentationTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Method', 'last_name' => 'Presentation',
            'email' => 'method-presentation@example.com', 'status' => 'Active',
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Receipt', 'last_name' => 'Clerk',
            'email' => 'receipt-method-clerk@example.com', 'status' => 'Active',
        ]));
    }

    private function makeOrder(float $grandTotal): Order
    {
        // subtotal/tax_amount must be set explicitly: orders.* and the
        // receipts.* snapshot columns are NOT NULL, and getOrCreateReceipt()
        // copies the in-memory attributes verbatim — an order fixture that
        // omits them presents NULLs no DB-hydrated production order can,
        // and the receipt INSERT is rejected under strict mode. Mirrors
        // ReceiptPaymentStatusTest::makeOrder().
        return Order::create([
            'customer_id' => $this->customer->id,
            'customer_name' => 'Method Presentation',
            'subtotal' => $grandTotal,
            'tax_amount' => 0,
            'grand_total' => $grandTotal,
        ]);
    }

    // ── currentPaymentMethodLabel() ────────────────────────────────────────

    public function test_single_payment_order_shows_its_one_method(): void
    {
        $order = $this->makeOrder(200);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'amount' => 200,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->assertSame('Cash', ReceiptService::currentPaymentMethodLabel($order));
    }

    public function test_split_payment_order_shows_multiple_methods_not_just_the_last_one(): void
    {
        $order = $this->makeOrder(1000);
        // Cash entered FIRST — the old lastPaidPayment/lastPayment fallback
        // would have shown only Card (entered last).
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now()->subMinute(), 'amount' => 600,
            'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(), 'amount' => 400,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        $label = ReceiptService::currentPaymentMethodLabel($order);

        $this->assertSame('Multiple Methods', $label);
        $this->assertNotSame('Credit / Debit Card', $label, 'must not silently drop Cash');
    }

    public function test_failed_only_attempt_shows_no_method_falls_back_to_terms(): void
    {
        // A declined card must never be reported as "the" payment method —
        // nothing actually settled.
        $order = $this->makeOrder(300);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(), 'amount' => 0,
            'status' => OrderPaymentStatus::Failed->value,
        ]);

        $this->assertNull(ReceiptService::currentPaymentMethodLabel($order));
    }

    public function test_failed_attempt_then_different_successful_method_shows_only_the_settled_method(): void
    {
        $order = $this->makeOrder(250);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now()->subMinute(), 'amount' => 0,
            'status' => OrderPaymentStatus::Failed->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'amount' => 250,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->assertSame('Cash', ReceiptService::currentPaymentMethodLabel($order));
    }

    public function test_pending_only_order_shows_no_method_and_pending_status(): void
    {
        // A COD/"Pay at Front Desk" placeholder row (status Pending, no
        // money yet collected) must never be reported as a settled payment
        // method — mirrors the Failed-only case above, since
        // OrderPaymentSummary::paymentMethodsUsed only ever includes
        // settled() rows and Pending does not qualify as settled.
        $order = $this->makeOrder(300);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value,
            'payment_datetime' => now(), 'amount' => 300,
            'status' => OrderPaymentStatus::Pending->value,
        ]);

        $this->assertNull(ReceiptService::currentPaymentMethodLabel($order));
        $this->assertSame('Pending', ReceiptService::currentPaymentStatusLabel($order));

        $html = $this->renderReceiptHtml($order);

        $this->assertStringContainsString('Payment Status: Pending', $html);
        $this->assertStringContainsString('Payment Terms: Pay on Delivery', $html);
        $this->assertStringNotContainsString('Payment Method:', $html, 'no settled method exists to report');
    }

    // ── mapPaymentMethod() via the stored snapshot (getOrCreateReceipt) ────

    public function test_stored_snapshot_never_records_a_failed_rows_method(): void
    {
        $order = $this->makeOrder(300);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now()->subMinute(), 'amount' => 0,
            'status' => OrderPaymentStatus::Failed->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'amount' => 300,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        $receipt = ReceiptService::getOrCreateReceipt($order);

        $this->assertSame('cash', $receipt->payment_method);
    }

    // ── Blade rendering: the itemized breakdown actually appears ──────────

    private function renderReceiptHtml(Order $order): string
    {
        $receipt = ReceiptService::getOrCreateReceipt($order);

        return view('admin.order_management.orders.print_receipt', [
            'receipt' => $receipt,
            'order' => $order->fresh(),
            'customer' => $this->customer,
            'sales_tax' => \App\Helpers\ConfigurationHelper::getSettings(null, 'sales_tax'),
        ])->render();
    }

    public function test_split_payment_receipt_renders_multiple_methods_and_itemized_breakdown(): void
    {
        $order = $this->makeOrder(1000);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now()->subMinute(), 'amount' => 600,
            'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(), 'amount' => 400,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        $html = $this->renderReceiptHtml($order);

        $this->assertStringContainsString('Payment Method: Multiple Methods', $html);
        $this->assertStringContainsString('Cash: $600.00', $html);
        $this->assertStringContainsString('Credit / Debit Card: $400.00', $html);
    }

    public function test_single_payment_receipt_renders_no_breakdown_lines(): void
    {
        $order = $this->makeOrder(500);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'amount' => 500,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        $html = $this->renderReceiptHtml($order);

        $this->assertStringContainsString('Payment Method: Cash', $html);
        $this->assertStringNotContainsString('Multiple Methods', $html);
        // No itemized breakdown for the single-method case — the layout
        // must render identically to before this change.
        $this->assertStringNotContainsString('Cash: $500.00', $html);
    }

    public function test_fully_refunded_receipt_renders_canonical_compound_label(): void
    {
        $order = $this->makeOrder(400);
        $paid = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'amount' => 400,
            'status' => OrderPaymentStatus::Paid->value,
        ]);
        $order->payments()->create([
            'parent_order_payment_id' => $paid->id,
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'refunded_at' => now(),
            'status' => OrderPaymentStatus::Refund->value,
            'refund_amount' => 400,
        ]);

        $html = $this->renderReceiptHtml($order);

        $this->assertStringContainsString('Payment Status: Paid in Full · Fully Refunded', $html);
    }
}

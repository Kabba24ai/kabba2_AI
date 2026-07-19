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
 * Storage compatibility for the newer payment methods (Tap to Pay, Store
 * Credit, Gift Card, Zelle / Venmo): ReceiptService::mapPaymentMethod()
 * emits snapshot values ('tap_to_pay', 'store_credit', 'gift_card',
 * 'zelle_venmo') that the ORIGINAL receipts.payment_method enum
 * (cash|card|online|cheque|other) rejected under strict MySQL — the
 * receipt INSERT threw, getOrCreateReceipt() swallowed it, and Print
 * Receipt broke with a null receipt. The column is now a nullable string
 * (2026_07_19 migration; the 2026_07_13 enum widening was the interim
 * fix), so receipt creation must succeed and round-trip the exact
 * snapshot value for every one of these methods, with the canonical
 * customer-facing label intact.
 */
class ReceiptMethodStorageCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Storage', 'last_name' => 'Compat',
            'email' => 'receipt-method-storage@example.com', 'status' => 'Active',
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Receipt', 'last_name' => 'Clerk',
            'email' => 'receipt-storage-clerk@example.com', 'status' => 'Active',
        ]));
    }

    private function paidOrderVia(OrderPaymentMethod $method, float $amount = 250.0): Order
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'customer_name' => 'Storage Compat',
            'subtotal' => $amount,
            'tax_amount' => 0,
            'grand_total' => $amount,
        ]);

        $order->payments()->create([
            'payment_method' => $method->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        return $order;
    }

    /**
     * The core proof, shared by all four methods: the receipt row is
     * actually created (not silently nulled by a rejected INSERT), the
     * snapshot survives a genuine DB round-trip with the exact expected
     * value, and the live label is the canonical customer-facing one.
     */
    private function assertReceiptStores(OrderPaymentMethod $method, string $expectedSnapshot, string $expectedLabel): void
    {
        $order = $this->paidOrderVia($method);

        $receipt = ReceiptService::getOrCreateReceipt($order);

        $this->assertNotNull(
            $receipt,
            "Receipt creation failed for {$method->value} — the payment_method column rejected '{$expectedSnapshot}'."
        );

        $stored = Receipt::findOrFail($receipt->id);
        $this->assertSame($expectedSnapshot, $stored->payment_method);
        $this->assertSame($expectedLabel, ReceiptService::currentPaymentMethodLabel($order));
    }

    public function test_receipt_creation_succeeds_for_tap_to_pay(): void
    {
        $this->assertReceiptStores(OrderPaymentMethod::TapToPay, 'tap_to_pay', 'Tap to Pay');
    }

    public function test_receipt_creation_succeeds_for_store_credit(): void
    {
        $this->assertReceiptStores(OrderPaymentMethod::StoreCredit, 'store_credit', 'Store Credit');
    }

    public function test_receipt_creation_succeeds_for_gift_card(): void
    {
        $this->assertReceiptStores(OrderPaymentMethod::GiftCard, 'gift_card', 'Gift Card');
    }

    public function test_receipt_creation_succeeds_for_zelle_venmo(): void
    {
        $this->assertReceiptStores(OrderPaymentMethod::ZelleVenmo, 'zelle_venmo', 'Zelle / Venmo');
    }
}

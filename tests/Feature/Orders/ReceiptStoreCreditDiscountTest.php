<?php

namespace Tests\Feature\Orders;

use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCredit;
use App\Models\Discounts\ProductDiscount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A Store Credit discount is a PRE-TAX product discount that rewrites the
 * order's tax_amount + grand_total. The receipt must reflect that — its
 * frozen financial snapshot is re-synced to the order's current canonical
 * totals on every getOrCreateReceipt(), the same "always live, never the
 * creation-time snapshot" rule the receipt's payment status/method already
 * follow. Regression guard for the receipt showing pre-discount money.
 */
class ReceiptStoreCreditDiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\Configurations\Setting::updateOrCreate(['setting_name' => 'sales_tax'], ['setting_type' => 'Product Settings', 'setting_value' => 0.0975]);
        $this->admin = User::create(['first_name' => 'Rcpt', 'last_name' => 'Admin', 'email' => 'rcpt-admin@test.local', 'status' => 'Active']);
        $this->actingAs($this->admin);
    }

    public function test_receipt_reflects_store_credit_discount_after_it_is_applied(): void
    {
        $customer = Customer::create(['first_name' => 'Rcpt', 'last_name' => 'Cust', 'email' => 'rcpt-' . uniqid() . '@test.local', 'status' => 'Active']);
        CustomerCredit::create(['customer_id' => $customer->id, 'type' => 'grant', 'amount' => 1000, 'reason' => 'seed']);

        $order = Order::create([
            'order_date' => now()->format('Y-m-d'), 'customer_id' => $customer->id, 'customer_name' => $customer->full_name,
            'subtotal' => 1000, 'tax_amount' => 97.50, 'grand_total' => 1097.50,
        ]);

        // Receipt created BEFORE any discount — snapshots the original totals.
        $before = ReceiptService::getOrCreateReceipt($order);
        $this->assertEquals(1000.00, (float) $before->subtotal);
        $this->assertEquals(97.50, (float) $before->sales_tax);
        $this->assertEquals(1097.50, (float) $before->total);

        // Apply a $400 Store Credit discount through the real endpoint.
        $this->postJson(
            route('admin.order-management.orders.store-credit-discount.apply', ['unique_id' => $order->unique_id]),
            ['store_credit_discount' => 400, 'responsible_person' => $this->admin->id]
        )->assertOk()->assertJsonPath('success', true);

        $order->refresh();

        // Same receipt row, now re-synced to the discounted canonical totals.
        $after = ReceiptService::getOrCreateReceipt($order);
        $this->assertSame($before->id, $after->id, 'no duplicate receipt is minted');
        $this->assertEquals(1000.00, (float) $after->subtotal, 'gross subtotal untouched');
        $this->assertEquals(58.50, (float) $after->sales_tax, 'recalculated tax on the discounted value');
        $this->assertEquals(658.50, (float) $after->total, 'grand total reflects the pre-tax discount');

        // The engine snapshot the receipt template renders the discount line from.
        $discount = ProductDiscount::where('target_type', 'order')->where('target_id', $order->id)
            ->where('discount_type', 'store_credit')->where('status', 'applied')->latest('id')->first();
        $this->assertNotNull($discount);
        $this->assertEquals(400.00, (float) $discount->calculated_discount_amount);
        $this->assertEquals(600.00, (float) $discount->discounted_product_value);
    }
}

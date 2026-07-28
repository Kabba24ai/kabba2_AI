<?php

namespace Tests\Feature\Discounts;

use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Discounts\ProductDiscount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Services\Reports\SalesReportEngineV2;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Increment 3 — reporting. A pre-tax Store Credit ORDER discount must: keep
 * gross_sales at the pre-discount base, appear as a discount, and correct the
 * line-level tax_collected (Finding 2 / Option C). Store Credit is never
 * counted as collected tender/revenue. No production writer can create a new
 * Store Credit payment.
 */
class DiscountReportingTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $employee;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::updateOrCreate(['setting_name' => 'sales_tax'], ['setting_type' => 'Product Settings', 'setting_value' => '0.0975']);
        $this->customer = Customer::create(['first_name' => 'Rep', 'last_name' => 'Cust', 'email' => 'rep-' . uniqid() . '@test.local', 'status' => 'Active']);
        $this->employee = User::create(['first_name' => 'Rep', 'last_name' => 'Clerk', 'email' => 'rep-clerk-' . uniqid() . '@test.local', 'status' => 'Active']);
        $this->store = Store::create(['store_name' => 'Rep Store']);
        $this->actingAs($this->employee);
    }

    private function orderWithProduct(float $sub, float $tax): Order
    {
        $category = ProductCategory::create(['title' => 'Cat ' . uniqid()]);
        $product = Product::create(['product_name' => 'P', 'slug' => 'p-' . uniqid(), 'product_type' => 'Rental']);
        $product->categories()->attach($category->id);

        $order = Order::create([
            'order_number' => 'R' . strtoupper(uniqid()), 'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id, 'customer_name' => 'Rep Cust',
            'subtotal' => $sub, 'tax_amount' => $tax, 'grand_total' => $sub + $tax,
        ]);
        OrderProduct::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'P',
            'price' => $sub, 'quantity' => 1, 'sub_total' => $sub, 'tax' => $tax, 'total' => $sub + $tax,
            'delivery_store_id' => $this->store->id, 'product_data' => ['product_type' => 'Rental'],
        ]);
        // Settled payment so the order is counted by the revenue engine.
        $order->payments()->create([
            'payment_method' => \App\Enums\Orders\OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'amount' => $sub + $tax,
            'status' => \App\Enums\Orders\OrderPaymentStatus::Paid->value,
        ]);
        return $order;
    }

    private function orderDiscount(Order $o, float $amount, float $taxBefore, float $taxAfter): void
    {
        ProductDiscount::create([
            'discount_type' => 'store_credit', 'calculation_type' => 'fixed_amount',
            'calculated_discount_amount' => $amount, 'target_type' => 'order', 'target_id' => $o->id,
            'customer_id' => $this->customer->id,
            'original_product_value' => $o->subtotal, 'discounted_product_value' => $o->subtotal - $amount,
            'taxable_value_before' => $o->subtotal, 'taxable_value_after' => $o->subtotal - $amount,
            'tax_before' => $taxBefore, 'tax_after' => $taxAfter,
            'applied_at' => now(), 'idempotency_key' => 'rep-' . Str::uuid(), 'status' => 'applied',
        ]);
    }

    private function kpis(): array
    {
        return app(SalesReportEngineV2::class)->kpis([
            'date_range' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]);
    }

    public function test_order_store_credit_discount_reflected_in_reporting(): void
    {
        // $1,000 base, $97.50 tax. Store Credit discount $400 → tax on $600 = $58.50.
        $o = $this->orderWithProduct(1000, 97.50);
        $this->orderDiscount($o, 400, 97.50, 58.50);

        $k = $this->kpis();
        $this->assertEqualsWithDelta(1000.0, $k['gross_sales'], 0.01, 'gross stays at pre-discount base');
        $this->assertEqualsWithDelta(400.0, $k['discounts'], 0.01, 'store credit discount is a discount');
        $this->assertEqualsWithDelta(58.50, $k['tax_collected'], 0.01, 'tax corrected to the discounted base');
        $this->assertEqualsWithDelta(600.0, $k['net_sales'], 0.01, 'net reflects the discount');
    }

    public function test_no_discount_order_reporting_unchanged(): void
    {
        $this->orderWithProduct(1000, 97.50);
        $k = $this->kpis();
        $this->assertEqualsWithDelta(1000.0, $k['gross_sales'], 0.01);
        $this->assertEqualsWithDelta(0.0, $k['discounts'], 0.01);
        $this->assertEqualsWithDelta(97.50, $k['tax_collected'], 0.01);
    }

    public function test_legacy_store_credit_ar_payment_excluded_from_revenue(): void
    {
        // A legacy StoreCredit A/R payment must NOT count as revenue/tax.
        CustomerAccount::create([
            'unique_id' => (string) Str::uuid(), 'customer_id' => $this->customer->id,
            'amount' => 500, 'balance' => 0, 'type' => 'payment', 'payment_type' => 'StoreCredit',
            'sales_tax' => 0, 'date' => now(),
        ]);
        $k = $this->kpis();
        $this->assertEqualsWithDelta(0.0, $k['gross_sales'], 0.01, 'store credit tender is not revenue');
        $this->assertEqualsWithDelta(0.0, $k['account_payments_received'], 0.01);
    }

    public function test_goodwill_and_general_categories_representable_but_zero(): void
    {
        // Structurally representable, operationally inactive → contribute nothing.
        $o = $this->orderWithProduct(1000, 97.50);
        ProductDiscount::create([
            'discount_type' => 'goodwill', 'calculation_type' => 'fixed_amount',
            'calculated_discount_amount' => 12, 'target_type' => 'order', 'target_id' => $o->id,
            'customer_id' => $this->customer->id, 'original_product_value' => 1000, 'discounted_product_value' => 988,
            'taxable_value_before' => 1000, 'taxable_value_after' => 988, 'tax_before' => 97.50, 'tax_after' => 96.33,
            'applied_at' => now(), 'idempotency_key' => 'gw-' . Str::uuid(), 'status' => 'applied',
        ]);
        // V2's store-credit discount category is store_credit-only → goodwill not folded in.
        $k = $this->kpis();
        $this->assertEqualsWithDelta(0.0, $k['discounts'], 0.01, 'goodwill not counted in the store_credit-scoped discount adjustment');
    }
}

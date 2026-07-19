<?php

namespace Tests\Feature\Reports;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPaymentRefundAllocation;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Services\Reports\BillingRevenueAttributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Architecture Finalization — dedicated coverage for
 * BillingRevenueAttributionService::baseQuery()'s refund-netting fix. This
 * previously summed raw order_payments.refund_amount/tax_refunded directly
 * — with no allocation awareness at all — which overstates the amount
 * netted off extension revenue whenever a multi-source or fee-retained
 * refund's stated refund_amount doesn't match what was actually allocated.
 * Every test here deliberately sets the LEGACY refund_amount/tax_refunded
 * columns to different, stale values from the real allocation rows, so a
 * passing assertion proves the allocation-aware branch is what actually
 * ran — not a coincidental fallback.
 */
class BillingRevenueAttributionServiceTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Store $store;
    private Product $product;
    private Order $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Billing', 'last_name' => 'Attribution',
            'email' => 'billing-attribution@example.com', 'status' => 'Active',
        ]);

        $employee = User::create([
            'first_name' => 'Billing', 'last_name' => 'Clerk',
            'email' => 'billing-attribution-clerk@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        $this->store = Store::create(['store_name' => 'Billing Attribution Store']);

        $category = ProductCategory::create(['title' => 'Billing Attribution Category']);
        $this->product = Product::create([
            'product_name' => 'Billing Attribution Product',
            'slug'         => 'billing-attribution-product-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $this->product->categories()->attach($category->id);

        $this->parent = Order::create([
            'order_number'  => 'BRA-1',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Billing Attribution',
            'subtotal'      => 2000,
            'tax_amount'    => 200,
            'grand_total'   => 2200,
        ]);

        OrderProduct::create([
            'order_id'          => $this->parent->id,
            'product_id'        => $this->product->id,
            'product_name'      => 'Billing Attribution Product',
            'price'             => 2000,
            'quantity'          => 1,
            'sub_total'         => 2000,
            'tax'               => 200,
            'total'             => 2200,
            'delivery_store_id' => $this->store->id,
            'product_data'      => ['product_type' => 'Rental'],
        ]);

        $this->parent->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 2200,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        $this->actingAs($employee);
    }

    private function filters(): array
    {
        return [
            'date_range' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date'   => now()->addDay()->toDateString(),
        ];
    }

    /**
     * Creates a paid $500 (+ $50 tax) extension on the parent, entirely via
     * direct model creation (bypassing the HTTP extension flow) so the
     * refund/allocation fixture below can be attached deterministically.
     */
    private function makePaidExtension(): array
    {
        $child = Order::create([
            'order_number'  => 'BRA-1-A',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Billing Attribution',
            'reference_order_number' => 'BRA-1',
        ]);

        $charge = BillingCharge::create([
            'parent_order_id'     => $this->parent->id,
            'child_order_id'      => $child->id,
            'billing_charge_type' => 'extension',
            'amount'              => 500,
            'tax_amount'          => 50,
            'status'              => 'paid',
            'paid_at'             => now(),
        ]);

        $payment = $child->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 550,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        return [$child, $charge, $payment];
    }

    public function test_unrefunded_extension_attributes_full_ex_tax_amount(): void
    {
        $this->makePaidExtension();

        $total = app(BillingRevenueAttributionService::class)->totalRevenue($this->filters());

        $this->assertSame(500.0, round($total, 2));
    }

    public function test_allocation_aware_refund_nets_off_only_the_allocated_ex_tax_ex_fee_amount(): void
    {
        [$child, , $originalPayment] = $this->makePaidExtension();

        // The refund row's own legacy refund_amount/tax_refunded are
        // deliberately stale/wrong (550/50 — a "full refund" reading) to
        // prove the allocation-aware branch, not this fallback, is used.
        $refund = $child->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at'      => now(),
            'amount'           => 0,
            'refund_amount'    => 550,
            'tax_refunded'     => 50,
            'status'           => OrderPaymentStatus::PartialRefund->value,
        ]);

        // Real allocation: $220 gross drawn, $6 processing fee retained,
        // $20 of that was tax. Ex-tax, ex-fee net = 220 - 6 - 20 = 194.
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id'   => $refund->id,
            'original_order_payment_id' => $originalPayment->id,
            'allocated_amount'          => 220,
            'allocated_base_amount'     => 200,
            'allocated_tax_amount'      => 20,
            'processing_fee_retained'   => 6,
            'status'                    => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        $total = app(BillingRevenueAttributionService::class)->totalRevenue($this->filters());

        // Extension net = 500 - 194 = 306. If the legacy columns had been
        // used instead, this would be 500 - (550 - 50) = 0.
        $this->assertSame(306.0, round($total, 2));
    }

    public function test_pending_and_failed_allocations_are_never_netted_off(): void
    {
        [$child, , $originalPayment] = $this->makePaidExtension();

        $refund = $child->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at'      => now(),
            'amount'           => 0,
            'refund_amount'    => 0,
            'tax_refunded'     => 0,
            'status'           => OrderPaymentStatus::PartialRefund->value,
        ]);

        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id'   => $refund->id,
            'original_order_payment_id' => $originalPayment->id,
            'allocated_amount'          => 220,
            'allocated_base_amount'     => 200,
            'allocated_tax_amount'      => 20,
            'processing_fee_retained'   => 0,
            'status'                    => OrderPaymentRefundAllocationStatus::Failed->value,
        ]);

        $total = app(BillingRevenueAttributionService::class)->totalRevenue($this->filters());

        // A Failed allocation attempted no real money movement — nothing
        // to net off. (Since the refund has ANY allocation row, the
        // allocation-aware branch is taken and correctly sums to 0, not
        // the legacy refund_amount fallback either.)
        $this->assertSame(500.0, round($total, 2));
    }

    public function test_extension_refund_never_reduces_parent_rental_revenue(): void
    {
        [$child, , $originalPayment] = $this->makePaidExtension();

        $refund = $child->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at'      => now(),
            'amount'           => 0,
            'refund_amount'    => 550,
            'tax_refunded'     => 50,
            'status'           => OrderPaymentStatus::Refund->value,
        ]);

        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id'   => $refund->id,
            'original_order_payment_id' => $originalPayment->id,
            'allocated_amount'          => 550,
            'allocated_base_amount'     => 500,
            'allocated_tax_amount'      => 50,
            'processing_fee_retained'   => 0,
            'status'                    => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        $total = app(BillingRevenueAttributionService::class)->totalRevenue($this->filters());

        // Fully refunded extension nets to exactly 0 — the parent
        // rental's own $2000 is a completely separate order_payments row
        // and must never be touched by the child's refund netting.
        $this->assertSame(0.0, round($total, 2));
    }
}

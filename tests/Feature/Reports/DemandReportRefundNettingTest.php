<?php

namespace Tests\Feature\Reports;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Services\Reports\ProductSalesRankingReport;
use App\Services\Reports\SalesByStoresReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Architecture Finalization — ProductSalesRankingReport and
 * SalesByStoresReport previously summed raw order_products.sub_total with
 * zero refund awareness: a fully-refunded order's original revenue counted
 * in full, and a partial refund was never netted out. Both now share
 * ProductSalesPerformanceEngine's netting (NetsRefundedRevenue trait):
 * fully-refunded orders excluded entirely, partial refunds proportionally
 * reduce the counted revenue.
 */
class DemandReportRefundNettingTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Store $store;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Refund', 'last_name' => 'Netting',
            'email' => 'refund-netting@example.com', 'status' => 'Active',
        ]);

        $this->store = Store::create(['store_name' => 'Netting Test Store']);

        $category = ProductCategory::create(['title' => 'Netting Test Category']);
        $this->product = Product::create([
            'product_name' => 'Netting Test Product',
            'slug'         => 'netting-test-product-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $this->product->categories()->attach($category->id);
    }

    private function makeOrder(string $orderNumber, float $subtotal): Order
    {
        $order = Order::create([
            'order_number'  => $orderNumber,
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Refund Netting',
            'subtotal'      => $subtotal,
            'grand_total'   => $subtotal,
        ]);

        OrderProduct::create([
            'order_id'          => $order->id,
            'product_id'        => $this->product->id,
            'product_name'      => 'Netting Test Product',
            'price'             => $subtotal,
            'quantity'          => 1,
            'sub_total'         => $subtotal,
            'tax'               => 0,
            'total'             => $subtotal,
            'delivery_store_id' => $this->store->id,
            'product_data'      => ['product_type' => 'Rental'],
        ]);

        return $order;
    }

    private function filters(): array
    {
        return [
            'date_range' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date'   => now()->addDay()->toDateString(),
            'payment_status' => 'paid',
        ];
    }

    public function test_fully_refunded_order_is_excluded_from_ranking_and_store_reports(): void
    {
        $order = $this->makeOrder('NET-FULL', 1000);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 1000, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at' => now(),
            'amount' => 0, 'refund_amount' => 1000,
            'status' => OrderPaymentStatus::Refund->value,
        ]);

        $ranking = app(ProductSalesRankingReport::class)->rankingData($this->filters());
        $this->assertSame(0.0, $ranking['totals']['revenue'], 'a fully-refunded order must contribute zero revenue to Product Ranking');

        $stores = app(SalesByStoresReport::class)->storeData($this->filters());
        $this->assertSame(0.0, $stores['totals']['revenue'], 'a fully-refunded order must contribute zero revenue to Sales By Stores');
    }

    public function test_partial_refund_proportionally_reduces_ranking_and_store_revenue(): void
    {
        $order = $this->makeOrder('NET-PARTIAL', 1000);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 1000, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        // Legacy-style partial refund row (no allocation rows) — exercises
        // the allocation-aware SQL's fallback-to-refund_amount branch.
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at' => now(),
            'amount' => 0, 'refund_amount' => 300,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        $ranking = app(ProductSalesRankingReport::class)->rankingData($this->filters());
        $this->assertSame(700.0, $ranking['totals']['revenue'], '1000 * (1000-300)/1000 = 700 net revenue');

        $stores = app(SalesByStoresReport::class)->storeData($this->filters());
        $this->assertSame(700.0, $stores['totals']['revenue']);
    }

    public function test_unrefunded_order_counts_full_revenue(): void
    {
        $order = $this->makeOrder('NET-NONE', 500);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $ranking = app(ProductSalesRankingReport::class)->rankingData($this->filters());
        $this->assertSame(500.0, $ranking['totals']['revenue']);
    }

    /**
     * SalesReportingService::applyFilters()'s 'paid' bucket used to
     * correlate only to the single highest-id order_payments row (MAX(id)).
     * A split-payment order whose LAST row entered didn't itself satisfy
     * the 'paid' rule was excluded entirely, even though the order was
     * genuinely, fully paid across its two rows.
     */
    public function test_split_payment_order_is_included_in_the_paid_bucket_regardless_of_row_order(): void
    {
        $order = $this->makeOrder('NET-SPLIT', 1000);
        // Cash entered first (qualifies for 'paid' outright)...
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now()->subMinute(),
            'amount' => 600, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        // ...then a LATER Account-method row, which alone would NOT satisfy
        // 'paid' (Account is excluded from that bucket). Under the OLD
        // MAX(id)-only check, this later row deciding the order's fate
        // would have excluded the whole order from the 'paid' bucket.
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Account->value,
            'payment_datetime' => now(),
            'amount' => 400, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $ranking = app(ProductSalesRankingReport::class)->rankingData($this->filters());
        $this->assertSame(1000.0, $ranking['totals']['revenue'], 'the order must be included because its EARLIER Cash row satisfies the paid bucket');
    }
}

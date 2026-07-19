<?php

namespace Tests\Feature\Reports;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Services\Reports\ProductSalesPerformanceEngine;
use App\Services\Reports\SalesReportEngineV2;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2D: the React/API reporting endpoints must reconcile EXACTLY with
 * the Blade reporting engines — one canonical financial interpretation.
 * Controlled scenario: Telehandler rental $2,000 + $500 paid extension
 * (canonical product revenue $2,500, net sales $2,500, rentals 1).
 */
class ApiSalesReportConsolidationTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $employee;
    private Store $store;
    private ProductCategory $category;
    private Product $product;
    private Order $parent;
    private string $apiBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiBase = 'http://' . config('app.domains.api') . '/api/sales-reports/v1';

        Setting::updateOrCreate(
            ['setting_name' => 'sales_tax'],
            ['setting_type' => 'General Settings', 'value_type' => 'text', 'setting_title' => 'sales_tax', 'setting_value' => '0.10']
        );

        $this->customer = Customer::create([
            'first_name' => 'Api', 'last_name' => 'Customer',
            'email' => 'api-customer@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Api', 'last_name' => 'Clerk',
            'email' => 'api-clerk@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        $this->store = Store::create(['store_name' => 'Api Store']);

        $this->category = ProductCategory::create(['title' => 'Telehandlers']);
        $this->product  = Product::create([
            'product_name' => 'Telehandler 42ft',
            'slug'         => 'telehandler-42ft-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $this->product->categories()->attach($this->category->id);

        $this->parent = Order::create([
            'order_number'  => '9001',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Api Customer',
            'subtotal'      => 2000,
            'tax_amount'    => 200,
            'grand_total'   => 2200,
        ]);

        OrderProduct::create([
            'order_id'          => $this->parent->id,
            'product_id'        => $this->product->id,
            'product_name'      => 'Telehandler 42ft',
            'price'             => 2000,
            'quantity'          => 1,
            'sub_total'         => 2000,
            'tax'               => 200,
            'total'             => 2200,
            'delivery_store_id' => $this->store->id,
            // 'product_price' included so getRevenueBreakdown() — which
            // reads its buckets from product_data JSON, not sub_total —
            // has a real nonzero number to net against in the Phase 4B
            // refund-netting test below.
            'product_data'      => ['product_type' => 'Rental', 'product_price' => 2000],
        ]);

        // TWO payment rows on purpose: the legacy unscoped order_payments
        // join fanned every product line out per payment row — the canonical
        // engines must count this order once
        $this->parent->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 1000,
            'status'           => OrderPaymentStatus::PartialPayment->value,
        ]);
        $this->parent->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 2200,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        $this->actingAs($this->employee);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function createPaidExtension(string $base = '500.00', string $paid = '550.00'): array
    {
        $this->postJson(
            route('admin.order-management.orders.extension.store', ['unique_id' => $this->parent->unique_id]),
            [
                'description'        => 'Extension',
                'base_amount'        => $base,
                'add_tax'            => true,
                'responsible_person' => $this->employee->id,
            ]
        )->assertOk();

        $child  = Order::where('order_number', 'like', '9001-%')->latest('id')->firstOrFail();
        $charge = BillingCharge::where('child_order_id', $child->id)->firstOrFail();

        $this->post(route('admin.dashboard.paymentstore'), [
            'source'                   => 'crm',
            'type'                     => 'extension',
            'billing_charge_unique_id' => $charge->unique_id,
            'customer_id'              => $this->customer->id,
            'amount'                   => $paid,
            'payment_type'             => 'Cash',
            'responsible_person'       => $this->employee->id,
        ])->assertRedirect();

        return [$child, $charge];
    }

    private function window(): array
    {
        return [now()->subDay()->toDateString(), now()->addDay()->toDateString()];
    }

    private function apiGet(string $path, array $query = [])
    {
        return $this->getJson($this->apiBase . $path . '?' . http_build_query($query));
    }

    private function engineFilters(): array
    {
        [$start, $end] = $this->window();

        return [
            'date_range'     => 'custom',
            'start_date'     => $start,
            'end_date'       => $end,
            'payment_status' => 'paid_and_account',
        ];
    }

    private function customQuery(array $extra = []): array
    {
        [$start, $end] = $this->window();

        return array_merge(['dateRange' => 'custom', 'startDate' => $start, 'endDate' => $end], $extra);
    }

    // ── Reconciliation: API === Blade engines ───────────────────────────

    public function test_top_products_reconciles_with_product_performance_engine(): void
    {
        $this->createPaidExtension();

        $response = $this->apiGet('/top-products', $this->customQuery())->assertOk()->json();

        $engineRows = app(ProductSalesPerformanceEngine::class)
            ->productData(array_merge($this->engineFilters(), ['limit' => 'all']))['rows'];
        $engineRow = collect($engineRows)->firstWhere('product_id', $this->product->id);

        $apiRow = collect($response['products'])->firstWhere('id', $this->product->id);

        $this->assertSame(2500.0, (float) $apiRow['total_sales'], 'rental 2000 + extension 500, no fan-out doubling');
        $this->assertSame((float) $engineRow['revenue'], (float) $apiRow['total_sales']);
        $this->assertSame(1, (int) $apiRow['order_count']);
        $this->assertSame(2500.0, (float) $response['total_sales']);
        $this->assertSame(['id', 'name', 'total_sales', 'order_count'], array_keys($apiRow), 'contract preserved');
    }

    public function test_top_categories_reconciles_with_category_engine(): void
    {
        $this->createPaidExtension();

        $response = $this->apiGet('/top-categories', $this->customQuery())->assertOk()->json();

        $apiRow = collect($response['categories'])->firstWhere('id', $this->category->id);

        $this->assertSame(2500.0, (float) $apiRow['total_sales']);
        $this->assertSame(1, (int) $apiRow['order_count']);
        $this->assertSame(2500.0, (float) $response['total_sales']);
    }

    public function test_sales_summary_reconciles_with_engine_kpis(): void
    {
        $this->createPaidExtension();

        $response = $this->apiGet('/sales-summary', $this->customQuery())->assertOk()->json();
        $kpis     = app(SalesReportEngineV2::class)->kpis($this->engineFilters());

        $this->assertSame(round((float) $kpis['gross_sales'], 2), round((float) $response['totalGrossSales'], 2));
        $this->assertSame(round((float) $kpis['net_sales'], 2), round((float) $response['totalNetSales'], 2));
        $this->assertSame(round((float) $kpis['tax_collected'], 2), round((float) $response['totalTax'], 2));
        $this->assertSame(round((float) $kpis['refunds'], 2), round((float) $response['totalRefunds'], 2));
        $this->assertSame((int) $kpis['transaction_count'], (int) $response['transactionCount']);

        // 2000 rental + 500 extension, exactly once; 1 transaction; 1 item
        $this->assertSame(2500.0, (float) $response['totalNetSales']);
        $this->assertSame(1, (int) $response['transactionCount']);
        $this->assertSame(1, (int) $response['itemsSold']);
        $this->assertSame(
            ['totalGrossSales', 'totalDiscounts', 'totalRefunds', 'totalNetSales', 'totalTax',
             'transactionCount', 'itemsSold', 'averageSaleValue', 'averageItemsPerSale'],
            array_keys($response),
            'contract preserved'
        );
    }

    public function test_rolling_30_days_series_sums_to_engine_net_sales(): void
    {
        $this->createPaidExtension();

        $rows = $this->apiGet('/rolling-30-days')->assertOk()->json();

        $this->assertCount(60, $rows);
        $this->assertSame(['date', 'sales', 'period'], array_keys($rows[0]), 'contract preserved');

        $currentSum = collect($rows)->where('period', 'current')->sum('sales');

        $engineNet = app(SalesReportEngineV2::class)->netSalesForPeriod(
            now()->subDays(29)->toDateString(),
            now()->toDateString(),
            ['payment_status' => 'paid_and_account']
        );

        $this->assertSame(round($engineNet, 2), round($currentSum, 2), 'daily series reconciles with engine');
        $this->assertSame(2500.0, round($currentSum, 2));
    }

    public function test_refunds_report_reconciles_and_counts_partial_refunds(): void
    {
        [$child] = $this->createPaidExtension();

        // A partial refund — the legacy endpoint (status IN ('Refunded')) missed these entirely
        $child->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at'      => now(),
            'amount'           => 0,
            'refund_amount'    => 110,
            'tax_refunded'     => 10,
            'status'           => OrderPaymentStatus::PartialRefund->value,
        ]);

        // And a full refund on the parent
        $this->parent->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at'      => now(),
            'amount'           => 0,
            'refund_amount'    => 2200,
            'tax_refunded'     => 200,
            'status'           => OrderPaymentStatus::Refund->value,
        ]);

        $response = $this->apiGet('/refunds-report', $this->customQuery())->assertOk()->json();

        $this->assertSame(2310.0, (float) $response['totalRefundAmount'], '2200 full + 110 partial');
        $this->assertSame(2, (int) $response['refundTransactionCount']);
        $this->assertSame(1, (int) $response['fullRefunds']);
        $this->assertSame(1, (int) $response['partialRefunds'], 'partial refunds are no longer invisible');
        $this->assertSame('Not specified', $response['refundsByReason'][0]['reason']);
    }

    /**
     * Refund Project Final Phase — Refund Consumer Cleanup left these three
     * endpoints with a dead (unreachable) refund branch. Payment Architecture
     * Finalization (Phase 4B) made refund netting real — see
     * SalesReportController's class docblock and the dedicated
     * ApiSalesReportRefundNettingTest suite for the full scenario matrix.
     * This test now proves the opposite of what it originally asserted: a
     * full refund on the parent order's $2,200 Paid payment MUST zero out
     * all three endpoints' numbers for that order, not leave them
     * unaffected. (The original assertion — that a refund changed nothing —
     * was itself proof of the bug being fixed, not a spec to preserve.)
     */
    public function test_revenue_breakdown_tax_and_payments_and_product_details_are_zeroed_out_by_a_full_refund(): void
    {
        $before = [
            'revenue' => $this->apiGet('/revenue-breakdown', $this->customQuery())->assertOk()->json(),
            'tax'     => $this->apiGet('/tax-and-payments', $this->customQuery())->assertOk()->json(),
            'product' => $this->apiGet('/product-sales-details', $this->customQuery())->assertOk()->json(),
        ];
        $this->assertSame(2000.0, array_sum($before['revenue']), 'sanity: pre-refund revenue is counted (rentalRevenue from product_data.product_price)');
        $this->assertSame(200.0, (float) $before['tax']['salesTaxCollected'], 'sanity: pre-refund tax is counted');
        $this->assertNotEmpty($before['product'], 'sanity: pre-refund product line is present');

        $this->parent->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at'      => now(),
            'amount'           => 0,
            'refund_amount'    => 2200,
            'tax_refunded'     => 200,
            'status'           => OrderPaymentStatus::Refund->value,
        ]);

        $after = [
            'revenue' => $this->apiGet('/revenue-breakdown', $this->customQuery())->assertOk()->json(),
            'tax'     => $this->apiGet('/tax-and-payments', $this->customQuery())->assertOk()->json(),
            'product' => $this->apiGet('/product-sales-details', $this->customQuery())->assertOk()->json(),
        ];

        $this->assertSame(0.0, array_sum($after['revenue']), 'a fully-refunded order must contribute zero revenue, not the pre-refund $2,200');
        $this->assertSame(0.0, (float) $after['tax']['salesTaxCollected']);
        $this->assertEmpty($after['product'], 'the product line must disappear entirely, not remain at its pre-refund value');
    }

    public function test_discounts_report_returns_real_numbers(): void
    {
        $this->parent->update(['discount_amount' => 150]);

        $response = $this->apiGet('/discounts-report', $this->customQuery())->assertOk()->json();

        $this->assertSame(150.0, (float) $response['totalDiscounts'], 'previously hardcoded to zero');
        $this->assertSame(1, (int) $response['transactionsWithDiscounts']);
        $this->assertSame(150.0, (float) $response['averageDiscountPerTransaction']);
        $this->assertSame(
            ['totalDiscounts', 'discountPercentage', 'transactionsWithDiscounts', 'averageDiscountPerTransaction'],
            array_keys($response),
            'contract preserved'
        );
    }

    public function test_unpaid_extension_is_excluded_everywhere(): void
    {
        // Create but do NOT pay
        $this->postJson(
            route('admin.order-management.orders.extension.store', ['unique_id' => $this->parent->unique_id]),
            ['description' => 'Ext', 'base_amount' => '500.00', 'add_tax' => true,
             'responsible_person' => $this->employee->id]
        )->assertOk();

        $summary = $this->apiGet('/sales-summary', $this->customQuery())->assertOk()->json();
        $this->assertSame(2000.0, (float) $summary['totalNetSales']);

        $products = $this->apiGet('/top-products', $this->customQuery())->assertOk()->json();
        $this->assertSame(2000.0, (float) collect($products['products'])->firstWhere('id', $this->product->id)['total_sales']);
    }

    public function test_include_previous_reports_prior_period_from_engine(): void
    {
        $this->createPaidExtension();

        $response = $this->apiGet('/top-products', [
            'dateRange' => 'this_week', 'include_previous' => '1',
        ])->assertOk()->json();

        $row = collect($response['products'])->firstWhere('id', $this->product->id);
        $this->assertArrayHasKey('previous_total_sales', $row, 'contract preserved');
        $this->assertSame(0.0, (float) $row['previous_total_sales'], 'no sales seeded last week');
    }
}

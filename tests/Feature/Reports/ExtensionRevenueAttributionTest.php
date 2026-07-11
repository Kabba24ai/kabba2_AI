<?php

namespace Tests\Feature\Reports;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Services\Reports\PaymentReconciliationLedger;
use App\Services\Reports\ProductSalesPerformanceEngine;
use App\Services\Reports\ProductSalesRankingReport;
use App\Services\Reports\SalesByStoresReport;
use App\Services\Reports\SalesReportEngineV2;
use App\Services\Reports\SalesTaxReportEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Extension Revenue Attribution (Phase 2B): a paid extension's revenue is
 * financially attributed to the PARENT rental's product, category, and store
 * — without becoming another rental. Controlled example:
 *
 *   42' Telehandler rental $2,000 (paid) + extension $500 (paid)
 *   → product/category/store revenue $2,500, rental count 1.
 *
 * The child order keeps its own operational identity (order 3153-A,
 * Extension Charge, no order_products), and the Sales Tax Report and
 * Payment Reconciliation Ledger are untouched by this phase.
 */
class ExtensionRevenueAttributionTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $employee;
    private Store $store;
    private ProductCategory $category;
    private Product $product;
    private Equipment $equipment;
    private Order $parent;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create([
            'setting_type' => 'General Settings', 'value_type' => 'text',
            'setting_name' => 'sales_tax', 'setting_title' => 'sales_tax', 'setting_value' => '0.10',
        ]);

        $this->customer = Customer::create([
            'first_name' => 'Attr', 'last_name' => 'Customer',
            'email' => 'attr@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Attr', 'last_name' => 'Clerk',
            'email' => 'attr-clerk@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        $this->store = Store::create(['store_name' => 'Attribution Store']);

        $this->category = ProductCategory::create(['title' => 'Telehandlers']);
        $this->product  = Product::create([
            'product_name' => 'Telehandler 42ft',
            'slug'         => 'telehandler-42ft-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $this->product->categories()->attach($this->category->id);

        $this->equipment = Equipment::create([
            'unique_id' => 'eq-attr', 'equipment_name' => 'JCB TH-2', 'equipment_id' => 'JCB-TH-2',
            'brand' => 'JCB', 'serial_number' => 'SN-ATTR', 'product_category_id' => $this->category->id,
        ]);

        $this->parent = $this->makeRentalOrder('3153', $this->product, 2000, $this->store);

        $this->actingAs($this->employee);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function makeRentalOrder(string $number, Product $product, float $subtotal, Store $store): Order
    {
        $order = Order::create([
            'order_number'  => $number,
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Attr Customer',
            'subtotal'      => $subtotal,
            'tax_amount'    => round($subtotal * 0.10, 2),
            'grand_total'   => round($subtotal * 1.10, 2),
        ]);

        OrderProduct::create([
            'order_id'          => $order->id,
            'product_id'        => $product->id,
            'product_name'      => $product->product_name,
            'price'             => $subtotal,
            'quantity'          => 1,
            'sub_total'         => $subtotal,
            'tax'               => round($subtotal * 0.10, 2),
            'total'             => round($subtotal * 1.10, 2),
            'equipment_id'      => $this->equipment->id,
            'delivery_store_id' => $store->id,
            'product_data'      => ['product_type' => 'Rental'],
        ]);

        $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => round($subtotal * 1.10, 2),
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        return $order;
    }

    private function createExtension(Order $parent, string $amount = '500.00'): array
    {
        $this->postJson(
            route('admin.order-management.orders.extension.store', ['unique_id' => $parent->unique_id]),
            [
                'description'        => 'One extra week',
                'base_amount'        => $amount,
                'add_tax'            => true,
                'responsible_person' => $this->employee->id,
            ]
        )->assertOk()->assertJson(['success' => true]);

        $child  = Order::where('order_number', 'like', $parent->order_number . '-%')->latest('id')->firstOrFail();
        $charge = BillingCharge::where('child_order_id', $child->id)->firstOrFail();

        return [$child, $charge];
    }

    private function payExtension(BillingCharge $charge, string $amount = '550.00'): void
    {
        $this->post(route('admin.dashboard.paymentstore'), [
            'source'                   => 'crm',
            'type'                     => 'extension',
            'billing_charge_unique_id' => $charge->unique_id,
            'customer_id'              => $this->customer->id,
            'amount'                   => $amount,
            'payment_type'             => 'Cash',
            'responsible_person'       => $this->employee->id,
        ])->assertRedirect();
    }

    private function filters(array $extra = []): array
    {
        return array_merge([
            'date_range' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date'   => now()->addDay()->toDateString(),
        ], $extra);
    }

    private function productRow(array $filters = []): ?array
    {
        $rows = app(ProductSalesPerformanceEngine::class)->productData($this->filters($filters))['rows'];

        return collect($rows)->firstWhere('product_id', $this->product->id);
    }

    private function categoryRow(): ?array
    {
        $rows = app(ProductSalesPerformanceEngine::class)->categoryData($this->filters())['rows'];

        return collect($rows)->firstWhere('category_id', $this->category->id);
    }

    // ── Core validation: $2,000 rental + $500 extension = $2,500 / 1 rental ──

    public function test_product_revenue_includes_extension_without_second_rental(): void
    {
        [, $charge] = $this->createExtension($this->parent);
        $this->payExtension($charge);

        $row = $this->productRow();
        $this->assertSame(2500.0, $row['revenue']);
        $this->assertSame(1, $row['qty']);

        $kpis = app(ProductSalesPerformanceEngine::class)->buildKpis($this->filters());
        $this->assertSame(2500.0, $kpis['total_revenue']);
        $this->assertSame(1, $kpis['txn_count']);
    }

    public function test_category_revenue_includes_extension(): void
    {
        [, $charge] = $this->createExtension($this->parent);
        $this->payExtension($charge);

        $row = $this->categoryRow();
        $this->assertSame(2500.0, $row['revenue']);
        $this->assertSame(1, $row['qty']);
    }

    public function test_store_revenue_includes_extension_without_extra_transaction(): void
    {
        [, $charge] = $this->createExtension($this->parent);
        $this->payExtension($charge);

        $data = app(SalesByStoresReport::class)->storeData($this->filters());
        $storeRow = collect($data['stores'])->firstWhere('name', 'Attribution Store');

        $this->assertSame(2500.0, $storeRow['revenue']);
        $this->assertSame(1, $storeRow['transactions']);
    }

    public function test_product_ranking_includes_extension_revenue(): void
    {
        [, $charge] = $this->createExtension($this->parent);
        $this->payExtension($charge);

        $data = app(ProductSalesRankingReport::class)->rankingData($this->filters());
        $row  = collect($data['products'])->firstWhere('product_id', $this->product->id);

        $this->assertSame(2500.0, $row['revenue']);
        $this->assertSame(1, $row['qty_sold']);
    }

    // ── Lifecycle states ────────────────────────────────────────────────

    public function test_unpaid_extension_adds_nothing(): void
    {
        $this->createExtension($this->parent);

        $this->assertSame(2000.0, $this->productRow()['revenue']);
    }

    public function test_refunded_extension_reverts_product_revenue(): void
    {
        [$child, $charge] = $this->createExtension($this->parent);
        $this->payExtension($charge);

        $child->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at'      => now(),
            'amount'           => 0,
            'refund_amount'    => 550,
            'tax_refunded'     => 50,
            'status'           => OrderPaymentStatus::Refund->value,
        ]);

        $this->assertSame(2000.0, $this->productRow()['revenue']);
    }

    public function test_extension_on_quiet_period_still_surfaces_under_product(): void
    {
        // Parent rented last month; extension paid this period. The product has
        // no demand rows in the window, but the attributed revenue must appear.
        $this->parent->update(['order_date' => now()->subMonth()->toDateString()]);
        OrderProduct::where('order_id', $this->parent->id)->first(); // parent line untouched

        [, $charge] = $this->createExtension($this->parent);
        $this->payExtension($charge);

        $row = $this->productRow();
        $this->assertSame(500.0, $row['revenue']);
        $this->assertSame(0, $row['qty']);
    }

    // ── Filter behavior (Product/Category Comparison fix) ──────────────

    public function test_engine_v2_product_filter_scopes_extension_to_parent_product(): void
    {
        // Second product family with its own rental, no extension
        $otherCategory = ProductCategory::create(['title' => 'Excavators']);
        $otherProduct  = Product::create([
            'product_name' => 'Mini Excavator',
            'slug'         => 'mini-excavator-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $otherProduct->categories()->attach($otherCategory->id);
        $this->makeRentalOrder('4200', $otherProduct, 1000, $this->store);

        [, $charge] = $this->createExtension($this->parent);
        $this->payExtension($charge);

        $engine = app(SalesReportEngineV2::class);
        $f      = $this->filters();

        // Telehandler-filtered net sales include its extension: 2000 + 500
        $telehandler = $engine->netSalesForPeriod($f['start_date'], $f['end_date'], array_merge($f, ['product' => $this->product->id]));
        $this->assertSame(2500.0, round($telehandler, 2));

        // Excavator-filtered net sales must NOT leak the Telehandler extension
        $excavator = $engine->netSalesForPeriod($f['start_date'], $f['end_date'], array_merge($f, ['product' => $otherProduct->id]));
        $this->assertSame(1000.0, round($excavator, 2));

        // Category comparison: same behavior by category
        $telehandlers = $engine->netSalesForPeriod($f['start_date'], $f['end_date'], array_merge($f, ['category' => $this->category->id]));
        $this->assertSame(2500.0, round($telehandlers, 2));

        $excavators = $engine->netSalesForPeriod($f['start_date'], $f['end_date'], array_merge($f, ['category' => $otherCategory->id]));
        $this->assertSame(1000.0, round($excavators, 2));

        // Unfiltered totals unchanged by the scoping (3000 + 500 once)
        $all = $engine->netSalesForPeriod($f['start_date'], $f['end_date'], $f);
        $this->assertSame(3500.0, round($all, 2));
    }

    public function test_product_performance_store_filter_respects_attribution(): void
    {
        $otherStore = Store::create(['store_name' => 'Other Store']);

        [, $charge] = $this->createExtension($this->parent);
        $this->payExtension($charge);

        $this->assertSame(2500.0, $this->productRow(['store' => $this->store->id])['revenue']);
        $this->assertNull($this->productRow(['store' => $otherStore->id]));
    }

    public function test_accessory_line_never_absorbs_extension_revenue(): void
    {
        // Parent whose FIRST (lowest-id) line is a retail accessory; the
        // rental line comes second. Attribution must pick the rental line.
        $accessory = Product::create([
            'product_name' => 'Safety Harness',
            'slug'         => 'safety-harness-' . uniqid(),
            'product_type' => 'Retail',
        ]);

        $mixedParent = Order::create([
            'order_number'  => '5100',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Attr Customer',
            'subtotal'      => 1150,
            'tax_amount'    => 115,
            'grand_total'   => 1265,
        ]);

        OrderProduct::create([
            'order_id' => $mixedParent->id, 'product_id' => $accessory->id,
            'product_name' => 'Safety Harness', 'price' => 150, 'quantity' => 1,
            'sub_total' => 150, 'tax' => 15, 'total' => 165,
            'delivery_store_id' => $this->store->id,
            'product_data' => ['product_type' => 'Retail'],
        ]);
        OrderProduct::create([
            'order_id' => $mixedParent->id, 'product_id' => $this->product->id,
            'product_name' => 'Telehandler 42ft', 'price' => 1000, 'quantity' => 1,
            'sub_total' => 1000, 'tax' => 100, 'total' => 1100,
            'delivery_store_id' => $this->store->id,
            'product_data' => ['product_type' => 'Rental'],
        ]);
        $mixedParent->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'amount' => 1265,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        [, $charge] = $this->createExtension($mixedParent, '300.00');
        $this->payExtension($charge, '330.00');

        $rows = app(ProductSalesPerformanceEngine::class)->productData($this->filters())['rows'];
        $telehandler = collect($rows)->firstWhere('product_id', $this->product->id);
        $harness     = collect($rows)->firstWhere('product_id', $accessory->id);

        // Telehandler: 2000 (setUp parent) + 1000 (mixed parent line) + 300 (extension)
        $this->assertSame(3300.0, $telehandler['revenue']);
        // The retail accessory keeps only its own line revenue
        $this->assertSame(150.0, $harness['revenue']);
    }

    // ── Operational identity + scope protection ────────────────────────

    public function test_no_operational_records_are_duplicated(): void
    {
        [$child, $charge] = $this->createExtension($this->parent);
        $this->payExtension($charge);

        $this->assertSame(0, $child->products()->count(), 'no order_products copied');
        $this->assertSame(1, $this->parent->products()->count(), 'parent lines unchanged');
        $this->assertSame(1, BillingCharge::where('child_order_id', $child->id)->count());
        $this->assertSame('3153-A', $child->order_number, 'operational identity retained');
        $this->assertSame(
            1,
            OrderProduct::where('equipment_id', $this->equipment->id)->count(),
            'no duplicated equipment assignment'
        );
    }

    public function test_sales_tax_report_and_ledger_are_unchanged_by_attribution(): void
    {
        [, $charge] = $this->createExtension($this->parent);
        $this->payExtension($charge);

        // Sales Tax Report: rental 2000+200 and extension 500+50, each once
        $engine  = app(SalesTaxReportEngine::class);
        $f       = $this->filters();
        $allRows = $engine->salesRows($f)
            ->concat($engine->refundRows($f))
            ->concat($engine->accountRows($f))
            ->concat($engine->billingRows($f))
            ->values();
        $taxable = $allRows->filter(fn ($r) => $r->tax_amount != 0);

        $this->assertSame(2500.0, round($taxable->sum(fn ($r) => $r->subtotal), 2));
        $this->assertSame(250.0, round($taxable->sum(fn ($r) => $r->tax_amount), 2));

        // Payment Reconciliation Ledger: 2200 rental + 550 extension, once each
        $ledger = collect(app(PaymentReconciliationLedger::class)->rows($f));
        $this->assertSame(2750.0, round($ledger->sum(fn ($r) => (float) ($r->grand_total ?? 0)), 2));
    }
}

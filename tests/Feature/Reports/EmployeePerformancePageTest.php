<?php

namespace Tests\Feature\Reports;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Employee Performance report — full HTTP page render.
 *
 * Regression trap: the index view previously @include'd a
 * `_kpi_cards` Blade partial that was never committed, guarded by
 * @if (!empty($data['employees'])). With NO employee data the include was
 * skipped and the page returned 200 — which is exactly how the defect
 * shipped — while ANY employee with an attributed order made every full
 * page load throw ViewException (500). This test renders the page WITH
 * populated employee data, so a reintroduced server-side include of a
 * missing partial fails immediately. KPI cards are rendered exclusively
 * by the page's buildKpiCards() JavaScript from the embedded report data.
 */
class EmployeePerformancePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_with_populated_employee_data(): void
    {
        $admin = User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->actingAs($admin);

        $seller = User::create([
            'first_name' => 'Alice', 'last_name' => 'Seller', 'employee_code' => '07',
            'email' => 'alice-page@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        $customer = Customer::create([
            'first_name' => 'Page', 'last_name' => 'Customer',
            'email' => 'page-customer@example.com', 'status' => 'Active',
        ]);

        $store = Store::create(['store_name' => 'Page Store']);

        $category = ProductCategory::create(['title' => 'Telehandlers']);
        $product  = Product::create([
            'product_name' => 'Telehandler 42ft',
            'slug'         => 'telehandler-42ft-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $product->categories()->attach($category->id);

        // An order attributed to the seller INSIDE the default MTD window —
        // this is the exact condition that used to reach the missing partial
        $order = Order::create([
            'order_number'  => 'EP-PAGE-1',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $customer->id,
            'customer_name' => 'Page Customer',
            'subtotal'      => 1000, 'tax_amount' => 100, 'grand_total' => 1100,
        ]);
        // Order::creating may overwrite creator attribution with the acting user
        $order->forceFill(['created_by_id' => $seller->id, 'created_by_type' => User::class])->saveQuietly();

        OrderProduct::create([
            'order_id' => $order->id, 'product_id' => $product->id,
            'product_name' => 'Telehandler 42ft', 'price' => 1000, 'quantity' => 1,
            'sub_total' => 1000, 'tax' => 100, 'total' => 1100,
            'delivery_store_id' => $store->id,
            'product_data' => ['product_type' => 'Rental'],
        ]);

        $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 1100,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        $response = $this->get(route('admin.reports.sales-reports.employee-performance.index'))
            ->assertOk();

        $html = $response->getContent();

        // Report shell present
        $this->assertStringContainsString('Employee Performance', $html);
        $this->assertStringContainsString('id="kpi-section"', $html);
        $this->assertStringContainsString('id="chart-revenue"', $html);

        // The embedded initial data the JavaScript renderer consumes —
        // the seller's row must be in it with a populated revenue figure
        $this->assertStringContainsString('Alice Seller', $html);
        $this->assertStringContainsString('"qualified_revenue_raw"', $html);

        // The removed server-side include never comes back
        $this->assertStringNotContainsString('employee_performance._kpi_cards', $html);

        // Initial chart render is deferred to DOMContentLoaded — the Vite
        // module bundle that defines window.ApexCharts is guaranteed to run
        // first, so no parse-time "ApexCharts is not defined" regression.
        $this->assertStringContainsString(
            "document.addEventListener('DOMContentLoaded', () => {",
            $html,
        );
        $this->assertStringNotContainsString(
            "    if (reportData.employees?.length) {\n        renderAll(reportData);\n    }\n\n})();",
            $html,
            'Initial renderAll must not execute at inline-script parse time.',
        );

        // The AJAX Run Report path is untouched
        $this->assertStringContainsString('renderAll(json.data)', $html);

        // No KPI icon carries truncated SVG path data (the "...'" literals
        // that used to throw malformed-<path> console errors)
        $this->assertStringNotContainsString("...')", $html);
    }
}

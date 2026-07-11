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
use App\Services\Reports\EmployeePerformanceEngine;
use App\Services\Reports\SalesReportEngineV2;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Employee Performance extension attribution (Phase 2C).
 *
 * Extension revenue credits ONLY billing_charges.responsible_person_id —
 * the employee explicitly selected during extension creation. Controlled
 * example: A rents $1,000 + $200 extension, B rents $800 + $100 extension,
 * plus a $50 unassigned legacy extension:
 *
 *   Employee A: $1,200   Employee B: $900   Company: $2,150
 *
 * A never receives B's $100; unassigned revenue credits no employee but
 * stays in company totals.
 */
class EmployeeExtensionAttributionTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $employeeA;
    private User $employeeB;
    private Store $store;
    private Product $product;
    private Order $orderA;
    private Order $orderB;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create([
            'setting_type' => 'General Settings', 'value_type' => 'text',
            'setting_name' => 'sales_tax', 'setting_title' => 'sales_tax', 'setting_value' => '0.10',
        ]);

        $this->customer = Customer::create([
            'first_name' => 'Emp', 'last_name' => 'Customer',
            'email' => 'emp-customer@example.com', 'status' => 'Active',
        ]);

        $this->employeeA = User::create([
            'first_name' => 'Alice', 'last_name' => 'Seller',
            'email' => 'alice@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->employeeB = User::create([
            'first_name' => 'Bob', 'last_name' => 'Seller',
            'email' => 'bob@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        $this->store = Store::create(['store_name' => 'Emp Store']);

        $category = ProductCategory::create(['title' => 'Telehandlers']);
        $this->product = Product::create([
            'product_name' => 'Telehandler 42ft',
            'slug'         => 'telehandler-42ft-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $this->product->categories()->attach($category->id);

        $this->orderA = $this->makeRentalOrder('8001', 1000, $this->employeeA);
        $this->orderB = $this->makeRentalOrder('8002', 800, $this->employeeB);

        $this->actingAs($this->employeeA);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function makeRentalOrder(string $number, float $subtotal, User $creator): Order
    {
        $order = Order::create([
            'order_number'    => $number,
            'order_date'      => now()->toDateString(),
            'customer_id'     => $this->customer->id,
            'customer_name'   => 'Emp Customer',
            'subtotal'        => $subtotal,
            'tax_amount'      => round($subtotal * 0.10, 2),
            'grand_total'     => round($subtotal * 1.10, 2),
            'created_by_id'   => $creator->id,
            'created_by_type' => User::class,
        ]);

        // Order::creating may overwrite creator attribution with the acting user
        $order->forceFill(['created_by_id' => $creator->id, 'created_by_type' => User::class])->saveQuietly();

        OrderProduct::create([
            'order_id'          => $order->id,
            'product_id'        => $this->product->id,
            'product_name'      => 'Telehandler 42ft',
            'price'             => $subtotal,
            'quantity'          => 1,
            'sub_total'         => $subtotal,
            'tax'               => round($subtotal * 0.10, 2),
            'total'             => round($subtotal * 1.10, 2),
            'delivery_store_id' => $this->store->id,
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

    private function createExtension(Order $parent, User $responsible, string $base): array
    {
        $this->postJson(
            route('admin.order-management.orders.extension.store', ['unique_id' => $parent->unique_id]),
            [
                'description'        => 'Extension',
                'base_amount'        => $base,
                'add_tax'            => true,
                'responsible_person' => $responsible->id,
            ]
        )->assertOk()->assertJson(['success' => true]);

        $child  = Order::where('order_number', 'like', $parent->order_number . '-%')->latest('id')->firstOrFail();
        $charge = BillingCharge::where('child_order_id', $child->id)->firstOrFail();

        return [$child, $charge];
    }

    private function payExtension(BillingCharge $charge, string $amount): void
    {
        $this->post(route('admin.dashboard.paymentstore'), [
            'source'                   => 'crm',
            'type'                     => 'extension',
            'billing_charge_unique_id' => $charge->unique_id,
            'customer_id'              => $this->customer->id,
            'amount'                   => $amount,
            'payment_type'             => 'Cash',
            'responsible_person'       => $charge->responsible_person_id,
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

    private function employeeRevenue(User $employee, array $extra = []): float
    {
        $kpis = app(SalesReportEngineV2::class)->kpis($this->filters(array_merge([
            'employee_id'    => $employee->id,
            'payment_status' => 'paid_and_account',
        ], $extra)));

        return round((float) $kpis['net_sales'], 2);
    }

    private function companyRevenue(): float
    {
        $kpis = app(SalesReportEngineV2::class)->kpis($this->filters(['payment_status' => 'paid_and_account']));

        return round((float) $kpis['net_sales'], 2);
    }

    /** Standard scenario: A +$200 ext, B +$100 ext, $50 unassigned legacy ext. */
    private function seedControlledScenario(): array
    {
        [, $chargeA] = $this->createExtension($this->orderA, $this->employeeA, '200.00');
        $this->payExtension($chargeA, '220.00');

        [, $chargeB] = $this->createExtension($this->orderB, $this->employeeB, '100.00');
        $this->payExtension($chargeB, '110.00');

        // Unassigned legacy extension: created normally, then the responsible
        // person is nulled the way a legacy charge would present
        [, $chargeU] = $this->createExtension($this->orderA, $this->employeeA, '50.00');
        $this->payExtension($chargeU, '55.00');
        DB::table('billing_charges')->where('id', $chargeU->id)
            ->update(['responsible_person_id' => null, 'responsible_person_name' => null]);

        return [$chargeA, $chargeB, $chargeU];
    }

    // ── Controlled validation ───────────────────────────────────────────

    public function test_extension_revenue_credits_only_the_responsible_employee(): void
    {
        $this->seedControlledScenario();

        $this->assertSame(1200.0, $this->employeeRevenue($this->employeeA), 'A: 1000 rental + 200 own extension');
        $this->assertSame(900.0, $this->employeeRevenue($this->employeeB), 'B: 800 rental + 100 own extension');
    }

    public function test_unassigned_extension_credits_no_employee_but_stays_in_company_total(): void
    {
        $this->seedControlledScenario();

        // Company: 1000 + 800 + 200 + 100 + 50 — each exactly once
        $this->assertSame(2150.0, $this->companyRevenue());

        // Sum of employee-attributed + unassigned = company, exactly once
        $employeeSum = $this->employeeRevenue($this->employeeA) + $this->employeeRevenue($this->employeeB);
        $this->assertSame(2150.0, round($employeeSum + 50.0, 2));
    }

    public function test_employee_with_no_extensions_gets_no_extension_revenue(): void
    {
        // Only A has an extension; B must stay at rental revenue exactly
        [, $chargeA] = $this->createExtension($this->orderA, $this->employeeA, '200.00');
        $this->payExtension($chargeA, '220.00');

        $this->assertSame(1200.0, $this->employeeRevenue($this->employeeA));
        $this->assertSame(800.0, $this->employeeRevenue($this->employeeB));
    }

    public function test_extension_only_employee_appears_with_their_revenue(): void
    {
        $carol = User::create([
            'first_name' => 'Carol', 'last_name' => 'ExtOnly',
            'email' => 'carol@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        [, $charge] = $this->createExtension($this->orderA, $carol, '150.00');
        $this->payExtension($charge, '165.00');

        $this->assertSame(150.0, $this->employeeRevenue($carol));

        $roster = app(EmployeePerformanceEngine::class)->availableEmployees()->pluck('id');
        $this->assertTrue($roster->contains($carol->id), 'extension-only employee appears in the roster');
    }

    // ── Lifecycle states ────────────────────────────────────────────────

    public function test_pay_later_extension_credits_nothing_until_paid(): void
    {
        [, $charge] = $this->createExtension($this->orderA, $this->employeeA, '200.00');

        $this->assertSame(1000.0, $this->employeeRevenue($this->employeeA));

        $this->payExtension($charge, '220.00');
        $this->assertSame(1200.0, $this->employeeRevenue($this->employeeA));
    }

    public function test_fully_refunded_extension_nets_employee_to_zero(): void
    {
        [$child, $charge] = $this->createExtension($this->orderA, $this->employeeA, '200.00');
        $this->payExtension($charge, '220.00');

        $child->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at'      => now(),
            'amount'           => 0,
            'refund_amount'    => 220,
            'tax_refunded'     => 20,
            'status'           => OrderPaymentStatus::Refund->value,
        ]);

        $this->assertSame(1000.0, $this->employeeRevenue($this->employeeA), 'full refund: extension nets to zero');
        $this->assertSame(800.0, $this->employeeRevenue($this->employeeB), 'refund never leaks to other employees');
    }

    public function test_partial_refund_reduces_only_the_responsible_employee(): void
    {
        [$child, $charge] = $this->createExtension($this->orderA, $this->employeeA, '200.00');
        $this->payExtension($charge, '220.00');

        $child->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at'      => now(),
            'amount'           => 0,
            'refund_amount'    => 110,
            'tax_refunded'     => 10,
            'status'           => OrderPaymentStatus::PartialRefund->value,
        ]);

        $this->assertSame(1100.0, $this->employeeRevenue($this->employeeA), '1000 + 200 - 100 ex-tax');
        $this->assertSame(800.0, $this->employeeRevenue($this->employeeB));
    }

    public function test_refund_credits_responsible_employee_even_if_another_user_created_the_child(): void
    {
        // B is responsible for the extension on A's order; the child order was
        // created while A was logged in. The refund must hit B, not A.
        [$child, $charge] = $this->createExtension($this->orderA, $this->employeeB, '100.00');
        $this->payExtension($charge, '110.00');

        $this->assertSame(1000.0, $this->employeeRevenue($this->employeeA));
        $this->assertSame(900.0, $this->employeeRevenue($this->employeeB));

        $child->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at'      => now(),
            'amount'           => 0,
            'refund_amount'    => 110,
            'tax_refunded'     => 10,
            'status'           => OrderPaymentStatus::Refund->value,
        ]);

        $this->assertSame(1000.0, $this->employeeRevenue($this->employeeA), 'creator absorbs nothing');
        $this->assertSame(800.0, $this->employeeRevenue($this->employeeB), 'responsible employee nets to zero');
    }

    public function test_voided_extension_credits_nothing(): void
    {
        [$child, $charge] = $this->createExtension($this->orderA, $this->employeeA, '200.00');
        $this->payExtension($charge, '220.00');

        $child->payments()->where('status', OrderPaymentStatus::Paid->value)
            ->firstOrFail()->update(['status' => OrderPaymentStatus::Voided->value]);

        $this->assertSame(1000.0, $this->employeeRevenue($this->employeeA));
    }

    // ── Date / store filters and counts ─────────────────────────────────

    public function test_extension_reports_in_its_paid_period(): void
    {
        [, $charge] = $this->createExtension($this->orderA, $this->employeeA, '200.00');
        $this->payExtension($charge, '220.00');

        $paidAt = now()->addMonth();
        DB::table('billing_charges')->where('id', $charge->id)->update(['paid_at' => $paidAt]);

        $this->assertSame(1000.0, $this->employeeRevenue($this->employeeA), 'creation period: rental only');

        $nextMonth = [
            'start_date' => $paidAt->copy()->startOfMonth()->toDateString(),
            'end_date'   => $paidAt->copy()->endOfMonth()->toDateString(),
        ];
        $this->assertSame(200.0, $this->employeeRevenue($this->employeeA, $nextMonth), 'paid period: extension only');
    }

    public function test_store_filter_keeps_attribution(): void
    {
        $otherStore = Store::create(['store_name' => 'Elsewhere']);

        [, $charge] = $this->createExtension($this->orderA, $this->employeeA, '200.00');
        $this->payExtension($charge, '220.00');

        $this->assertSame(1200.0, $this->employeeRevenue($this->employeeA, ['store' => $this->store->id]));
        $this->assertSame(0.0, $this->employeeRevenue($this->employeeA, ['store' => $otherStore->id]));
    }

    public function test_orders_closed_count_is_unchanged_by_extensions(): void
    {
        [, $charge] = $this->createExtension($this->orderA, $this->employeeA, '200.00');
        $this->payExtension($charge, '220.00');

        $rows = app(EmployeePerformanceEngine::class)
            ->reportData($this->filters(), [$this->employeeA->id, $this->employeeB->id], 'single')['employees'];

        $rowA = collect($rows)->firstWhere('id', $this->employeeA->id);
        $this->assertSame(1200.0, round((float) $rowA['qualified_revenue'], 2));
        // orders_closed counts orders with product lines — extensions never inflate it
        $this->assertSame(1, (int) $rowA['orders_closed']);
    }
}

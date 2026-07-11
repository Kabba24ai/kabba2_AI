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
use App\Services\Reports\SalesReportEngineV2;
use App\Services\Reports\SalesTaxReportEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sales Tax Report treatment of Rental Extension child orders (Phase 2A).
 *
 * Controlled reconciliation: parent rental $100 + $10 tax (paid cash),
 * extension $40 + $4 tax. A paid extension must appear EXACTLY ONCE
 * (via the Billing Engine stream, dated by paid_at) — never a second
 * time through the order-level stream, which only admits orders that
 * own order_products rows. Refunds reverse through the refund stream.
 */
class SalesTaxExtensionReportingTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $employee;
    private Store $store;
    private Order $parent;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create([
            'setting_type' => 'General Settings', 'value_type' => 'text',
            'setting_name' => 'sales_tax', 'setting_title' => 'sales_tax', 'setting_value' => '0.10',
        ]);

        $this->customer = Customer::create([
            'first_name' => 'Tax', 'last_name' => 'Audit',
            'email' => 'tax-audit@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Tax', 'last_name' => 'Clerk',
            'email' => 'tax-clerk@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        $this->store = Store::create(['store_name' => 'Tax Audit Store']);

        $category = ProductCategory::create(['title' => 'Telehandlers']);
        $product  = Product::create([
            'product_name' => 'Telehandler 42ft',
            'slug'         => 'telehandler-42ft-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $product->categories()->attach($category->id);

        $this->parent = Order::create([
            'order_number'  => '3153',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Tax Audit',
            'subtotal'      => 100,
            'tax_amount'    => 10,
            'grand_total'   => 110,
        ]);

        OrderProduct::create([
            'order_id'          => $this->parent->id,
            'product_id'        => $product->id,
            'product_name'      => 'Telehandler 42ft',
            'price'             => 100,
            'quantity'          => 1,
            'sub_total'         => 100,
            'tax'               => 10,
            'total'             => 110,
            'delivery_store_id' => $this->store->id,
            'product_data'      => ['product_type' => 'Rental'],
        ]);

        $this->parent->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 110,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        $this->actingAs($this->employee);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function createExtension(): array
    {
        $this->postJson(
            route('admin.order-management.orders.extension.store', ['unique_id' => $this->parent->unique_id]),
            [
                'description'        => 'One extra week',
                'base_amount'        => '40.00',
                'add_tax'            => true,
                'responsible_person' => $this->employee->id,
            ]
        )->assertOk()->assertJson(['success' => true]);

        $child  = Order::where('order_number', 'like', '3153-%')->latest('id')->firstOrFail();
        $charge = BillingCharge::where('child_order_id', $child->id)->firstOrFail();

        return [$child, $charge];
    }

    private function payExtensionCash(BillingCharge $charge): void
    {
        $this->post(route('admin.dashboard.paymentstore'), [
            'source'                   => 'crm',
            'type'                     => 'extension',
            'billing_charge_unique_id' => $charge->unique_id,
            'customer_id'              => $this->customer->id,
            'amount'                   => '44.00',
            'payment_type'             => 'Cash',
            'responsible_person'       => $this->employee->id,
        ])->assertRedirect();
    }

    private function refundChild(Order $child, float $amount, float $tax, string $status): void
    {
        // Written exactly the way RefundPaymentController records refunds
        $child->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at'      => now(),
            'amount'           => 0,
            'refund_amount'    => $amount,
            'tax_refunded'     => $tax,
            'status'           => $status,
        ]);
    }

    private function filters(array $extra = []): array
    {
        return array_merge([
            'start_date' => now()->subDay()->toDateString(),
            'end_date'   => now()->addDay()->toDateString(),
        ], $extra);
    }

    /**
     * Replicates the SalesTax IndexController KPI math over the four streams.
     * Returns [taxable_subtotal, net_tax, rows_total, tax_free, refund_total].
     */
    private function taxKpis(array $filters): array
    {
        $engine  = app(SalesTaxReportEngine::class);
        $allRows = $engine->salesRows($filters)
            ->concat($engine->refundRows($filters))
            ->concat($engine->accountRows($filters))
            ->concat($engine->billingRows($filters))
            ->values();

        $taxFree = $allRows->filter(fn ($r) => $r->tax_amount == 0)->sum(fn ($r) => $r->grand_total);
        $taxable = $allRows->filter(fn ($r) => $r->tax_amount != 0)->values();

        return [
            round($taxable->sum(fn ($r) => $r->subtotal), 2),
            round($taxable->sum(fn ($r) => $r->tax_amount), 2),
            round($taxable->sum(fn ($r) => $r->grand_total), 2),
            round($taxFree, 2),
            round($allRows->where('type', 'refund')->sum(fn ($r) => $r->grand_total), 2),
        ];
    }

    private function assertTaxReport(array $filters, float $taxableSub, float $netTax, float $rowsTotal, float $refundTotal = 0.0): void
    {
        [$sub, $tax, $total, $taxFree, $refunds] = $this->taxKpis($filters);

        $this->assertSame($taxableSub, $sub, 'taxable subtotal');
        $this->assertSame($netTax, $tax, 'net tax collected');
        $this->assertSame($rowsTotal, $total, 'gross total (taxable rows)');
        $this->assertSame(0.0, $taxFree, 'tax-free revenue');
        $this->assertSame($refundTotal, $refunds, 'refund total');
    }

    // ── Scenarios ───────────────────────────────────────────────────────

    public function test_baseline_rental_without_extension(): void
    {
        $this->assertTaxReport($this->filters(), 100.0, 10.0, 110.0);
    }

    public function test_paid_extension_is_counted_exactly_once(): void
    {
        [, $charge] = $this->createExtension();
        $this->payExtensionCash($charge);

        $this->assertTaxReport($this->filters(), 140.0, 14.0, 154.0);
    }

    public function test_pay_later_extension_is_excluded_before_payment(): void
    {
        $this->createExtension();

        $this->assertTaxReport($this->filters(), 100.0, 10.0, 110.0);
    }

    public function test_pay_later_extension_enters_once_after_payment(): void
    {
        [, $charge] = $this->createExtension();

        $this->assertTaxReport($this->filters(), 100.0, 10.0, 110.0);

        $this->payExtensionCash($charge);

        $this->assertTaxReport($this->filters(), 140.0, 14.0, 154.0);
    }

    public function test_fully_refunded_extension_nets_to_zero(): void
    {
        [$child, $charge] = $this->createExtension();
        $this->payExtensionCash($charge);
        $this->refundChild($child, 44, 4, OrderPaymentStatus::Refund->value);

        $this->assertTaxReport($this->filters(), 100.0, 10.0, 110.0, -44.0);
    }

    public function test_partially_refunded_extension_reduces_proportionally(): void
    {
        [$child, $charge] = $this->createExtension();
        $this->payExtensionCash($charge);
        $this->refundChild($child, 22, 2, OrderPaymentStatus::PartialRefund->value);

        // 140 − 20 taxable, 14 − 2 tax, 154 − 22 total
        $this->assertTaxReport($this->filters(), 120.0, 12.0, 132.0, -22.0);
    }

    public function test_gateway_voided_extension_is_excluded(): void
    {
        [$child, $charge] = $this->createExtension();
        $this->payExtensionCash($charge);

        // VoidPaymentController updates the Paid row's status in place
        $child->payments()->where('status', OrderPaymentStatus::Paid->value)
            ->firstOrFail()->update(['status' => OrderPaymentStatus::Voided->value]);

        $this->assertTaxReport($this->filters(), 100.0, 10.0, 110.0);
    }

    public function test_two_extensions_with_only_one_paid(): void
    {
        [, $chargeA] = $this->createExtension();
        $this->payExtensionCash($chargeA);
        $this->createExtension(); // 3153-B stays Pay Later

        $this->assertTaxReport($this->filters(), 140.0, 14.0, 154.0);
    }

    public function test_store_filter_matches_unfiltered_result(): void
    {
        [$child, $charge] = $this->createExtension();
        $this->payExtensionCash($charge);

        $storeFilters = $this->filters(['store' => $this->store->id]);
        $this->assertTaxReport($storeFilters, 140.0, 14.0, 154.0);

        // Refund under the store filter must also reverse (the child has no
        // product rows — it matches through the charge's store attribution)
        $this->refundChild($child, 44, 4, OrderPaymentStatus::Refund->value);
        $this->assertTaxReport($storeFilters, 100.0, 10.0, 110.0, -44.0);
    }

    public function test_extension_paid_in_a_later_period_reports_only_in_paid_period(): void
    {
        [, $charge] = $this->createExtension();
        $this->payExtensionCash($charge);

        // Move settlement into next month (paid_at basis)
        $paidAt = now()->addMonth();
        DB::table('billing_charges')->where('id', $charge->id)->update(['paid_at' => $paidAt]);

        // Child-creation month: no extension — no cross-period duplication
        $this->assertTaxReport($this->filters(), 100.0, 10.0, 110.0);

        // Settlement month: the extension exactly once
        $nextMonth = [
            'start_date' => $paidAt->copy()->startOfMonth()->toDateString(),
            'end_date'   => $paidAt->copy()->endOfMonth()->toDateString(),
        ];
        $this->assertTaxReport($nextMonth, 40.0, 4.0, 44.0);
    }

    public function test_rental_count_is_unchanged_by_a_paid_extension(): void
    {
        [, $charge] = $this->createExtension();
        $this->payExtensionCash($charge);

        $kpis = app(SalesReportEngineV2::class)->kpis([
            'date_range' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date'   => now()->addDay()->toDateString(),
        ]);

        $this->assertSame(1, (int) $kpis['transaction_count']);
    }
}

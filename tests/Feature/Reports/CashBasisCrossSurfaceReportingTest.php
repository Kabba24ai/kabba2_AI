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
use App\Services\Dashboard\DashboardFinancialPresenter;
use App\Services\Reports\PaymentReconciliationLedger;
use App\Services\Reports\SalesReportEngineV2;
use App\Services\Reports\SalesTaxReportEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cash-basis / payment-date accounting — cross-surface reconciliation.
 *
 * Every collected-revenue surface (Sales Tax Report Stream A, Sales Summary
 * KPIs, Payment Reconciliation Ledger Stream A, dashboard financial KPIs)
 * reads the SAME CollectedRevenueQuery rows, allocated exclusively through
 * ProportionalPaymentSplit. These tests prove the shared semantics end to end:
 * identical amounts across surfaces, temporal stability, overpayment
 * exclusion, unpaid-order exclusion, refund-period separation, and lifetime
 * reconciliation to the order's stored totals.
 */
class CashBasisCrossSurfaceReportingTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Cash', 'last_name' => 'Basis',
            'email' => 'cash-basis@example.com', 'status' => 'Active',
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Ledger', 'last_name' => 'Clerk',
            'email' => 'cash-basis-clerk@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]));

        $category = ProductCategory::create(['title' => 'Cash Basis Category']);
        $product  = Product::create([
            'product_name' => 'Cash Basis Product',
            'slug'         => 'cash-basis-product-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $product->categories()->attach($category->id);

        // $1000 subtotal + $100 tax (10%) = $1100 grand total, created June 24.
        $this->order = Order::create([
            'order_number'  => 'CASH-1',
            'order_date'    => '2026-06-24',
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Cash Basis',
            'subtotal'      => 1000,
            'tax_amount'    => 100,
            'grand_total'   => 1100,
        ]);

        OrderProduct::create([
            'order_id'     => $this->order->id,
            'product_id'   => $product->id,
            'product_name' => 'Cash Basis Product',
            'price'        => 1000,
            'quantity'     => 1,
            'sub_total'    => 1000,
            'tax'          => 100,
            'total'        => 1100,
            'product_data' => ['product_type' => 'Rental'],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function v2Filters(string $start, string $end): array
    {
        return [
            'date_range'     => 'custom',
            'start_date'     => $start,
            'end_date'       => $end,
            'payment_status' => 'paid',
        ];
    }

    private function splitPayJuneJuly(): void
    {
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => '2026-06-24 09:00:00',
            'amount'           => 700, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-07-29 10:00:00',
            'amount'           => 400, 'status' => OrderPaymentStatus::Paid->value,
        ]);
    }

    public function test_the_same_payment_produces_identical_amounts_on_every_surface(): void
    {
        $this->splitPayJuneJuly();

        // ── Sales Tax Report, July ──
        $taxRows = app(SalesTaxReportEngine::class)->salesRows([
            'start_date' => '2026-07-01', 'end_date' => '2026-07-31',
        ]);
        $this->assertCount(1, $taxRows);
        $this->assertSame(400.0, $taxRows->first()->grand_total);
        $this->assertSame(36.36, $taxRows->first()->tax_amount);
        $this->assertSame(363.64, $taxRows->first()->subtotal);

        // ── Sales Summary KPIs, July ──
        $kpis = app(SalesReportEngineV2::class)->kpis($this->v2Filters('2026-07-01', '2026-07-31'));
        $this->assertSame(363.64, round($kpis['gross_sales'], 2));
        $this->assertSame(36.36, round($kpis['tax_collected'], 2));
        $this->assertSame(400.0, round($kpis['total_collected'], 2));
        $this->assertSame(363.64, round($kpis['net_sales'], 2));
        $this->assertSame(1, $kpis['transaction_count']);

        // ── Reconciliation Ledger, July ──
        $ledger = app(PaymentReconciliationLedger::class)->rows($this->v2Filters('2026-07-01', '2026-07-31'));
        $orderRows = $ledger->where('stream', 'order')->values();
        $this->assertCount(1, $orderRows);
        $this->assertSame(400.0, $orderRows->first()->grand_total);
        $this->assertSame(36.36, $orderRows->first()->tax_amount);
        $this->assertSame(363.64, $orderRows->first()->base_amount);

        // The load-bearing invariant: Σ ledger grand_total == KPI total_collected.
        $this->assertSame(
            round($kpis['total_collected'], 2),
            round($ledger->sum('grand_total'), 2),
            'ledger must reconcile to the KPI engine for the same filters'
        );

        // ── Dashboard financial KPIs (month-to-date == July window) ──
        Carbon::setTestNow('2026-07-31 12:00:00');
        $dashboard = app(DashboardFinancialPresenter::class)->salesData();
        $this->assertSame(
            round($kpis['net_sales'], 2),
            round($dashboard['mtd']['totalSales'], 2),
            'dashboard Total Sales must equal the Sales Summary net sales for the same period'
        );
    }

    public function test_recording_the_july_payment_does_not_restate_june_on_the_summary_or_ledger(): void
    {
        // June's $700 exists alone first.
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => '2026-06-24 09:00:00',
            'amount'           => 700, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        $juneBefore = app(SalesReportEngineV2::class)->kpis($this->v2Filters('2026-06-01', '2026-06-30'));
        $this->assertSame(636.36, round($juneBefore['gross_sales'], 2), '700 applied − 63.64 tax');
        $this->assertSame(63.64, round($juneBefore['tax_collected'], 2));

        // July's $400 arrives.
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-07-29 10:00:00',
            'amount'           => 400, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        // June re-runs byte-for-byte identical — on the KPIs and the ledger.
        $juneAfter = app(SalesReportEngineV2::class)->kpis($this->v2Filters('2026-06-01', '2026-06-30'));
        $this->assertSame(round($juneBefore['gross_sales'], 2), round($juneAfter['gross_sales'], 2));
        $this->assertSame(round($juneBefore['tax_collected'], 2), round($juneAfter['tax_collected'], 2));
        $this->assertSame(round($juneBefore['total_collected'], 2), round($juneAfter['total_collected'], 2));

        $juneLedger = app(PaymentReconciliationLedger::class)->rows($this->v2Filters('2026-06-01', '2026-06-30'));
        $this->assertSame(700.0, round($juneLedger->where('stream', 'order')->sum('grand_total'), 2));

        // Lifetime allocations sum exactly to the stored order totals.
        $lifetime = app(SalesReportEngineV2::class)->kpis($this->v2Filters('2026-01-01', '2026-12-31'));
        $this->assertSame(1000.0, round($lifetime['gross_sales'], 2), 'lifetime gross == order subtotal');
        $this->assertSame(100.0, round($lifetime['tax_collected'], 2), 'lifetime tax == order tax');
        $this->assertSame(1100.0, round($lifetime['total_collected'], 2), 'lifetime collected == order grand_total');
    }

    public function test_unpaid_orders_contribute_nothing_anywhere(): void
    {
        // The order exists with NO payments.
        $kpis   = app(SalesReportEngineV2::class)->kpis($this->v2Filters('2026-06-01', '2026-07-31'));
        $ledger = app(PaymentReconciliationLedger::class)->rows($this->v2Filters('2026-06-01', '2026-07-31'));
        $tax    = app(SalesTaxReportEngine::class)->salesRows(['start_date' => '2026-06-01', 'end_date' => '2026-07-31']);

        $this->assertSame(0.0, round($kpis['gross_sales'], 2));
        $this->assertSame(0, $kpis['transaction_count']);
        $this->assertCount(0, $ledger->where('stream', 'order'));
        $this->assertCount(0, $tax);
    }

    public function test_overpayment_is_surfaced_separately_and_never_creates_revenue_or_tax(): void
    {
        // $1,200 collected in July on the $1,100 order.
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-07-29 10:00:00',
            'amount'           => 1200, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $kpis = app(SalesReportEngineV2::class)->kpis($this->v2Filters('2026-07-01', '2026-07-31'));
        $this->assertSame(1000.0, round($kpis['gross_sales'], 2), 'revenue capped at the order subtotal');
        $this->assertSame(100.0, round($kpis['tax_collected'], 2), 'tax capped at the order tax');
        $this->assertSame(1100.0, round($kpis['total_collected'], 2), 'collected capped at the canonical total');
        $this->assertSame(100.0, round($kpis['overpayments'], 2), 'the excess is surfaced separately');

        $ledgerRow = app(PaymentReconciliationLedger::class)
            ->rows($this->v2Filters('2026-07-01', '2026-07-31'))
            ->where('stream', 'order')->first();
        $this->assertSame(1100.0, $ledgerRow->grand_total);
        $this->assertNotNull($ledgerRow->notes, 'the ledger row must call out the overpayment');

        $taxRow = app(SalesTaxReportEngine::class)
            ->salesRows(['start_date' => '2026-07-01', 'end_date' => '2026-07-31'])->first();
        $this->assertSame(1100.0, $taxRow->grand_total, 'overpayment never creates taxable volume');
        $this->assertSame(100.0, $taxRow->tax_amount);
    }

    public function test_a_refund_lands_in_the_refund_period_and_never_rewrites_the_payment_period(): void
    {
        // Paid in full in June …
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-06-24 09:00:00',
            'amount'           => 1100, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $juneBefore = app(SalesReportEngineV2::class)->kpis($this->v2Filters('2026-06-01', '2026-06-30'));

        // … then $300 refunded in July (its own event row, as the refund flow creates).
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-07-15 10:00:00',
            'refunded_at'      => '2026-07-15 10:00:00',
            'amount'           => 0,
            'refund_amount'    => 300,
            'status'           => OrderPaymentStatus::Refund->value,
        ]);

        // June is untouched — the refund is a separate, negative, July-dated event.
        $juneAfter = app(SalesReportEngineV2::class)->kpis($this->v2Filters('2026-06-01', '2026-06-30'));
        $this->assertSame(round($juneBefore['gross_sales'], 2), round($juneAfter['gross_sales'], 2));
        $this->assertSame(round($juneBefore['refunds'], 2), round($juneAfter['refunds'], 2), 'the July refund must not appear in June');
        $this->assertSame(0.0, round($juneAfter['refunds'], 2));

        $july = app(SalesReportEngineV2::class)->kpis($this->v2Filters('2026-07-01', '2026-07-31'));
        $this->assertSame(300.0, round($july['refunds'], 2), 'the refund reports in its own period');
        $this->assertSame(0.0, round($july['gross_sales'], 2), 'no positive revenue in July');

        $julyLedger = app(PaymentReconciliationLedger::class)->rows($this->v2Filters('2026-07-01', '2026-07-31'));
        $this->assertCount(1, $julyLedger->where('stream', 'refund'));
        $this->assertSame(-300.0, round($julyLedger->where('stream', 'refund')->first()->grand_total, 2));

        $juneLedger = app(PaymentReconciliationLedger::class)->rows($this->v2Filters('2026-06-01', '2026-06-30'));
        $this->assertSame(1100.0, round($juneLedger->where('stream', 'order')->sum('grand_total'), 2), 'the June positive row survives the July refund');
    }

    public function test_collection_terminology_is_explicit_and_internally_consistent(): void
    {
        // $1,100 paid June; $300 refunded July; window spans both.
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-06-24 09:00:00',
            'amount'           => 1100, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-07-15 10:00:00',
            'refunded_at'      => '2026-07-15 10:00:00',
            'amount'           => 0,
            'refund_amount'    => 300,
            'status'           => OrderPaymentStatus::Refund->value,
        ]);

        $kpis = app(SalesReportEngineV2::class)->kpis($this->v2Filters('2026-06-01', '2026-07-31'));

        // The four terms exist and are individually correct.
        $this->assertSame(1100.0, round($kpis['gross_collections'], 2), 'gross_collections = positive cash in (Σ applied)');
        $this->assertSame(300.0, round($kpis['refunds'], 2));
        $this->assertSame(800.0, round($kpis['net_collections'], 2), 'net_collections = gross_collections − refunds');
        $this->assertSame(0.0, round($kpis['overpayments'], 2));

        // Identities: net = gross − refunds; total_collected is the NET alias.
        $this->assertSame(
            round($kpis['gross_collections'] - $kpis['refunds'], 2),
            round($kpis['net_collections'], 2)
        );
        $this->assertSame(
            round($kpis['net_collections'], 2),
            round($kpis['total_collected'], 2),
            'total_collected is the backward-compatible alias of net_collections'
        );

        // Ledger identity is against NET collections (refund rows are negative).
        $ledger = app(PaymentReconciliationLedger::class)->rows($this->v2Filters('2026-06-01', '2026-07-31'));
        $this->assertSame(
            round($kpis['net_collections'], 2),
            round($ledger->sum('grand_total'), 2),
            'Σ ledger grand_total == net_collections'
        );

        // Basis labeling: cash views are payment-date; POD is the explicit
        // order-date expected projection.
        $this->assertSame('payment_date_cash', $kpis['basis']);
        $podKpis = app(SalesReportEngineV2::class)->kpis(array_merge(
            $this->v2Filters('2026-06-01', '2026-07-31'),
            ['payment_status' => 'pod'],
        ));
        $this->assertSame('order_date_expected', $podKpis['basis']);
        $this->assertSame('POD / Expected Revenue — Order-Date Basis', $podKpis['basis_label']);
    }
}

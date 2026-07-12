<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * ApiSalesReportAdapter — the React/API reporting stack's bridge onto the
 * canonical financial engines (Phase 2D consolidation).
 *
 * The /api/sales-reports/v1 endpoints previously re-implemented aggregation
 * with endpoint-specific SQL (an unscoped order_payments join that fanned out
 * rows, refund detection by refund_amount > 0 only, no extension awareness).
 * This adapter translates the API's filter contract onto SalesReportEngineV2,
 * ProductSalesPerformanceEngine, PaymentReconciliationLedger, and
 * SalesReportingService, so both stacks share ONE financial interpretation —
 * including Billing Engine extension attribution, which the engines already
 * carry via BillingRevenueAttributionService.
 *
 * Response CONTRACTS (JSON keys/shapes) are preserved by the controller; this
 * class only supplies canonical numbers.
 */
class ApiSalesReportAdapter
{
    public function __construct(
        private SalesReportEngineV2 $engine,
        private ProductSalesPerformanceEngine $products,
        private PaymentReconciliationLedger $ledger,
        private SalesReportingService $reporting,
    ) {}

    /**
     * Translate the React stack's filter keys onto the canonical engine keys.
     * Component flags (waiver/insurance/delivery/shipping) map onto the
     * engine's component filters, which apply on the KPI path; the legacy
     * behavior of dropping entire product rows for these flags was a defect.
     */
    public function engineFilters(array $react, Carbon $start, Carbon $end): array
    {
        $normalize = fn ($v) => ($v === 'all' || $v === null || $v === '') ? null : $v;
        $flag      = fn ($k) => !empty($react[$k]) && $react[$k] !== 'false';

        $filters = [
            'date_range'     => 'custom',
            'start_date'     => $start->toDateString(),
            'end_date'       => $end->toDateString(),
            'store'          => $normalize($react['store'] ?? null),
            'category'       => $normalize($react['category'] ?? null),
            'product'        => $normalize($react['product'] ?? null),
            'item_type'      => $normalize($react['itemType'] ?? null),
            // Legacy whitelist included Account/Invoice payment rows
            'payment_status' => 'paid_and_account',
        ];

        if ($flag('waiverOnly'))       $filters['damage_waiver']   = 'only';
        if ($flag('excludeWaiver'))    $filters['damage_waiver']   = 'exclude';
        if ($flag('insuranceOnly'))    $filters['track_insurance'] = 'only';
        if ($flag('excludeInsurance')) $filters['track_insurance'] = 'exclude';
        if ($flag('deliveryOnly'))     $filters['delivery']        = 'only';
        if ($flag('excludeDelivery'))  $filters['delivery']        = 'exclude';
        if ($flag('excludeShipping'))  $filters['shipping']        = 'exclude';

        return $filters;
    }

    /**
     * Canonical daily net sales for an arbitrary window, keyed 'Y-m-d'.
     * Sums exactly to SalesReportEngineV2 net_sales for the same window
     * (orders on order_date, billing/extensions on paid_at, refunds on their
     * refund date — the same series behind the Blade Sales Trend chart).
     */
    public function dailyNetSales(Carbon $start, Carbon $end, array $react): array
    {
        $trend = $this->engine->trendData($this->engineFilters($react, $start, $end));

        $series = [];
        foreach ($trend['current'] as $i => $value) {
            $series[$start->copy()->addDays($i)->toDateString()] = (float) $value;
        }

        return $series;
    }

    /** Canonical KPI snapshot (Pure Sales Summary numbers) for the window. */
    public function kpis(array $react, Carbon $start, Carbon $end): array
    {
        return $this->engine->kpis($this->engineFilters($react, $start, $end));
    }

    /** Units sold (order_products.quantity) through the canonical base query. */
    public function itemsSold(array $react, Carbon $start, Carbon $end): int
    {
        return (int) $this->reporting
            ->baseQuery($this->engineFilters($react, $start, $end))
            ->sum('order_products.quantity');
    }

    /** Orders carrying a discount, for the discounts KPI. */
    public function discountedTransactionCount(array $react, Carbon $start, Carbon $end): int
    {
        return (int) $this->reporting
            ->baseQuery($this->engineFilters($react, $start, $end))
            ->where('orders.discount_amount', '>', 0)
            ->distinct('orders.id')
            ->count('orders.id');
    }

    /**
     * Top products in the legacy contract shape: rows of
     * {id, name, total_sales, order_count} ordered by total_sales desc, plus
     * the all-products total. Revenue comes from ProductSalesPerformanceEngine
     * (partial-refund netting + extension attribution included); order_count
     * is the distinct paid orders containing the product.
     */
    public function topProducts(array $react, Carbon $start, Carbon $end): array
    {
        $filters  = $this->engineFilters($react, $start, $end);
        $data     = $this->products->productData(array_merge($filters, ['limit' => 'all']));
        $orderCounts = $this->reporting->baseQuery($filters)
            ->selectRaw('order_products.product_id AS pid, COUNT(DISTINCT orders.id) AS c')
            ->groupBy('order_products.product_id')
            ->pluck('c', 'pid');

        $rows = collect($data['rows'])->map(fn ($row) => (object) [
            'id'          => $row['product_id'],
            'name'        => $row['product_name'],
            'total_sales' => (float) $row['revenue'],
            'order_count' => (int) ($orderCounts[$row['product_id']] ?? 0),
        ])->values();

        return [$rows, (float) $data['totals']['revenue']];
    }

    /** Top categories — same treatment keyed by primary category. */
    public function topCategories(array $react, Carbon $start, Carbon $end): array
    {
        $filters = $this->engineFilters($react, $start, $end);
        // Categories are never self-filtered in this endpoint (legacy contract)
        unset($filters['category']);

        $data = $this->products->categoryData(array_merge($filters, ['limit' => 'all']));
        $orderCounts = $this->reporting->baseQuery($filters)
            ->selectRaw('pcc.primary_category_id AS cid, COUNT(DISTINCT orders.id) AS c')
            ->groupBy('pcc.primary_category_id')
            ->pluck('c', 'cid');

        $rows = collect($data['rows'])->map(fn ($row) => (object) [
            'id'          => $row['category_id'],
            'name'        => $row['category_name'],
            'total_sales' => (float) $row['revenue'],
            'order_count' => (int) ($orderCounts[$row['category_id']] ?? 0),
        ])->values();

        return [$rows, (float) $data['totals']['revenue']];
    }

    /** Refund rows from the canonical reconciliation ledger (refund stream). */
    public function refundRows(array $react, Carbon $start, Carbon $end): Collection
    {
        return collect($this->ledger->rows($this->engineFilters($react, $start, $end)))
            ->filter(fn ($row) => ($row->stream ?? null) === 'refund')
            ->values();
    }
}

<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SalesReportEngineV2 — single source of truth for all Pure Sales Summary calculations.
 *
 * Revenue philosophy:
 *   - Net Sales is the primary business performance metric.
 *   - Every report section (KPI cards, trend cards, chart) consumes this engine.
 *   - No independent revenue calculations exist outside this class.
 *
 * Transaction-based accounting — each financial event is dated when it occurred:
 *   Sales / Discounts  →  orders.order_date                              (sale transaction date)
 *   Refunds            →  COALESCE(refunded_at, payment_datetime, created_at)  (refund transaction date)
 *   Account Payments   →  customer_accounts.date                               (payment transaction date)
 *   Tax                →  same anchor as its parent transaction
 *
 * Refund date note:
 *   refunded_at is the canonical field set by RefundPaymentController at refund creation.
 *   The COALESCE chain covers historical records backfilled from payment_datetime/created_at.
 */
class SalesReportEngineV2
{
    public function __construct(private SalesReportingService $reporting) {}

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Return all KPI metrics for the given filters. Raw floats — identical shape
     * to the old PureSalesSummaryReport::kpis() so callers require no changes.
     */
    public function kpis(array $filters): array
    {
        [$start, $end] = $this->reporting->resolveDateRange($filters);
        return $this->snapshot($filters, $start, $end);
    }

    /**
     * Return net sales for an explicit date window using the verified snapshot() path.
     * Called by SalesTrendAnalysisEngine — guarantees reconciliation with Pure Sales Summary.
     *
     * $filters must NOT include date_range/start_date/end_date — those are set here
     * so that baseQuery() inside snapshot() applies the correct order-date window.
     */
    public function netSalesForPeriod(string $startDate, string $endDate, array $filters): float
    {
        $filters = array_merge($filters, [
            'date_range' => 'custom',
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();
        return $this->snapshot($filters, $start, $end)['net_sales'];
    }

    /**
     * Return trend data for the selected period plus the preceding equal-length period.
     *
     * Acceptance guarantees:
     *   - trend['netSales']          === kpis()['net_sales']  (same snapshot)
     *   - array_sum(trend['current']) === trend['netSales']   (no per-day floors)
     *   - trend['growthRate'] derived from net_sales, not gross sales
     */
    public function trendData(array $filters): array
    {
        [$start, $end] = $this->reporting->resolveDateRange($filters);

        if (!$start || !$end) {
            return $this->emptyTrend();
        }

        $days      = (int) $start->diffInDays($end) + 1;
        $prevEnd   = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();

        $prevFilters = array_merge($filters, [
            'date_range' => 'custom',
            'start_date' => $prevStart->toDateString(),
            'end_date'   => $prevEnd->toDateString(),
        ]);

        // Period-level snapshots — drive trend summary cards.
        $currentSnapshot  = $this->snapshot($filters, $start, $end);
        $previousSnapshot = $this->snapshot($prevFilters, $prevStart, $prevEnd);

        // Daily series — drive chart bars. Must sum to snapshot net_sales.
        $currentDaily  = $this->buildDailySeries($filters, $start, $end, $days);
        $previousDaily = $this->buildDailySeries($prevFilters, $prevStart, $prevEnd, $days);

        $categories = [];
        $current    = [];
        $previous   = [];

        for ($i = 0; $i < $days; $i++) {
            $date     = $start->copy()->addDays($i);
            $prevDate = $prevStart->copy()->addDays($i);
            $categories[] = $date->format('M j');
            // No max(0.0) floor — negative days are valid (refund-heavy days).
            // This ensures array_sum($current) == $currentSnapshot['net_sales'].
            $current[]  = $currentDaily[$date->toDateString()]      ?? 0.0;
            $previous[] = $previousDaily[$prevDate->toDateString()]  ?? 0.0;
        }

        $netSales         = $currentSnapshot['net_sales'];
        $previousNetSales = $previousSnapshot['net_sales'];

        return [
            'categories'             => $categories,
            'current'                => $current,
            'previous'               => $previous,
            'netSales'               => $netSales,
            'previousNetSales'       => $previousNetSales,
            'dailyNetAverage'        => $days > 0 ? round($netSales / $days, 2) : 0,
            'growthRate'             => $previousNetSales > 0
                ? round((($netSales - $previousNetSales) / $previousNetSales) * 100, 1)
                : 0,
            'netSalesPerTransaction' => $currentSnapshot['transaction_count'] > 0
                ? round($netSales / $currentSnapshot['transaction_count'], 2)
                : 0,
        ];
    }

    // ─── Private: Revenue Snapshot ────────────────────────────────────────────

    /**
     * Compute a complete revenue snapshot for a date window.
     * Called for both the current period and the previous period — identical logic.
     *
     * This is the ONE place revenue is calculated. KPI cards and trend cards
     * both read from this output.
     */
    private function snapshot(array $filters, ?Carbon $start, ?Carbon $end): array
    {
        if (!$start || !$end) {
            return $this->zeroSnapshot($filters);
        }

        $startDate = $start->toDateString();
        $endDate   = $end->toDateString();

        // 1. Order revenue — anchored on orders.order_date (sale transaction date)
        $orderAgg = $this->queryOrderRevenue($filters);
        $grossSales       = (float) ($orderAgg->gross_sales       ?? 0);
        $taxCollected     = (float) ($orderAgg->tax_collected     ?? 0);
        $deliveryRevenue  = (float) ($orderAgg->delivery_revenue  ?? 0);
        $transactionCount = (int)   ($orderAgg->transaction_count ?? 0);

        // 2. Addon components — damage waiver, track insurance, tire insurance
        //    Extracted from product_data JSON; each is a sub-component of sub_total.
        [$damageWaiverRevenue, $trackInsuranceRevenue, $tireInsuranceRevenue]
            = $this->queryAddonRevenue($filters);

        // 3. Discounts — anchored on orders.order_date (discount is part of original sale)
        $discounts = $this->queryDiscounts($filters);

        // 4. Refunds — anchored on COALESCE(refunded_at, payment_datetime, created_at) (REFUND TRANSACTION DATE)
        //    A refund in June on a May order appears in June, not May.
        $refunds = $this->queryRefunds($filters, $startDate, $endDate);

        // 5. Account payments — anchored on customer_accounts.date (payment transaction date)
        $paymentStatus           = $filters['payment_status'] ?? 'paid';
        $accountPaymentsReceived = 0.0;
        $accountPaymentsTax      = 0.0;
        if (in_array($paymentStatus, ['paid', 'all', 'account'])) {
            [$accountPaymentsReceived, $accountPaymentsTax]
                = $this->queryAccountPayments($filters, $startDate, $endDate);
        }

        // 6. Merge account payments into their canonical KPI buckets:
        //    base → gross sales; tax → tax collected
        $grossSales   += $accountPaymentsReceived;
        $taxCollected += $accountPaymentsTax;
        $shippingRevenue = 0.0;

        // 7. Apply component-level revenue filters
        $c = $this->applyComponentFilters($filters, [
            'gross_sales'               => $grossSales,
            'tax_collected'             => $taxCollected,
            'delivery_revenue'          => $deliveryRevenue,
            'damage_waiver_revenue'     => $damageWaiverRevenue,
            'track_insurance_revenue'   => $trackInsuranceRevenue,
            'tire_insurance_revenue'    => $tireInsuranceRevenue,
            'shipping_revenue'          => $shippingRevenue,
            'refunds'                   => $refunds,
            'discounts'                 => $discounts,
            'account_payments_received' => $accountPaymentsReceived,
            'account_payments_tax'      => $accountPaymentsTax,
            'transaction_count'         => (float) $transactionCount,
        ]);

        $grossSales              = $c['gross_sales'];
        $taxCollected            = $c['tax_collected'];
        $deliveryRevenue         = $c['delivery_revenue'];
        $damageWaiverRevenue     = $c['damage_waiver_revenue'];
        $trackInsuranceRevenue   = $c['track_insurance_revenue'];
        $tireInsuranceRevenue    = $c['tire_insurance_revenue'];
        $shippingRevenue         = $c['shipping_revenue'];
        $refunds                 = $c['refunds'];
        $discounts               = $c['discounts'];
        $accountPaymentsReceived = $c['account_payments_received'];
        $accountPaymentsTax      = $c['account_payments_tax'];
        $transactionCount        = (int) $c['transaction_count'];

        // 8. Derived metrics
        $netSales             = $grossSales - $discounts - $refunds;
        $averageTicket        = $transactionCount > 0 ? $netSales / $transactionCount : 0;
        $totalCollected       = $grossSales + $taxCollected - $refunds - $discounts;
        $totalAccountPayments = $accountPaymentsReceived + $accountPaymentsTax;

        return [
            'gross_sales'               => $grossSales,
            'discounts'                 => $discounts,
            'refunds'                   => $refunds,
            'net_sales'                 => $netSales,
            'delivery_revenue'          => $deliveryRevenue,
            'damage_waiver_revenue'     => $damageWaiverRevenue,
            'track_insurance_revenue'   => $trackInsuranceRevenue,
            'tire_insurance_revenue'    => $tireInsuranceRevenue,
            'shipping_revenue'          => $shippingRevenue,
            'operational_revenue'       => $netSales,
            'tax_collected'             => $taxCollected,
            'total_collected'           => $totalCollected,
            'total_account_payments'    => $totalAccountPayments,
            'transaction_count'         => $transactionCount,
            'average_ticket'            => $averageTicket,
            'account_payments_received' => $accountPaymentsReceived,
            'payment_status'            => $paymentStatus,
        ];
    }

    // ─── Private: Daily Series ────────────────────────────────────────────────

    /**
     * Build a map of 'Y-m-d' → daily net sales float.
     * Each day = order gross ± component filters + account payments − discounts − refunds.
     *
     * Reconciliation guarantee:
     *   sum(values) === snapshot(filters)['net_sales']
     *
     * This holds because both use the same queries and same date anchors.
     * No per-day floor is applied — negative days are valid.
     */
    private function buildDailySeries(array $filters, Carbon $start, Carbon $end, int $days): array
    {
        $startDate = $start->toDateString();
        $endDate   = $end->toDateString();

        $paymentStatus = $filters['payment_status'] ?? 'paid';
        $anyOnly       = in_array('only', [
            $filters['damage_waiver']   ?? 'all',
            $filters['track_insurance'] ?? 'all',
            $filters['delivery']        ?? 'all',
            $filters['shipping']        ?? 'all',
        ]);

        // Daily order revenue — order_date anchor
        $dailyOrder = $this->queryDailyOrderRevenue($filters);

        // Daily discounts — order_date anchor (mirrors snapshot queryDiscounts)
        // Zeroed in "only" mode to match applyComponentFilters behavior in snapshot.
        $dailyDiscounts = !$anyOnly
            ? $this->queryDailyDiscounts($filters)
            : collect();

        // Daily refunds — TRANSACTION DATE anchor (COALESCE(refunded_at, payment_datetime, created_at))
        // Zeroed in "only" mode to match applyComponentFilters behavior in snapshot.
        $dailyRefunds = !$anyOnly
            ? $this->queryDailyRefunds($filters, $startDate, $endDate)
            : collect();

        // Daily account payments — customer_accounts.date anchor
        // Excluded in "only" mode and when payment_status excludes account payments.
        $dailyAcct = (in_array($paymentStatus, ['paid', 'all', 'account']) && !$anyOnly)
            ? $this->queryDailyAccountPayments($filters, $startDate, $endDate)
            : collect();

        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();

            $dayRow  = $dailyOrder[$date] ?? null;
            $dayGross = $this->applyDailyComponentFilter($filters, $dayRow);
            $dayAcct  = (float) ($dailyAcct[$date]->daily_acct          ?? 0);
            $dayDisc  = (float) ($dailyDiscounts[$date]->daily_discounts ?? 0);
            $dayRef   = (float) ($dailyRefunds[$date]->daily_refunds     ?? 0);

            // No max(0.0) floor — required for sum(daily) == snapshot net_sales.
            $result[$date] = $dayGross + $dayAcct - $dayDisc - $dayRef;
        }

        return $result;
    }

    // ─── Private: Revenue Queries ─────────────────────────────────────────────

    /**
     * Aggregate order-level revenue using orders.order_date as the date anchor.
     * Date range is already baked into $filters and applied by baseQuery().
     */
    private function queryOrderRevenue(array $filters): object
    {
        return $this->reporting->baseQuery($filters)
            ->selectRaw("
                SUM(order_products.sub_total)     AS gross_sales,
                SUM(order_products.tax)           AS tax_collected,
                COUNT(DISTINCT orders.id)         AS transaction_count,
                SUM(COALESCE(
                    CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.service_option_price')), '') AS DECIMAL(12,2)),
                    0
                ))                                AS delivery_revenue
            ")
            ->first();
    }

    /**
     * Return [damageWaiver, trackInsurance, tireInsurance] revenue totals.
     * Extracted from product_data JSON; these amounts are sub-components of sub_total.
     */
    private function queryAddonRevenue(array $filters): array
    {
        $rows = $this->reporting->baseQuery($filters)
            ->selectRaw("
                order_products.quantity,
                JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_rental_items_prices.rental_damage_waiver'))   AS dw_price,
                JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_rental_items_prices.rental_track_insurance')) AS track_price,
                JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_rental_items_prices.rental_tire_insurance'))  AS tire_price
            ")
            ->get();

        return [
            $rows->sum(fn ($r) => (float) ($r->dw_price    ?? 0) * (int) ($r->quantity ?? 1)),
            $rows->sum(fn ($r) => (float) ($r->track_price ?? 0) * (int) ($r->quantity ?? 1)),
            $rows->sum(fn ($r) => (float) ($r->tire_price  ?? 0) * (int) ($r->quantity ?? 1)),
        ];
    }

    /**
     * Discounts anchored to orders.order_date — they are part of the original sale.
     * Sums at the order level (not line level) to avoid double-counting multi-line orders.
     */
    private function queryDiscounts(array $filters): float
    {
        $orderIds = $this->reporting->baseQuery($filters)
            ->selectRaw('DISTINCT orders.id')
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return 0.0;
        }

        return (float) DB::table('orders')
            ->whereIn('id', $orderIds)
            ->sum('discount_amount');
    }

    /**
     * Refunds anchored to COALESCE(op.refunded_at, op.payment_datetime, op.created_at) — the REFUND TRANSACTION DATE.
     *
     * Key difference from old code: a refund on a May order processed in June
     * appears in June's refund total, not May's. May's Net Sales are preserved.
     *
     * Context filters (store, item type, category, product) scope which orders'
     * refunds to include, but the DATE bucket is the refund transaction date.
     *
     * refunded_at is the canonical field (added in migration 2026_06_21_000001).
     * COALESCE covers historical records backfilled during migration.
     */
    private function queryRefunds(array $filters, string $startDate, string $endDate): float
    {
        $query = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->whereNull('o.deleted_at')
            ->whereIn('op.status', ['Refunded', 'Partial Refund'])
            ->whereBetween(DB::raw('DATE(COALESCE(op.refunded_at, op.payment_datetime, op.created_at))'), [$startDate, $endDate]);

        $this->applyRefundContextFilters($query, $filters);

        return (float) $query->sum('op.refund_amount');
    }

    /**
     * Account payments (base + tax), anchored to customer_accounts.date.
     * Returns [base, tax].
     */
    private function queryAccountPayments(array $filters, string $startDate, string $endDate): array
    {
        $query = DB::table('customer_accounts')
            ->where('type', 'payment')
            ->whereNull('deleted_at')
            ->whereBetween('date', [$startDate, $endDate]);

        if (!empty($filters['store'])) {
            $customerIds = DB::table('orders')
                ->join('order_products', 'order_products.order_id', '=', 'orders.id')
                ->whereNull('orders.deleted_at')
                ->whereNull('order_products.deleted_at')
                ->where(function ($q) use ($filters) {
                    $q->where('order_products.delivery_store_id', $filters['store'])
                      ->orWhere('order_products.pickup_store_id', $filters['store']);
                })
                ->pluck('orders.customer_id')
                ->unique();

            $query->whereIn('customer_id', $customerIds);
        }

        // customer_accounts.amount is tax-inclusive for type='payment' records.
        // Extract formula: pre-tax = amount / (1 + rate), tax = amount − amount / (1 + rate).
        // Records with sales_tax = 0 fall through to ELSE branches (base = amount, tax = 0).
        $row = $query->selectRaw("
            SUM(
                CASE WHEN CAST(NULLIF(COALESCE(sales_tax, '0'), '') AS DECIMAL(10,6)) > 0
                    THEN amount / (1 + CAST(NULLIF(COALESCE(sales_tax, '0'), '') AS DECIMAL(10,6)))
                    ELSE amount
                END
            ) AS base_total,
            SUM(
                CASE WHEN CAST(NULLIF(COALESCE(sales_tax, '0'), '') AS DECIMAL(10,6)) > 0
                    THEN amount - amount / (1 + CAST(NULLIF(COALESCE(sales_tax, '0'), '') AS DECIMAL(10,6)))
                    ELSE 0
                END
            ) AS tax_total
        ")->first();

        return [
            (float) ($row->base_total ?? 0),
            (float) ($row->tax_total  ?? 0),
        ];
    }

    // ─── Private: Daily Queries ───────────────────────────────────────────────

    /**
     * Daily order revenue grouped by orders.order_date.
     * Includes component sub-values (delivery, DW, track, tire) for filter application.
     */
    private function queryDailyOrderRevenue(array $filters): Collection
    {
        return $this->reporting->baseQuery($filters)
            ->selectRaw("
                DATE(orders.order_date) AS date,
                SUM(order_products.sub_total) AS daily_gross,
                SUM(COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.service_option_price')), '') AS DECIMAL(12,2)), 0)) AS daily_delivery,
                SUM(COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_rental_items_prices.rental_damage_waiver')), '') AS DECIMAL(12,2)), 0) * order_products.quantity) AS daily_dw,
                SUM(COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_rental_items_prices.rental_track_insurance')), '') AS DECIMAL(12,2)), 0) * order_products.quantity) AS daily_ti,
                SUM(COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_rental_items_prices.rental_tire_insurance')), '') AS DECIMAL(12,2)), 0) * order_products.quantity) AS daily_tire
            ")
            ->groupByRaw('DATE(orders.order_date)')
            ->get()
            ->keyBy('date');
    }

    /**
     * Daily discounts grouped by orders.order_date.
     * Mirrors queryDiscounts() but per-day for chart use.
     */
    private function queryDailyDiscounts(array $filters): Collection
    {
        $orderIds = $this->reporting->baseQuery($filters)
            ->selectRaw('DISTINCT orders.id')
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return collect();
        }

        return DB::table('orders')
            ->whereIn('id', $orderIds)
            ->selectRaw('DATE(order_date) AS date, SUM(discount_amount) AS daily_discounts')
            ->groupByRaw('DATE(order_date)')
            ->get()
            ->keyBy('date');
    }

    /**
     * Daily refunds grouped by COALESCE(op.refunded_at, op.payment_datetime, op.created_at) (REFUND TRANSACTION DATE).
     * Mirrors queryRefunds() but per-day for chart use.
     *
     * The date bucket is when the refund was recorded, not the original order date.
     * This ensures sum(daily_refunds) == queryRefunds() total for the same window.
     */
    private function queryDailyRefunds(array $filters, string $startDate, string $endDate): Collection
    {
        $query = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->whereNull('o.deleted_at')
            ->whereIn('op.status', ['Refunded', 'Partial Refund'])
            ->whereBetween(DB::raw('DATE(COALESCE(op.refunded_at, op.payment_datetime, op.created_at))'), [$startDate, $endDate])
            ->selectRaw('DATE(COALESCE(op.refunded_at, op.payment_datetime, op.created_at)) AS date, SUM(op.refund_amount) AS daily_refunds')
            ->groupByRaw('DATE(COALESCE(op.refunded_at, op.payment_datetime, op.created_at))');

        $this->applyRefundContextFilters($query, $filters);

        return $query->get()->keyBy('date');
    }

    /**
     * Daily account payments grouped by customer_accounts.date.
     * Mirrors queryAccountPayments() base amount per-day for chart use.
     * Only base amount (not tax) — aligns with how base flows into gross_sales in snapshot().
     */
    private function queryDailyAccountPayments(array $filters, string $startDate, string $endDate): Collection
    {
        $query = DB::table('customer_accounts')
            ->where('type', 'payment')
            ->whereNull('deleted_at')
            ->whereBetween('date', [$startDate, $endDate]);

        if (!empty($filters['store'])) {
            $customerIds = DB::table('orders')
                ->join('order_products', 'order_products.order_id', '=', 'orders.id')
                ->whereNull('orders.deleted_at')
                ->whereNull('order_products.deleted_at')
                ->where(function ($q) use ($filters) {
                    $q->where('order_products.delivery_store_id', $filters['store'])
                      ->orWhere('order_products.pickup_store_id', $filters['store']);
                })
                ->pluck('orders.customer_id')
                ->unique();

            $query->whereIn('customer_id', $customerIds);
        }

        // Use the same extract formula as queryAccountPayments() so that
        // sum(daily_acct) == snapshot accountPaymentsReceived (reconciliation guarantee).
        return $query
            ->selectRaw("date, SUM(
                CASE WHEN CAST(NULLIF(COALESCE(sales_tax, '0'), '') AS DECIMAL(10,6)) > 0
                    THEN amount / (1 + CAST(NULLIF(COALESCE(sales_tax, '0'), '') AS DECIMAL(10,6)))
                    ELSE amount
                END
            ) AS daily_acct")
            ->groupBy('date')
            ->get()
            ->keyBy('date');
    }

    // ─── Private: Filter Helpers ──────────────────────────────────────────────

    /**
     * Apply context filters to a refund query to scope which orders' refunds to include.
     * These filters determine WHICH orders are in scope — not WHEN the refund occurred.
     * The date bucket is COALESCE(refunded_at, payment_datetime, created_at) regardless.
     *
     * Filters applied: store, item_type/sale_type, category, product.
     * Payment status is intentionally NOT applied to refunds (future refinement).
     */
    private function applyRefundContextFilters(\Illuminate\Database\Query\Builder $query, array $filters): void
    {
        // Store filter
        if (!empty($filters['store'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->selectRaw('1')
                    ->from('order_products as orp_rf')
                    ->whereColumn('orp_rf.order_id', 'o.id')
                    ->whereNull('orp_rf.deleted_at')
                    ->where(function ($q) use ($filters) {
                        $q->where('orp_rf.delivery_store_id', $filters['store'])
                          ->orWhere('orp_rf.pickup_store_id', $filters['store']);
                    });
            });
        }

        // Item type / sale type filter
        $itemType = null;
        if (!empty($filters['item_type']) && $filters['item_type'] !== 'all') {
            $itemType = ucfirst($filters['item_type']);
        } elseif (!empty($filters['sale_type']) && $filters['sale_type'] !== 'all') {
            $typeMap  = ['rental' => 'Rental', 'retail' => 'Retail'];
            $itemType = $typeMap[$filters['sale_type']] ?? null;
        }

        if ($itemType) {
            $query->whereExists(function ($sub) use ($itemType) {
                $sub->selectRaw('1')
                    ->from('order_products as orp_rf2')
                    ->join('products as p_rf', 'p_rf.id', '=', 'orp_rf2.product_id')
                    ->whereColumn('orp_rf2.order_id', 'o.id')
                    ->whereNull('orp_rf2.deleted_at')
                    ->where('p_rf.product_type', $itemType);
            });
        }

        // Category filter
        if (!empty($filters['category'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->selectRaw('1')
                    ->from('order_products as orp_rf3')
                    ->join('product_category_children as pcc_rf', 'pcc_rf.product_id', '=', 'orp_rf3.product_id')
                    ->whereColumn('orp_rf3.order_id', 'o.id')
                    ->whereNull('orp_rf3.deleted_at')
                    ->where('pcc_rf.product_category_id', $filters['category']);
            });
        }

        // Product filter
        if (!empty($filters['product'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->selectRaw('1')
                    ->from('order_products as orp_rf4')
                    ->whereColumn('orp_rf4.order_id', 'o.id')
                    ->whereNull('orp_rf4.deleted_at')
                    ->where('orp_rf4.product_id', $filters['product']);
            });
        }
    }

    /**
     * Apply damage_waiver / track_insurance / delivery / shipping filters at the
     * revenue-component level, not at the row/order level.
     *
     * "exclude" → zero out just the targeted component bucket; leave all others intact.
     * "only"    → zero out ALL buckets; keep only the selected component(s).
     */
    private function applyComponentFilters(array $filters, array $components): array
    {
        $dwFilter    = $filters['damage_waiver']   ?? 'all';
        $tiFilter    = $filters['track_insurance'] ?? 'all';
        $delivFilter = $filters['delivery']        ?? 'all';
        $shipFilter  = $filters['shipping']        ?? 'all';

        $anyOnly = in_array('only', [$dwFilter, $tiFilter, $delivFilter, $shipFilter]);

        if ($anyOnly) {
            $result = array_map(fn () => 0.0, $components);

            if ($dwFilter    === 'only') {
                $result['gross_sales']             += $components['damage_waiver_revenue'];
                $result['damage_waiver_revenue']    = $components['damage_waiver_revenue'];
            }
            if ($tiFilter    === 'only') {
                $result['gross_sales']             += $components['track_insurance_revenue'];
                $result['track_insurance_revenue']  = $components['track_insurance_revenue'];
            }
            if ($delivFilter === 'only') {
                $result['gross_sales']             += $components['delivery_revenue'];
                $result['delivery_revenue']         = $components['delivery_revenue'];
            }
            if ($shipFilter  === 'only') {
                $result['gross_sales']             += $components['shipping_revenue'];
                $result['shipping_revenue']         = $components['shipping_revenue'];
            }

            return $result;
        }

        // "Exclude" mode: subtract the component from gross_sales (it is a sub-component
        // of sub_total and thus already inside gross_sales) and zero the display card.
        $result = $components;

        if ($dwFilter    === 'exclude') {
            $result['gross_sales']            -= $result['damage_waiver_revenue'];
            $result['damage_waiver_revenue']   = 0.0;
        }
        if ($tiFilter    === 'exclude') {
            $result['gross_sales']            -= $result['track_insurance_revenue'];
            $result['track_insurance_revenue'] = 0.0;
        }
        if ($delivFilter === 'exclude') {
            $result['gross_sales']            -= $result['delivery_revenue'];
            $result['delivery_revenue']        = 0.0;
        }
        if ($shipFilter  === 'exclude') {
            $result['gross_sales']            -= $result['shipping_revenue'];
            $result['shipping_revenue']        = 0.0;
        }

        return $result;
    }

    /**
     * Apply component filters to a single daily order row.
     * Mirrors applyComponentFilters() but for one day's sub_total data.
     */
    private function applyDailyComponentFilter(array $filters, ?object $row): float
    {
        if (!$row) {
            return 0.0;
        }

        $gross = (float) ($row->daily_gross    ?? 0);
        $deliv = (float) ($row->daily_delivery ?? 0);
        $dw    = (float) ($row->daily_dw       ?? 0);
        $ti    = (float) ($row->daily_ti       ?? 0);

        $dwFilter    = $filters['damage_waiver']   ?? 'all';
        $tiFilter    = $filters['track_insurance'] ?? 'all';
        $delivFilter = $filters['delivery']        ?? 'all';
        $shipFilter  = $filters['shipping']        ?? 'all';

        $anyOnly = in_array('only', [$dwFilter, $tiFilter, $delivFilter, $shipFilter]);

        if ($anyOnly) {
            $total = 0.0;
            if ($dwFilter    === 'only') $total += $dw;
            if ($tiFilter    === 'only') $total += $ti;
            if ($delivFilter === 'only') $total += $deliv;
            // shipping has no daily column yet — would remain 0
            return $total;
        }

        if ($dwFilter    === 'exclude') $gross -= $dw;
        if ($tiFilter    === 'exclude') $gross -= $ti;
        if ($delivFilter === 'exclude') $gross -= $deliv;

        return $gross;
    }

    // ─── Private: Empty Structures ────────────────────────────────────────────

    private function zeroSnapshot(array $filters): array
    {
        return [
            'gross_sales'               => 0.0,
            'discounts'                 => 0.0,
            'refunds'                   => 0.0,
            'net_sales'                 => 0.0,
            'delivery_revenue'          => 0.0,
            'damage_waiver_revenue'     => 0.0,
            'track_insurance_revenue'   => 0.0,
            'tire_insurance_revenue'    => 0.0,
            'shipping_revenue'          => 0.0,
            'operational_revenue'       => 0.0,
            'tax_collected'             => 0.0,
            'total_collected'           => 0.0,
            'total_account_payments'    => 0.0,
            'transaction_count'         => 0,
            'average_ticket'            => 0.0,
            'account_payments_received' => 0.0,
            'payment_status'            => $filters['payment_status'] ?? 'paid',
        ];
    }

    private function emptyTrend(): array
    {
        return [
            'categories'             => [],
            'current'                => [],
            'previous'               => [],
            'netSales'               => 0,
            'previousNetSales'       => 0,
            'dailyNetAverage'        => 0,
            'growthRate'             => 0,
            'netSalesPerTransaction' => 0,
        ];
    }
}

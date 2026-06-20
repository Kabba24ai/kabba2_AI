<?php

namespace App\Services\Reports;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pure Sales Summary — Report 1.
 *
 * Answers: "How much revenue did we actually sell in this period?"
 * Uses SalesReportingService for all filter + query logic.
 * Every other Sales Report will follow the same pattern.
 */
class PureSalesSummaryReport
{
    public function __construct(private SalesReportingService $reporting) {}

    // ─── KPI Aggregation ────────────────────────────────────────────────────

    /**
     * Return all KPI card values for the given filters.
     * Runs two DB queries: one for line-item aggregates, one for order-level totals.
     */
    public function kpis(array $filters): array
    {
        // ── Query 1: line-item level aggregates ─────────────────────────────
        $lineAgg = $this->reporting->baseQuery($filters)
            ->selectRaw("
                SUM(order_products.sub_total)                                                AS gross_sales,
                SUM(order_products.tax)                                                      AS tax_collected,
                COUNT(DISTINCT orders.id)                                                    AS transaction_count,
                SUM(
                    COALESCE(
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.service_option_price')) AS DECIMAL(12,2)),
                        0
                    )
                )                                                                            AS delivery_revenue
            ")
            ->first();

        $grossSales        = (float) ($lineAgg->gross_sales        ?? 0);
        $taxCollected      = (float) ($lineAgg->tax_collected      ?? 0);
        $deliveryRevenue   = (float) ($lineAgg->delivery_revenue   ?? 0);
        $transactionCount  = (int)   ($lineAgg->transaction_count  ?? 0);

        // ── Query 2: order-level discount total (SUM of DISTINCT order discounts) ─
        // We identify the in-scope order IDs from the same filters, then sum at order level.
        $orderIds = $this->reporting->baseQuery($filters)
            ->selectRaw('DISTINCT orders.id')
            ->pluck('id');

        $discounts = (float) DB::table('orders')
            ->whereIn('id', $orderIds)
            ->sum('discount_amount');

        // ── Query 3: damage waiver + track insurance (PHP-parsed from product_data) ─
        // These prices live inside product_data.product_rental_items_prices JSON object.
        $addonRows = $this->reporting->baseQuery($filters)
            ->selectRaw("
                order_products.quantity,
                JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_rental_items_prices.rental_damage_waiver'))    AS dw_price,
                JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_rental_items_prices.rental_track_insurance'))  AS track_price,
                JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_rental_items_prices.rental_tire_insurance'))   AS tire_price
            ")
            ->get();

        $damageWaiverRevenue   = $addonRows->sum(fn ($r) => (float) ($r->dw_price    ?? 0) * (int) ($r->quantity ?? 1));
        $trackInsuranceRevenue = $addonRows->sum(fn ($r) => (float) ($r->track_price ?? 0) * (int) ($r->quantity ?? 1));
        $tireInsuranceRevenue  = $addonRows->sum(fn ($r) => (float) ($r->tire_price  ?? 0) * (int) ($r->quantity ?? 1));

        // ── Query 4: account payments received (customer_accounts.type='payment') ─
        // Returns base (pre-tax) and tax component so each flows into the correct bucket:
        // base → gross sales, tax → tax collected (mirrors direct paid order split).
        $paymentStatus           = $filters['payment_status'] ?? 'paid';
        $accountPaymentsReceived = 0.0;
        $accountPaymentsTax      = 0.0;
        if (in_array($paymentStatus, ['paid', 'all', 'account'])) {
            $apSummary               = $this->accountPaymentsSummary($filters);
            $accountPaymentsReceived = $apSummary['total'];
            $accountPaymentsTax      = $apSummary['tax'];
        }

        // ── Query 5: refunds against the in-scope orders ─────────────────────
        $refunds = $this->refundsTotal($filters);

        // Shipping revenue — tracked as a separate bucket; wired to product_data key when available.
        $shippingRevenue = 0.0;

        // ── Merge account payments into their canonical buckets ──────────────
        // Account payment base → gross sales; account payment tax → tax collected.
        $grossSales   += $accountPaymentsReceived;
        $taxCollected += $accountPaymentsTax;

        // ── Apply component-level revenue filters ────────────────────────────
        // Damage Waiver / Track Insurance / Delivery / Shipping filters must never
        // exclude whole orders — they only zero or isolate a specific revenue bucket.
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

        $grossSales             = $c['gross_sales'];
        $taxCollected           = $c['tax_collected'];
        $deliveryRevenue        = $c['delivery_revenue'];
        $damageWaiverRevenue    = $c['damage_waiver_revenue'];
        $trackInsuranceRevenue  = $c['track_insurance_revenue'];
        $tireInsuranceRevenue   = $c['tire_insurance_revenue'];
        $shippingRevenue        = $c['shipping_revenue'];
        $refunds                = $c['refunds'];
        $discounts              = $c['discounts'];
        $accountPaymentsReceived = $c['account_payments_received'];
        $accountPaymentsTax     = $c['account_payments_tax'];
        $transactionCount       = (int) $c['transaction_count'];

        // ── Derived metrics ──────────────────────────────────────────────────
        $netSales      = max(0, $grossSales - $discounts - $refunds);
        $averageTicket = $transactionCount > 0 ? $netSales / $transactionCount : 0;

        // Total Collected = all money that came in: rental/gross + every ancillary component
        // + tax - refunds - discounts. Including ancillary components means excluding any one
        // of them (via filter) reduces Total Collected by exactly that component's amount.
        $totalCollected = $grossSales
            + $deliveryRevenue
            + $damageWaiverRevenue
            + $trackInsuranceRevenue
            + $tireInsuranceRevenue
            + $shippingRevenue
            + $taxCollected
            - $refunds
            - $discounts;

        // Total Account Payments = what account customers actually paid (base + tax).
        $totalAccountPayments = $accountPaymentsReceived + $accountPaymentsTax;

        $operationalRevenue = $netSales
            + $deliveryRevenue
            + $damageWaiverRevenue
            + $trackInsuranceRevenue
            + $tireInsuranceRevenue
            + $shippingRevenue;

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
            'operational_revenue'       => $operationalRevenue,
            'tax_collected'             => $taxCollected,
            'total_collected'           => $totalCollected,
            'total_account_payments'    => $totalAccountPayments,
            'transaction_count'         => $transactionCount,
            'average_ticket'            => $averageTicket,
            'account_payments_received' => $accountPaymentsReceived,
            'payment_status'            => $paymentStatus,
        ];
    }

    // ─── Detail Grid ────────────────────────────────────────────────────────

    /**
     * Return a paginated detail grid — one row per order_product.
     */
    public function detailGrid(array $filters, int $page = 1, int $perPage = 50): LengthAwarePaginator
    {
        $query = $this->reporting->baseQuery($filters)
            ->select([
                'order_products.id',
                'orders.order_date                        as transaction_date',
                'orders.order_number                      as transaction_number',
                'orders.unique_id                         as order_unique_id',
                'stores.store_name',
                'orders.customer_name',
                'order_products.product_name',
                'pc.title                                 as category_name',
                'products.product_type                    as item_type',
                'order_products.quantity',
                'order_products.price                     as unit_price',
                'order_products.sub_total',
                'order_products.tax',
                'order_products.total',
                'orders.subtotal                          as order_subtotal',
                'orders.discount_amount                   as order_discount',
                'order_products.product_data',
            ])
            ->orderByDesc('orders.order_date')
            ->orderByDesc('orders.id');

        $total = (clone $query)->count('order_products.id');

        $items = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->formatDetailRow($row));

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path'  => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /**
     * Return the full ungrouped dataset for CSV/Excel export (no pagination).
     */
    public function exportData(array $filters): Collection
    {
        return $this->reporting->baseQuery($filters)
            ->select([
                'order_products.id',
                'orders.order_date                        as transaction_date',
                'orders.order_number                      as transaction_number',
                'stores.store_name',
                'orders.customer_name',
                'order_products.product_name',
                'pc.title                                 as category_name',
                'products.product_type                    as item_type',
                'order_products.quantity',
                'order_products.price                     as unit_price',
                'order_products.sub_total',
                'order_products.tax',
                'order_products.total',
                'orders.subtotal                          as order_subtotal',
                'orders.discount_amount                   as order_discount',
                'order_products.product_data',
            ])
            ->orderByDesc('orders.order_date')
            ->get()
            ->map(fn ($row) => $this->formatDetailRow($row));
    }

    // ─── Account Payments ────────────────────────────────────────────────────

    /**
     * Return base revenue and tax from customer_accounts payments in the date range.
     * Anchored on customer_accounts.date (payment received date), not order_date.
     *
     * sales_tax stores the rate (e.g. 0.0975), not a dollar amount.
     * Payment records often have sales_tax_type = NULL even when a rate is set,
     * so tax is computed whenever the rate is non-zero (not gated on sales_tax_type).
     *
     * @return array{total: float, tax: float}
     */
    public function accountPaymentsSummary(array $filters): array
    {
        [$start, $end] = $this->reporting->resolveDateRange($filters);

        $query = DB::table('customer_accounts')
            ->where('type', 'payment')
            ->whereNull('deleted_at');

        if ($start && $end) {
            $query->whereBetween('date', [
                $start->toDateString(),
                $end->toDateString(),
            ]);
        }

        // Store filter: limit to customers who have had orders at this store
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

        $row = (clone $query)->selectRaw("
            SUM(amount) AS base_total,
            SUM(
                CASE WHEN CAST(NULLIF(COALESCE(sales_tax, '0'), '') AS DECIMAL(10,6)) > 0
                    THEN amount * CAST(NULLIF(COALESCE(sales_tax, '0'), '') AS DECIMAL(10,6))
                    ELSE 0
                END
            ) AS tax_total
        ")->first();

        return [
            'total' => (float) ($row->base_total ?? 0),
            'tax'   => (float) ($row->tax_total  ?? 0),
        ];
    }

    /** @deprecated Use accountPaymentsSummary()['total'] */
    public function accountPaymentsTotal(array $filters): float
    {
        return $this->accountPaymentsSummary($filters)['total'];
    }

    /**
     * Sum of refund_amount for Refunded / Partial Refund payments on in-scope orders.
     * Uses order_date as the anchor (matches sales period, not refund-processed date).
     */
    public function refundsTotal(array $filters): float
    {
        $orderIds = $this->reporting->baseQuery($filters)
            ->selectRaw('DISTINCT orders.id')
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return 0.0;
        }

        return (float) DB::table('order_payments')
            ->whereIn('order_id', $orderIds)
            ->whereIn('status', ['Refunded', 'Partial Refund'])
            ->sum('refund_amount');
    }

    // ─── Component Filter Application ────────────────────────────────────────

    /**
     * Apply damage_waiver / track_insurance / delivery / shipping filters at the
     * revenue-component level, not at the row/order level.
     *
     * "exclude" → zero out just the targeted bucket, leave everything else intact.
     * "only"    → zero out ALL other buckets; show only the selected component(s).
     *
     * @param  array $filters   Request filters (damage_waiver, track_insurance, …)
     * @param  array $components  All computed revenue buckets keyed by name
     * @return array  Same shape as $components with filters applied
     */
    private function applyComponentFilters(array $filters, array $components): array
    {
        $dwFilter    = $filters['damage_waiver']   ?? 'all';
        $tiFilter    = $filters['track_insurance'] ?? 'all';
        $delivFilter = $filters['delivery']        ?? 'all';
        $shipFilter  = $filters['shipping']        ?? 'all';

        $anyOnly = in_array('only', [$dwFilter, $tiFilter, $delivFilter, $shipFilter]);

        if ($anyOnly) {
            // "Only" mode: start from zero and re-enable only the selected component(s).
            // Rental gross, tax, refunds, discounts, and account payments are cleared —
            // the user wants to see only this revenue stream in isolation.
            $result = array_map(fn() => 0.0, $components);

            if ($dwFilter    === 'only') $result['damage_waiver_revenue']   = $components['damage_waiver_revenue'];
            if ($tiFilter    === 'only') $result['track_insurance_revenue'] = $components['track_insurance_revenue'];
            if ($delivFilter === 'only') $result['delivery_revenue']        = $components['delivery_revenue'];
            if ($shipFilter  === 'only') $result['shipping_revenue']        = $components['shipping_revenue'];

            return $result;
        }

        // "Exclude" mode: preserve everything, zero only the targeted component.
        $result = $components;
        if ($dwFilter    === 'exclude') $result['damage_waiver_revenue']   = 0.0;
        if ($tiFilter    === 'exclude') $result['track_insurance_revenue'] = 0.0;
        if ($delivFilter === 'exclude') $result['delivery_revenue']        = 0.0;
        if ($shipFilter  === 'exclude') $result['shipping_revenue']        = 0.0;

        return $result;
    }

    /**
     * Compute the daily revenue total for one trend row, applying the same
     * component-level filters used in kpis().
     * $row must have columns: daily_gross, daily_delivery, daily_dw, daily_ti, daily_tire.
     */
    private function computeDailyTotal(array $filters, ?object $row): float
    {
        if (!$row) {
            return 0.0;
        }

        $gross  = (float) ($row->daily_gross    ?? 0);
        $deliv  = (float) ($row->daily_delivery ?? 0);
        $dw     = (float) ($row->daily_dw       ?? 0);
        $ti     = (float) ($row->daily_ti       ?? 0);
        $tire   = (float) ($row->daily_tire     ?? 0);

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
            // "shipping only" has no dedicated daily_shipping column — returns 0 for now
            return max(0.0, $total);
        }

        // Normal total: gross (rental) + every ancillary component
        $total = $gross + $deliv + $dw + $ti + $tire;

        // "Exclude" modes: subtract just that component
        if ($dwFilter    === 'exclude') $total -= $dw;
        if ($tiFilter    === 'exclude') $total -= $ti;
        if ($delivFilter === 'exclude') $total -= $deliv;

        return max(0.0, $total);
    }

    // ─── Sales Trend ─────────────────────────────────────────────────────────

    /**
     * Daily revenue trend for the selected filter period plus the immediately
     * preceding equal-length period for comparison.
     */
    public function trendData(array $filters): array
    {
        [$start, $end] = $this->reporting->resolveDateRange($filters);

        if (!$start || !$end) {
            return [
                'categories'         => [],
                'current'            => [],
                'previous'           => [],
                'totalSales'         => 0,
                'previousTotalSales' => 0,
                'dailyAverage'       => 0,
                'growthRate'         => 0,
            ];
        }

        $days = (int) $start->diffInDays($end) + 1;

        $componentSelect = "
            DATE(orders.order_date) AS date,
            SUM(order_products.sub_total) AS daily_gross,
            SUM(COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.service_option_price')), '') AS DECIMAL(12,2)), 0)) AS daily_delivery,
            SUM(COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_rental_items_prices.rental_damage_waiver')), '') AS DECIMAL(12,2)), 0) * order_products.quantity) AS daily_dw,
            SUM(COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_rental_items_prices.rental_track_insurance')), '') AS DECIMAL(12,2)), 0) * order_products.quantity) AS daily_ti,
            SUM(COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.product_rental_items_prices.rental_tire_insurance')), '') AS DECIMAL(12,2)), 0) * order_products.quantity) AS daily_tire
        ";

        $currentRows = $this->reporting->baseQuery($filters)
            ->selectRaw($componentSelect)
            ->groupByRaw('DATE(orders.order_date)')
            ->get()
            ->keyBy('date');

        $prevEnd   = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();

        $prevFilters = array_merge($filters, [
            'date_range' => 'custom',
            'start_date' => $prevStart->toDateString(),
            'end_date'   => $prevEnd->toDateString(),
        ]);

        $previousRows = $this->reporting->baseQuery($prevFilters)
            ->selectRaw($componentSelect)
            ->groupByRaw('DATE(orders.order_date)')
            ->get()
            ->keyBy('date');

        $categories = [];
        $current    = [];
        $previous   = [];

        for ($i = 0; $i < $days; $i++) {
            $date     = $start->copy()->addDays($i);
            $prevDate = $prevStart->copy()->addDays($i);

            $categories[] = $date->format('M j');
            $current[]    = $this->computeDailyTotal($filters, $currentRows[$date->toDateString()] ?? null);
            $previous[]   = $this->computeDailyTotal($filters, $previousRows[$prevDate->toDateString()] ?? null);
        }

        $totalSales         = array_sum($current);
        $previousTotalSales = array_sum($previous);

        return [
            'categories'         => $categories,
            'current'            => $current,
            'previous'           => $previous,
            'totalSales'         => $totalSales,
            'previousTotalSales' => $previousTotalSales,
            'dailyAverage'       => $days > 0 ? round($totalSales / $days, 2) : 0,
            'growthRate'         => $previousTotalSales > 0
                ? round((($totalSales - $previousTotalSales) / $previousTotalSales) * 100, 1)
                : 0,
        ];
    }

    // ─── Filter Helpers ──────────────────────────────────────────────────────

    /**
     * Return categories that have at least one product with finalized orders.
     * Used to populate the Category dropdown.
     */
    public function availableCategories(): Collection
    {
        return DB::table('product_categories as pc')
            ->join('product_category_children as pcc', 'pcc.product_category_id', '=', 'pc.id')
            ->join('products', 'products.id', '=', 'pcc.product_id')
            ->join('order_products', 'order_products.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereNull('orders.deleted_at')
            ->whereNull('order_products.deleted_at')
            ->select('pc.id', 'pc.title')
            ->distinct()
            ->orderBy('pc.title')
            ->get();
    }

    /**
     * Return products belonging to the given category (or all if null).
     */
    public function availableProducts(?int $categoryId = null): Collection
    {
        $q = DB::table('products')
            ->join('order_products', 'order_products.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereNull('orders.deleted_at')
            ->whereNull('order_products.deleted_at');

        if ($categoryId) {
            $q->join('product_category_children as pcc', 'pcc.product_id', '=', 'products.id')
              ->where('pcc.product_category_id', $categoryId);
        }

        return $q->select('products.id', 'products.product_name')
            ->distinct()
            ->orderBy('products.product_name')
            ->get();
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function formatDetailRow(object $row): object
    {
        $productData = is_string($row->product_data)
            ? (json_decode($row->product_data, true) ?? [])
            : ($row->product_data ?? []);

        // Apportion order-level discount to this line: proportional to sub_total share.
        $orderSubtotal = (float) ($row->order_subtotal ?? 0);
        $lineSubtotal  = (float) ($row->sub_total ?? 0);
        $orderDiscount = (float) ($row->order_discount ?? 0);
        $lineDiscount  = ($orderSubtotal > 0)
            ? round($orderDiscount * ($lineSubtotal / $orderSubtotal), 2)
            : 0;

        $extendedAmount = $lineSubtotal - $lineDiscount;

        // Delivery fee embedded in product_data
        $deliveryFee = (float) ($productData['service_option_price'] ?? 0);

        return (object) [
            'id'                 => $row->id,
            'transaction_date'   => $row->transaction_date,
            'transaction_number' => $row->transaction_number,
            'order_unique_id'    => $row->order_unique_id,
            'store_name'         => $row->store_name ?? '—',
            'customer_name'      => $row->customer_name ?? '—',
            'product_name'       => $row->product_name,
            'category_name'      => $row->category_name ?? '—',
            'item_type'          => $row->item_type ?? '—',
            'quantity'           => (int) ($row->quantity ?? 1),
            'unit_price'         => (float) ($row->unit_price ?? 0),
            'discount'           => $lineDiscount,
            'extended_amount'    => $extendedAmount,
            'delivery_fee'       => $deliveryFee,
            'tax'                => (float) ($row->tax ?? 0),
        ];
    }
}

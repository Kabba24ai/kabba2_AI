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
        // Account orders become realized revenue when a customer_account payment
        // record is created. Shown for 'paid', 'all', and 'account' status views.
        $paymentStatus = $filters['payment_status'] ?? 'paid';
        $accountPaymentsReceived = 0.0;
        if (in_array($paymentStatus, ['paid', 'all', 'account'])) {
            $accountPaymentsReceived = $this->accountPaymentsTotal($filters);
        }

        // ── Query 5: refunds against the in-scope orders ─────────────────────
        $refunds = $this->refundsTotal($filters);

        // ── Derived metrics ──────────────────────────────────────────────────
        $netSales      = max(0, $grossSales - $discounts);
        $averageTicket = $transactionCount > 0 ? $netSales / $transactionCount : 0;

        // Matches dashboard formula: SUM(grand_total) + account_payments - refunds
        // grand_total = subtotal - discount + tax  →  net_sales + tax_collected
        $totalRealizedRevenue = max(0, $netSales + $taxCollected + $accountPaymentsReceived - $refunds);

        return [
            'gross_sales'               => $grossSales,
            'discounts'                 => $discounts,
            'net_sales'                 => $netSales,
            'tax_collected'             => $taxCollected,
            'delivery_revenue'          => $deliveryRevenue,
            'damage_waiver_revenue'     => $damageWaiverRevenue,
            'track_insurance_revenue'   => $trackInsuranceRevenue,
            'tire_insurance_revenue'    => $tireInsuranceRevenue,
            'transaction_count'         => $transactionCount,
            'average_ticket'            => $averageTicket,
            'account_payments_received' => $accountPaymentsReceived,
            'payment_status'            => $paymentStatus,
            'refunds'                   => $refunds,
            'total_realized_revenue'    => $totalRealizedRevenue,
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
     * Sum of customer_accounts payments received (type='payment') in the date range.
     * This is the realized-revenue moment for Account-method orders.
     */
    public function accountPaymentsTotal(array $filters): float
    {
        $query = DB::table('customer_accounts')->where('type', 'payment');

        [$start, $end] = $this->reporting->resolveDateRange($filters);
        if ($start && $end) {
            $query->whereBetween('date', [
                $start->toDateString(),
                $end->toDateString(),
            ]);
        }

        return (float) $query->sum('amount');
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

        $currentRows = $this->reporting->baseQuery($filters)
            ->selectRaw('DATE(orders.order_date) as date, SUM(order_products.sub_total) as daily_total')
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
            ->selectRaw('DATE(orders.order_date) as date, SUM(order_products.sub_total) as daily_total')
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
            $current[]    = (float) ($currentRows[$date->toDateString()]->daily_total ?? 0);
            $previous[]   = (float) ($previousRows[$prevDate->toDateString()]->daily_total ?? 0);
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

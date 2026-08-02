<?php

namespace App\Services\Reports;

use App\Services\Reports\Concerns\NetsRefundedRevenue;
use Illuminate\Database\Eloquent\Builder;

/**
 * ProductSalesPerformanceEngine — demand-based product and category reporting.
 *
 * Revenue source: NET line revenue —
 *
 *     order_products.sub_total - order_products.pretax_discount_allocated
 *
 * `sub_total` is the GROSS merchandise value and is never reduced by a pre-tax
 * adjustment; the concession lives in `pretax_discount_allocated`. An earlier
 * version of this docblock claimed "discounts are priced-in" to `sub_total`,
 * which was NOT true — the discount engine never wrote that column, so Store
 * Credit concessions did not appear in product reporting at all.
 *
 * Scope: paid orders + account orders; excludes unpaid COD and fully-refunded orders.
 * Partial refunds: proportionally reduce each line item's net revenue.
 *
 * LEGACY ORDERS: an order discounted before per-line allocations existed
 * reports GROSS product revenue, because there is no record of which lines
 * bore the concession. The unattributed amount is disclosed by
 * {@see self::legacyUnallocatedDiscount()} rather than silently absorbed.
 *
 * This engine contains ZERO accounting formulas. It measures product demand,
 * not realized cash revenue. It will NOT reconcile to Pure Sales Summary.
 *
 * Billing Engine attribution (Phase 2B): paid extension revenue is added to
 * the parent rental's product/category/store buckets through
 * BillingRevenueAttributionService — revenue only, never qty or txn counts,
 * so an extension raises the rental's reported revenue without becoming a
 * second rental.
 */
class ProductSalesPerformanceEngine
{
    use NetsRefundedRevenue;

    public function __construct(
        private SalesReportingService $reporting,
        private BillingRevenueAttributionService $billingAttribution,
    ) {}

    // ─── Public orchestrator ──────────────────────────────────────────────────

    public function reportData(array $filters, string $view, string $storeMode): array
    {
        $kpis     = $this->buildKpis($filters);
        $allIndiv = ($storeMode === 'all_individually');

        $isProductView = in_array($view, ['products', 'category_drilldown', 'product_single'], true);

        if ($view === 'categories' && $allIndiv) {
            $viewData = $this->categoryDataByStore($filters);
        } elseif ($view === 'categories') {
            $viewData = $this->categoryData($filters);
        } elseif ($isProductView && $allIndiv) {
            $viewData = $this->productDataByStore($filters);
        } elseif ($isProductView) {
            $viewData = $this->productData($filters);
        } elseif ($view === 'stores' && $allIndiv) {
            $viewData = $this->storeData($filters);
        } else {
            $viewData = $this->categoryData($filters);
        }

        return array_merge(['kpis' => $kpis, 'view' => $view, 'store_mode' => $storeMode], $viewData);
    }

    // ─── View data builders ───────────────────────────────────────────────────

    public function categoryData(array $filters): array
    {
        $expr = $this->netRevenueExpr();

        $orderCol = ($filters['sort_by'] ?? 'revenue') === 'qty' ? 'qty' : 'revenue';

        $rows = $this->demandQuery($filters)
            ->selectRaw("
                pc.id                                 AS category_id,
                pc.title                              AS category_name,
                SUM(order_products.quantity)          AS qty,
                SUM({$expr})                          AS revenue
            ")
            ->groupBy('pc.id', 'pc.title')
            ->orderByDesc($orderCol)
            ->get();

        $rows = $this->withBillingRevenue($rows, 'category', $filters, ['category_id'], fn ($b) => (object) [
            'category_id'   => $b->category_id,
            'category_name' => $b->category_name,
            'qty'           => 0,
            'revenue'       => 0,
        ], $orderCol);

        $totalRevenue = (float) $rows->sum('revenue');
        $totalQty     = (int)   $rows->sum('qty');

        $categories = $rows->values()->map(function ($row, $i) use ($totalRevenue, $totalQty) {
            $rev = (float) $row->revenue;
            $qty = (int)   $row->qty;
            return [
                'rank'            => $i + 1,
                'category_id'     => $row->category_id,
                'category_name'   => $row->category_name ?? '—',
                'revenue'         => round($rev, 2),
                'qty'             => $qty,
                'avg_rev_per_txn' => $qty > 0 ? round($rev / $qty, 2) : 0,
                'revenue_pct'     => $totalRevenue > 0 ? round($rev / $totalRevenue * 100, 1) : 0,
                'qty_pct'         => $totalQty     > 0 ? round($qty / $totalQty     * 100, 1) : 0,
            ];
        })->toArray();

        $limit = $filters['limit'] ?? '10';
        if ($limit !== 'all' && is_numeric($limit)) {
            $categories = array_slice($categories, 0, (int) $limit);
        }

        return [
            'rows'   => $categories,
            'totals' => $this->rowTotals($rows),
            'stores' => [],
            'chart'  => $this->singleSeriesChart($categories, 'category_name'),
        ];
    }

    public function productData(array $filters): array
    {
        $expr = $this->netRevenueExpr();

        $orderCol = ($filters['sort_by'] ?? 'revenue') === 'qty' ? 'qty' : 'revenue';

        $rows = $this->demandQuery($filters)
            ->selectRaw("
                order_products.product_id             AS product_id,
                products.product_name                 AS product_name,
                products.product_type                 AS product_type,
                pc.id                                 AS category_id,
                pc.title                              AS category_name,
                SUM(order_products.quantity)          AS qty,
                SUM({$expr})                          AS revenue
            ")
            ->groupBy('order_products.product_id', 'products.product_name', 'products.product_type', 'pc.id', 'pc.title')
            ->orderByDesc($orderCol)
            ->get();

        $rows = $this->withBillingRevenue($rows, 'product', $filters, ['product_id'], fn ($b) => (object) [
            'product_id'    => $b->product_id,
            'product_name'  => $b->product_name,
            'product_type'  => $b->product_type,
            'category_id'   => $b->category_id,
            'category_name' => $b->category_name,
            'qty'           => 0,
            'revenue'       => 0,
        ], $orderCol);

        $totalRevenue = (float) $rows->sum('revenue');
        $totalQty     = (int)   $rows->sum('qty');

        $products = $rows->values()->map(function ($row, $i) use ($totalRevenue, $totalQty) {
            $rev = (float) $row->revenue;
            $qty = (int)   $row->qty;
            return [
                'rank'              => $i + 1,
                'product_id'        => $row->product_id,
                'product_name'      => $row->product_name,
                'product_type'      => $row->product_type ?? '—',
                'category_id'       => $row->category_id,
                'category_name'     => $row->category_name ?? '—',
                'revenue'           => round($rev, 2),
                'qty'               => $qty,
                'avg_rev_per_txn'   => $qty > 0 ? round($rev / $qty, 2) : 0,
                'revenue_pct'       => $totalRevenue > 0 ? round($rev / $totalRevenue * 100, 1) : 0,
                'qty_pct'           => $totalQty     > 0 ? round($qty / $totalQty     * 100, 1) : 0,
                'unit_count'        => null,   // Phase 2: Revenue Per Asset
                'revenue_per_asset' => null,   // Phase 2
            ];
        })->toArray();

        $limit = $filters['limit'] ?? '10';
        if ($limit !== 'all' && is_numeric($limit)) {
            $products = array_slice($products, 0, (int) $limit);
        }

        return [
            'rows'   => $products,
            'totals' => $this->rowTotals($rows),
            'stores' => [],
            'chart'  => $this->singleSeriesChart($products, 'product_name'),
        ];
    }

    public function categoryDataByStore(array $filters): array
    {
        $storeList  = \App\Models\Stores\Store::orderBy('store_name')->get(['id', 'store_name']);
        $storeNames = $storeList->pluck('store_name', 'id')->toArray();
        $storeIds   = $storeList->pluck('id')->toArray();

        $baseFilters = array_merge($filters, ['store' => null]);
        $expr = $this->netRevenueExpr();

        $rows = $this->demandQuery($baseFilters)
            ->selectRaw("
                pc.id                                         AS category_id,
                pc.title                                      AS category_name,
                order_products.delivery_store_id              AS store_id,
                COALESCE(stores.store_name, 'Other')          AS store_name,
                SUM(order_products.quantity)                  AS qty,
                SUM({$expr})                                  AS revenue
            ")
            ->groupBy('pc.id', 'pc.title', 'order_products.delivery_store_id', 'stores.store_name')
            ->orderByDesc('revenue')
            ->get();

        $rows = $this->withBillingRevenue($rows, 'category_store', $baseFilters, ['category_id', 'store_id'], fn ($b) => (object) [
            'category_id'   => $b->category_id,
            'category_name' => $b->category_name,
            'store_id'      => $b->store_id,
            'store_name'    => $b->store_name ?? 'Other',
            'qty'           => 0,
            'revenue'       => 0,
        ], 'revenue');

        $limit  = $filters['limit']   ?? '10';
        $sortBy = $filters['sort_by'] ?? 'revenue';
        return $this->pivotByStore($rows, 'category_id', 'category_name', $storeIds, $storeNames, $limit, $sortBy);
    }

    public function productDataByStore(array $filters): array
    {
        $storeList  = \App\Models\Stores\Store::orderBy('store_name')->get(['id', 'store_name']);
        $storeNames = $storeList->pluck('store_name', 'id')->toArray();
        $storeIds   = $storeList->pluck('id')->toArray();

        $baseFilters = array_merge($filters, ['store' => null]);
        $expr = $this->netRevenueExpr();

        $rows = $this->demandQuery($baseFilters)
            ->selectRaw("
                order_products.product_id                     AS item_id,
                products.product_name                         AS item_name,
                pc.id                                         AS category_id,
                pc.title                                      AS category_name,
                order_products.delivery_store_id              AS store_id,
                COALESCE(stores.store_name, 'Other')          AS store_name,
                SUM(order_products.quantity)                  AS qty,
                SUM({$expr})                                  AS revenue
            ")
            ->groupBy('order_products.product_id', 'products.product_name', 'pc.id', 'pc.title', 'order_products.delivery_store_id', 'stores.store_name')
            ->orderByDesc('revenue')
            ->get();

        $rows = $this->withBillingRevenue($rows, 'product_store', $baseFilters, ['item_id', 'store_id'], fn ($b) => (object) [
            'item_id'       => $b->product_id,
            'item_name'     => $b->product_name,
            'category_id'   => $b->category_id,
            'category_name' => $b->category_name,
            'store_id'      => $b->store_id,
            'store_name'    => $b->store_name ?? 'Other',
            'qty'           => 0,
            'revenue'       => 0,
        ], 'revenue', ['item_id' => 'product_id']);

        $limit  = $filters['limit']   ?? '10';
        $sortBy = $filters['sort_by'] ?? 'revenue';
        return $this->pivotByStore($rows, 'item_id', 'item_name', $storeIds, $storeNames, $limit, $sortBy);
    }

    public function storeData(array $filters): array
    {
        $baseFilters = array_merge($filters, ['store' => null]);
        $expr = $this->netRevenueExpr();

        $rows = $this->demandQuery($baseFilters)
            ->selectRaw("
                order_products.delivery_store_id              AS store_id,
                COALESCE(stores.store_name, 'Other')          AS store_name,
                SUM(order_products.quantity)                  AS qty,
                SUM({$expr})                                  AS revenue
            ")
            ->groupBy('order_products.delivery_store_id', 'stores.store_name')
            ->orderByDesc('revenue')
            ->get();

        $rows = $this->withBillingRevenue($rows, 'store', $baseFilters, ['store_id'], fn ($b) => (object) [
            'store_id'   => $b->store_id,
            'store_name' => $b->store_name ?? 'Other',
            'qty'        => 0,
            'revenue'    => 0,
        ], 'revenue');

        $totalRevenue = (float) $rows->sum('revenue');
        $totalQty     = (int)   $rows->sum('qty');

        $storeRows = $rows->values()->map(function ($row, $i) use ($totalRevenue, $totalQty) {
            $rev = (float) $row->revenue;
            $qty = (int)   $row->qty;
            return [
                'rank'        => $i + 1,
                'store_id'    => $row->store_id,
                'store_name'  => $row->store_name,
                'revenue'     => round($rev, 2),
                'qty'         => $qty,
                'revenue_pct' => $totalRevenue > 0 ? round($rev / $totalRevenue * 100, 1) : 0,
                'qty_pct'     => $totalQty     > 0 ? round($qty / $totalQty     * 100, 1) : 0,
            ];
        })->toArray();

        $chartLabels = array_column($storeRows, 'store_name');
        $chartData   = array_column($storeRows, 'revenue');

        return [
            'rows'   => $storeRows,
            'totals' => $this->rowTotals($rows),
            'stores' => [],
            'chart'  => ['labels' => $chartLabels, 'series' => [['name' => 'Revenue', 'data' => $chartData]]],
        ];
    }

    // ─── KPIs ─────────────────────────────────────────────────────────────────

    /**
     * Pre-tax concessions in scope that are NOT attributed to any product.
     *
     * These belong to orders discounted before per-line allocation existed.
     * Their product revenue above is therefore GROSS, overstated by exactly
     * this amount. Surfacing it is the difference between a report that is
     * incomplete and one that is quietly wrong: a consumer can see how much
     * revenue is unattributed and decide whether it matters, instead of being
     * told a net figure that is not net.
     *
     * Counted once per ORDER — `legacy_unallocated_pretax_discount` is an
     * order-level column, so summing it across a line-grouped query would
     * multiply it by the line count.
     */
    public function legacyUnallocatedDiscount(array $filters): float
    {
        $orderIds = $this->demandQuery($filters)
            ->distinct()
            ->pluck('orders.id');

        if ($orderIds->isEmpty()) {
            return 0.0;
        }

        return (float) \Illuminate\Support\Facades\DB::table('orders')
            ->whereIn('id', $orderIds)
            ->sum('legacy_unallocated_pretax_discount');
    }

    public function buildKpis(array $filters): array
    {
        $expr = $this->netRevenueExpr();

        // Total demand (paid + account)
        $total = $this->demandQuery($filters)
            ->selectRaw("
                SUM({$expr})                         AS total_revenue,
                SUM(order_products.quantity)         AS total_qty,
                COUNT(DISTINCT orders.id)            AS txn_count
            ")
            ->first();

        // Paid-only split
        $paidFilters = array_merge($filters, ['payment_status' => 'paid']);
        $paidRevenue = (float) ($this->demandQuery($paidFilters)
            ->selectRaw("SUM({$expr}) AS r")
            ->value('r') ?? 0);

        // Attributed billing revenue (extensions) is settled cash: it raises
        // total and paid revenue equally, never qty or transaction counts
        $billingRevenue = $this->billingAttribution->totalRevenue($filters);
        $paidRevenue   += $billingRevenue;

        $totalRevenue = (float) ($total->total_revenue ?? 0) + $billingRevenue;
        $acctRevenue  = round($totalRevenue - $paidRevenue, 2);
        $txnCount     = (int)   ($total->txn_count  ?? 0);
        $totalQty     = (int)   ($total->total_qty   ?? 0);

        // Top category by revenue
        $topCatRow = $this->demandQuery($filters)
            ->selectRaw("pc.title AS name, SUM({$expr}) AS rev")
            ->groupBy('pc.id', 'pc.title')
            ->orderByDesc('rev')
            ->first();

        // Top product by revenue
        $topProdRevRow = $this->demandQuery($filters)
            ->selectRaw("products.product_name AS name, SUM({$expr}) AS rev")
            ->groupBy('order_products.product_id', 'products.product_name')
            ->orderByDesc('rev')
            ->first();

        // Top product by qty
        $topProdQtyRow = $this->demandQuery($filters)
            ->selectRaw("products.product_name AS name, SUM(order_products.quantity) AS qty")
            ->groupBy('order_products.product_id', 'products.product_name')
            ->orderByDesc('qty')
            ->first();

        $paidPct = $totalRevenue > 0 ? round($paidRevenue / $totalRevenue * 100, 1) : 0;
        $acctPct = $totalRevenue > 0 ? round($acctRevenue  / $totalRevenue * 100, 1) : 0;

        return [
            'total_revenue'      => round($totalRevenue, 2),
            'paid_revenue'       => round($paidRevenue, 2),
            'account_revenue'    => round($acctRevenue, 2),
            'paid_pct'           => $paidPct,
            'account_pct'        => $acctPct,
            'total_qty'          => $totalQty,
            'avg_rev_per_txn'    => $txnCount > 0 ? round($totalRevenue / $txnCount, 2) : 0,
            'top_category'       => $topCatRow?->name ?? '—',
            'top_product_by_rev' => $topProdRevRow?->name ?? '—',
            'top_product_by_qty' => $topProdQtyRow?->name ?? '—',
            'txn_count'          => $txnCount,
        ];
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Merge attributed Billing Engine revenue (extensions) into grouped demand
     * rows: matching buckets gain revenue only (qty/txn untouched); buckets
     * with billing revenue but no demand rows in the period are appended so
     * the revenue still surfaces under the parent rental's identity.
     *
     * $billingKeyMap maps a row match-key to the billing row's field name when
     * the aliases differ (e.g. item_id → product_id in the by-store pivots).
     */
    private function withBillingRevenue(
        \Illuminate\Support\Collection $rows,
        string $groupBy,
        array $filters,
        array $matchKeys,
        \Closure $newRow,
        string $sortCol,
        array $billingKeyMap = [],
    ): \Illuminate\Support\Collection {
        $billing = $this->billingAttribution->groupedRevenue($groupBy, $filters);

        if ($billing->isEmpty()) {
            return $rows;
        }

        $rows = $rows->values();

        foreach ($billing as $b) {
            $existing = $rows->first(function ($r) use ($b, $matchKeys, $billingKeyMap) {
                foreach ($matchKeys as $key) {
                    $billingKey = $billingKeyMap[$key] ?? $key;
                    if (($r->$key ?? null) != ($b->$billingKey ?? null)) {
                        return false;
                    }
                }
                return true;
            });

            if ($existing) {
                $existing->revenue = (float) $existing->revenue + (float) $b->revenue;
            } else {
                $rows->push($newRow($b));
                $rows->last()->revenue = (float) $b->revenue;
            }
        }

        return $rows->sortByDesc(fn ($r) => (float) ($r->{$sortCol} ?? 0))->values();
    }

    /**
     * Base demand query: paid + account orders, fully-refunded orders excluded,
     * partial refund subqueries joined for revenue reduction — see
     * NetsRefundedRevenue::applyRefundNetting()/netRevenueExpr(), the single
     * canonical location for this netting now shared with
     * ProductSalesRankingReport and SalesByStoresReport.
     */
    private function demandQuery(array $filters): Builder
    {
        $paymentStatus = $filters['payment_status'] ?? 'paid_and_account';
        $mergedFilters = array_merge($filters, ['payment_status' => $paymentStatus]);

        // Replace all_individually store marker — per-store grouping is done by
        // the calling method; the base query should have no store filter.
        if (($mergedFilters['store'] ?? null) === 'all_individually') {
            $mergedFilters['store'] = null;
        }

        return $this->applyRefundNetting($this->reporting->baseQuery($mergedFilters));
    }

    /**
     * Pivot a flat store+item result set into a nested structure with store columns.
     */
    private function pivotByStore(
        \Illuminate\Support\Collection $rows,
        string $idKey,
        string $nameKey,
        array $storeIds,
        array $storeNames,
        string $limit = 'all',
        string $sortBy = 'revenue'
    ): array {
        $grouped = [];
        foreach ($rows as $row) {
            $id = $row->$idKey;
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id'            => $id,
                    'name'          => $row->$nameKey,
                    'category_id'   => $row->category_id ?? null,
                    'category_name' => $row->category_name ?? null,
                    'stores'        => [],
                    'total_revenue' => 0,
                    'total_qty'     => 0,
                ];
            }
            $grouped[$id]['stores'][$row->store_id ?? 'other'] = [
                'revenue' => round((float) $row->revenue, 2),
                'qty'     => (int) $row->qty,
            ];
            $grouped[$id]['total_revenue'] += (float) $row->revenue;
            $grouped[$id]['total_qty']     += (int)   $row->qty;
        }

        usort($grouped, $sortBy === 'qty'
            ? fn($a, $b) => $b['total_qty']     <=> $a['total_qty']
            : fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']
        );

        if ($limit !== 'all' && is_numeric($limit)) {
            $grouped = array_slice($grouped, 0, (int) $limit);
        }

        $totalAllRev = array_sum(array_column($grouped, 'total_revenue'));
        $totalAllQty = array_sum(array_column($grouped, 'total_qty'));

        foreach ($grouped as $i => &$item) {
            $item['rank']          = $i + 1;
            $item['revenue_pct']   = $totalAllRev > 0 ? round($item['total_revenue'] / $totalAllRev * 100, 1) : 0;
            $item['qty_pct']       = $totalAllQty > 0 ? round($item['total_qty']     / $totalAllQty * 100, 1) : 0;
            $item['total_revenue'] = round($item['total_revenue'], 2);
            // Zero-fill stores with no data for this item
            foreach ($storeIds as $sid) {
                if (!isset($item['stores'][$sid])) {
                    $item['stores'][$sid] = ['revenue' => 0, 'qty' => 0];
                }
            }
        }
        unset($item);

        // Build ApexCharts multi-series: one series per store
        $chartLabels = array_map(fn($item) => $item['name'], $grouped);
        $chartSeries = [];
        foreach ($storeIds as $sid) {
            $chartSeries[] = [
                'name' => $storeNames[$sid] ?? 'Other',
                'data' => array_map(fn($item) => $item['stores'][$sid]['revenue'] ?? 0, $grouped),
            ];
        }

        return [
            'rows'   => array_values($grouped),
            'totals' => ['revenue' => round($totalAllRev, 2), 'qty' => $totalAllQty],
            'stores' => array_values(array_map(fn($sid) => ['id' => $sid, 'name' => $storeNames[$sid] ?? 'Other'], $storeIds)),
            'chart'  => ['labels' => $chartLabels, 'series' => $chartSeries],
        ];
    }

    private function singleSeriesChart(array $rows, string $labelKey): array
    {
        return [
            'labels' => array_column($rows, $labelKey),
            'series' => [['name' => 'Product Revenue', 'data' => array_column($rows, 'revenue')]],
        ];
    }

    private function rowTotals(\Illuminate\Support\Collection $rows): array
    {
        return [
            'revenue' => round((float) $rows->sum('revenue'), 2),
            'qty'     => (int) $rows->sum('qty'),
        ];
    }

    /**
     * Phase 2 placeholder — Revenue Per Asset.
     * Returns active fleet unit count per product_id.
     */
    private function fleetCountsByProduct(array $productIds): array
    {
        // Phase 2:
        // return \App\Models\MaintenanceManagement\Equipment::whereIn('assigned_product_id', $productIds)
        //     ->whereIn('current_status', ['Available', 'Rented', 'Maintenance'])
        //     ->selectRaw('assigned_product_id, COUNT(*) as unit_count')
        //     ->groupBy('assigned_product_id')
        //     ->pluck('unit_count', 'assigned_product_id')
        //     ->toArray();
        return [];
    }
}

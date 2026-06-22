<?php

namespace App\Services\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * ProductSalesPerformanceEngine — demand-based product and category reporting.
 *
 * Revenue source: order_products.sub_total (discounts are priced-in).
 * Scope: paid orders + account orders; excludes unpaid COD and fully-refunded orders.
 * Partial refunds: proportionally reduce each line item's sub_total.
 *
 * This engine contains ZERO accounting formulas. It measures product demand,
 * not realized cash revenue. It will NOT reconcile to Pure Sales Summary.
 */
class ProductSalesPerformanceEngine
{
    public function __construct(private SalesReportingService $reporting) {}

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

        $totalRevenue = (float) ($total->total_revenue ?? 0);
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
     * Base demand query: paid + account orders, fully-refunded orders excluded,
     * partial refund subqueries joined for revenue reduction.
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

        $query = $this->reporting->baseQuery($mergedFilters);

        // Exclude fully-refunded orders
        $query->whereNotExists(function ($sub) {
            $sub->selectRaw('1')
                ->from('order_payments as rp')
                ->whereColumn('rp.order_id', 'orders.id')
                ->where('rp.status', 'Refunded')
                ->whereNull('rp.deleted_at');
        });

        // Join subquery: gross sub_total per order (needed for partial-refund ratio)
        $query->leftJoinSub(
            DB::table('order_products as op_gross')
                ->selectRaw('order_id, SUM(sub_total) AS order_gross')
                ->whereNull('deleted_at')
                ->groupBy('order_id'),
            'order_totals',
            'order_totals.order_id',
            '=',
            'orders.id'
        );

        // Join subquery: sum of partial refund amounts per order
        $query->leftJoinSub(
            DB::table('order_payments as op_ref')
                ->selectRaw('order_id, SUM(refund_amount) AS partial_refunded')
                ->where('status', 'Partial Refund')
                ->whereNull('deleted_at')
                ->groupBy('order_id'),
            'order_refunds',
            'order_refunds.order_id',
            '=',
            'orders.id'
        );

        return $query;
    }

    /**
     * Per-row net revenue expression (no SUM — callers wrap in SUM as needed).
     *
     * ratio = (order_gross - partial_refunded) / order_gross
     * net   = sub_total * GREATEST(0, ratio)   — floor at 0, never negative
     *
     * Falls back to sub_total when order_totals is NULL (no join match).
     */
    private function netRevenueExpr(): string
    {
        return "order_products.sub_total * GREATEST(0,
            (COALESCE(order_totals.order_gross, order_products.sub_total) - COALESCE(order_refunds.partial_refunded, 0))
            / NULLIF(COALESCE(order_totals.order_gross, order_products.sub_total), 0)
        )";
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

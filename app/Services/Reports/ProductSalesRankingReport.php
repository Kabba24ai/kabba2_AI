<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Product Sales Ranking — Report 4.
 *
 * Answers: "Which products are our best and worst sellers by revenue or volume?"
 * Returns one row per product with qty sold, revenue, avg price, and share metrics.
 */
class ProductSalesRankingReport
{
    public function __construct(private SalesReportingService $reporting) {}

    public function rankingData(array $filters): array
    {
        $sortBy  = $filters['sort_by'] ?? 'revenue';
        $orderBy = $sortBy === 'qty' ? 'qty_sold' : 'revenue';

        $rows = $this->reporting->baseQuery($filters)
            ->selectRaw("
                order_products.product_id,
                products.product_name,
                products.product_type,
                SUM(order_products.quantity)   AS qty_sold,
                SUM(order_products.sub_total)  AS revenue
            ")
            ->groupBy('order_products.product_id', 'products.product_name', 'products.product_type')
            ->orderByDesc($orderBy)
            ->get();

        $totalRevenue = (float) $rows->sum('revenue');
        $totalQty     = (int)   $rows->sum('qty_sold');

        $products = $rows->values()->map(function ($row, $index) use ($totalRevenue, $totalQty) {
            $rev = (float) $row->revenue;
            $qty = (int)   $row->qty_sold;
            return [
                'rank'          => $index + 1,
                'product_id'    => $row->product_id,
                'product_name'  => $row->product_name,
                'product_type'  => $row->product_type ?? '—',
                'qty_sold'      => $qty,
                'revenue'       => round($rev, 2),
                'avg_price'     => $qty > 0 ? round($rev / $qty, 2) : 0,
                'revenue_share' => $totalRevenue > 0 ? round(($rev / $totalRevenue) * 100, 1) : 0,
                'qty_share'     => $totalQty     > 0 ? round(($qty / $totalQty)     * 100, 1) : 0,
            ];
        })->toArray();

        return [
            'products' => $products,
            'totals'   => [
                'product_count' => count($products),
                'revenue'       => round($totalRevenue, 2),
                'qty_sold'      => $totalQty,
                'avg_price'     => $totalQty > 0 ? round($totalRevenue / $totalQty, 2) : 0,
            ],
            'sort_by'          => $sortBy,
            'date_range_label' => $this->reporting->dateRangeLabel($filters),
        ];
    }

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
}

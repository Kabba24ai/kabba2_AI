<?php

namespace App\Services\Reports;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pure Sales Summary — Report 1.
 *
 * Answers: "How much revenue did we actually sell in this period?"
 *
 * Calculation responsibility has moved to SalesReportEngineV2.
 * This class handles presentation concerns: detail grid, export data,
 * and dropdown population. kpis() and trendData() delegate to the engine.
 */
class PureSalesSummaryReport
{
    public function __construct(
        private SalesReportingService $reporting,
        private SalesReportEngineV2   $engine,
    ) {}

    // ─── KPI Aggregation ────────────────────────────────────────────────────

    /**
     * Return all KPI card values for the given filters.
     * Delegates entirely to SalesReportEngineV2 — the single source of truth.
     */
    public function kpis(array $filters): array
    {
        return $this->engine->kpis($filters);
    }

    // ─── Sales Trend ─────────────────────────────────────────────────────────

    /**
     * Daily revenue trend for the selected period plus the preceding equal-length period.
     * Delegates entirely to SalesReportEngineV2 — the single source of truth.
     *
     * Guarantees:
     *   trend['netSales'] === kpis()['net_sales']         (same snapshot engine)
     *   array_sum(trend['current']) === trend['netSales']  (no per-day floors)
     */
    public function trendData(array $filters): array
    {
        return $this->engine->trendData($filters);
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

    // ─── Filter Helpers ──────────────────────────────────────────────────────

    /**
     * Return categories that have at least one product with finalized orders.
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
        $deliveryFee    = (float) ($productData['service_option_price'] ?? 0);

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

<?php

namespace App\Http\Controllers\Admin\Reports\SalesReports\ProductPerformance;

use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Services\Reports\ProductSalesPerformanceEngine;
use App\Services\Reports\SalesReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IndexController extends Controller
{
    public function __construct(
        private ProductSalesPerformanceEngine $engine,
        private SalesReportingService         $reporting,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            // AJAX: cascade product dropdown when category changes
            if ($request->boolean('ajax_products')) {
                $products = $this->reporting->availableProducts(
                    $request->input('category') ? (int) $request->input('category') : null
                );
                return response()->json(['products' => $products]);
            }

            $filters   = $this->extractFilters($request);
            $view      = $request->input('view', 'categories'); // tab: categories | products | stores
            $storeMode = ($filters['store'] === 'all_individually') ? 'all_individually' : 'single';

            // Stores tab only valid in all_individually mode
            if ($view === 'stores' && $storeMode !== 'all_individually') {
                $view = 'categories';
            }

            // Determine effective rendering mode from tab + filter state.
            // When on the "categories" tab with a category selected, drill into
            // products within that category rather than producing a 1-bar chart.
            $hasCategory = !empty($filters['category']);
            $hasProduct  = !empty($filters['product']);

            // When a category or product filter is active on the categories tab,
            // pass 'products' to the engine — it routes to productData() which
            // has existed since initial deployment and correctly scopes by the
            // category/product already present in $filters.
            if ($view === 'categories' && ($hasCategory || $hasProduct)) {
                $effectiveView = 'products';
            } else {
                $effectiveView = $view; // 'categories' | 'products' | 'stores'
            }

            $data = $this->engine->reportData($filters, $effectiveView, $storeMode);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'data' => $data]);
            }

            return view('admin.reports.sales_reports.product_performance.index', [
                'data'           => $data,
                'filters'        => $filters,
                'view'           => $view,          // tab selection (for active state)
                'effectiveView'  => $effectiveView, // actual rendering mode
                'storeMode'      => $storeMode,
                'stores'         => Store::orderBy('store_name')->get(['id', 'store_name']),
                'categories'     => $this->reporting->availableCategories(),
                'products'       => $this->reporting->availableProducts($filters['category'] ?? null),
                'dateRangeLabel' => $this->reporting->dateRangeLabel($filters),
            ]);

        } catch (\Throwable $e) {
            Log::error('Product Performance report error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            if ($request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }

            throw $e;
        }
    }

    private function extractFilters(Request $request): array
    {
        return [
            'date_range'     => $request->input('date_range', 'mtd'),
            'start_date'     => $request->input('start_date'),
            'end_date'       => $request->input('end_date'),
            'month'          => $request->input('month'),
            'year'           => $request->input('year'),
            'store'          => $request->input('store') ?: null,
            'sale_type'      => $request->input('sale_type', 'all'),
            'category'       => $request->input('category') ? (int) $request->input('category') : null,
            'product'        => $request->input('product')  ? (int) $request->input('product')  : null,
            'payment_status' => 'paid_and_account', // always — product demand scope
        ];
    }
}

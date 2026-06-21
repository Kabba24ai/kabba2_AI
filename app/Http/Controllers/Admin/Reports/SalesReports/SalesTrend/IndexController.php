<?php

namespace App\Http\Controllers\Admin\Reports\SalesReports\SalesTrend;

use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Services\Reports\SalesTrendAnalysisEngine;
use App\Services\Reports\SalesReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IndexController extends Controller
{
    public function __construct(
        private SalesTrendAnalysisEngine $engine,
        private SalesReportingService    $reporting,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            // Product list dependency (category → product cascade)
            if ($request->boolean('ajax_products')) {
                $products = $this->reporting->availableProducts(
                    $request->input('category') ? (int) $request->input('category') : null
                );
                return response()->json(['products' => $products]);
            }

            $primaryYear  = (int) ($request->input('year', Carbon::now()->year));
            $compareYears = $this->sanitizeCompareYears(
                $request->input('compare_years', []),
                $primaryYear
            );
            $filters         = $this->extractFilters($request);
            $compareCategory = $request->input('compare_category') ? (int)$request->input('compare_category') : null;
            $compareProduct  = $request->input('compare_product')  ? (int)$request->input('compare_product')  : null;

            // Pre-load shared collections (used in mode dispatch and view).
            $categories  = $this->reporting->availableCategories();
            $products    = $this->reporting->availableProducts($filters['category'] ?? null);
            $allProducts = $filters['category']
                ? $this->reporting->availableProducts(null)
                : $products;

            // ── Mode dispatch (priority: product > category > by_store > year) ──
            $allIndividually = ($filters['store'] === 'all_individually');

            if ($filters['product'] && $compareProduct && $filters['product'] !== $compareProduct) {
                // Product comparison
                $compareYears = [];
                $prodA = $allProducts->firstWhere('id', $filters['product']);
                $prodB = $allProducts->firstWhere('id', $compareProduct);
                $nameA = $prodA ? $prodA->product_name : ('Product ' . $filters['product']);
                $nameB = $prodB ? $prodB->product_name : ('Product ' . $compareProduct);
                $data  = $this->engine->productComparison(
                    $primaryYear, $filters['product'], $nameA, $compareProduct, $nameB, $filters
                );
            } elseif ($filters['category'] && $compareCategory && $filters['category'] !== $compareCategory) {
                // Category comparison
                $compareYears = [];
                $catA  = $categories->firstWhere('id', $filters['category']);
                $catB  = $categories->firstWhere('id', $compareCategory);
                $nameA = $catA ? $catA->title : ('Category ' . $filters['category']);
                $nameB = $catB ? $catB->title : ('Category ' . $compareCategory);
                $data  = $this->engine->categoryComparison(
                    $primaryYear, $filters['category'], $nameA, $compareCategory, $nameB, $filters
                );
            } elseif ($allIndividually) {
                // All Individually store mode
                $filters['store'] = null;
                $compareYears     = [];
                $data = $this->engine->reportDataByStore($primaryYear, $filters);
            } else {
                // Default year / YOY mode
                $data = $this->engine->reportData($primaryYear, $compareYears, $filters);
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'data' => $data]);
            }

            return view('admin.reports.sales_reports.sales_trend.index', [
                'data'            => $data,
                'primaryYear'     => $primaryYear,
                'compareYears'    => $compareYears,
                'stores'          => Store::orderBy('store_name')->get(['id', 'store_name']),
                'categories'      => $categories,
                'products'        => $products,
                'allProducts'     => $allProducts,
                'availableYears'  => $this->buildAvailableYears(),
                'filters'         => $filters,
                'selectedStore'   => $request->input('store', ''),
                'compareCategory' => $compareCategory,
                'compareProduct'  => $compareProduct,
            ]);

        } catch (\Throwable $e) {
            Log::error('Sales Trend Analysis error', [
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

    /**
     * Clamp compare_years: max 2, no duplicates, no primary year, valid ints.
     * Accepts array from checkbox inputs or comma-separated string.
     */
    private function sanitizeCompareYears(mixed $raw, int $primaryYear): array
    {
        if (!is_array($raw)) {
            $raw = array_filter(explode(',', (string) $raw));
        }

        return array_values(array_unique(
            array_slice(
                array_filter(
                    array_map('intval', $raw),
                    fn($y) => $y > 2000 && $y !== $primaryYear
                ),
                0, 2
            )
        ));
    }

    private function extractFilters(Request $request): array
    {
        return [
            'store'          => $request->input('store')    ?: null,
            'sale_type'      => $request->input('sale_type', 'all'),
            'category'       => $request->input('category') ? (int) $request->input('category') : null,
            'product'        => $request->input('product')  ? (int) $request->input('product')  : null,
            'payment_status' => $request->input('payment_status', 'paid'),
        ];
    }

    /** 2024 up to 2040 — ascending, future-proofed range. */
    private function buildAvailableYears(): array
    {
        return range(2024, 2040);
    }
}

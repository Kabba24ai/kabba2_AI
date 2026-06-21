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
            $filters = $this->extractFilters($request);

            $data = $this->engine->reportData($primaryYear, $compareYears, $filters);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'data' => $data]);
            }

            return view('admin.reports.sales_reports.sales_trend.index', [
                'data'           => $data,
                'primaryYear'    => $primaryYear,
                'compareYears'   => $compareYears,
                'stores'         => Store::orderBy('store_name')->get(['id', 'store_name']),
                'categories'     => $this->reporting->availableCategories(),
                'products'       => $this->reporting->availableProducts($filters['category'] ?? null),
                'availableYears' => $this->buildAvailableYears(),
                'filters'        => $filters,
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

    /** 2040 down to 2024 — future-proofed range. */
    private function buildAvailableYears(): array
    {
        return range(2040, 2024);
    }
}

<?php

namespace App\Http\Controllers\Admin\Reports\SalesReports\SalesTrend;

use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Services\Reports\SalesTrendReport;
use App\Services\Reports\SalesReportingService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct(
        private SalesTrendReport      $report,
        private SalesReportingService $reporting,
    ) {}

    public function __invoke(Request $request)
    {
        $filters = $this->extractFilters($request);

        if ($request->has('ajax_products')) {
            $products = $this->reporting->availableProducts($filters['category'] ?? null);
            return response()->json(['products' => $products]);
        }

        if ($request->ajax()) {
            $trend = $this->report->trendData($filters);
            return response()->json(['success' => true, 'trend' => $trend]);
        }

        $trend      = $this->report->trendData($filters);
        $stores     = Store::orderBy('store_name')->get(['id', 'store_name']);
        $categories = $this->reporting->availableCategories();
        $products   = $this->reporting->availableProducts($filters['category'] ?? null);

        return view('admin.reports.sales_reports.sales_trend.index', [
            'filters'    => $filters,
            'trend'      => $trend,
            'stores'     => $stores,
            'categories' => $categories,
            'products'   => $products,
        ]);
    }

    private function extractFilters(Request $request): array
    {
        return [
            'date_range'      => $request->input('date_range', 'mtd'),
            'start_date'      => $request->input('start_date'),
            'end_date'        => $request->input('end_date'),
            'store'           => $request->input('store'),
            'item_type'       => $request->input('item_type', 'all'),
            'category'        => $request->input('category') ? (int) $request->input('category') : null,
            'product'         => $request->input('product') ? (int) $request->input('product') : null,
            'sale_type'       => $request->input('sale_type', 'all'),
            'damage_waiver'   => $request->input('damage_waiver', 'all'),
            'track_insurance' => $request->input('track_insurance', 'all'),
            'delivery'        => $request->input('delivery', 'all'),
            'shipping'        => $request->input('shipping', 'all'),
            'group_by'        => $request->input('group_by', 'day'),
            'payment_status'  => $request->input('payment_status', 'paid'),
        ];
    }
}

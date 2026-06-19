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

        if ($request->ajax()) {
            $trend = $this->report->trendData($filters);
            return response()->json(['success' => true, 'trend' => $trend]);
        }

        $trend  = $this->report->trendData($filters);
        $stores = Store::orderBy('store_name')->get(['id', 'store_name']);

        return view('admin.reports.sales_reports.sales_trend.index', [
            'filters' => $filters,
            'trend'   => $trend,
            'stores'  => $stores,
        ]);
    }

    private function extractFilters(Request $request): array
    {
        return [
            'date_range'     => $request->input('date_range', 'mtd'),
            'start_date'     => $request->input('start_date'),
            'end_date'       => $request->input('end_date'),
            'store'          => $request->input('store'),
            'group_by'       => $request->input('group_by', 'day'),
            'payment_status' => $request->input('payment_status', 'paid'),
        ];
    }
}

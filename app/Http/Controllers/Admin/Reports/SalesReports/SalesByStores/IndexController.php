<?php

namespace App\Http\Controllers\Admin\Reports\SalesReports\SalesByStores;

use App\Http\Controllers\Controller;
use App\Services\Reports\SalesByStoresReport;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct(private SalesByStoresReport $report) {}

    public function __invoke(Request $request)
    {
        $filters = $this->extractFilters($request);

        if ($request->ajax()) {
            $data = $this->report->storeData($filters);
            return response()->json(['success' => true, 'data' => $data]);
        }

        $data = $this->report->storeData($filters);

        return view('admin.reports.sales_reports.sales_by_stores.index', [
            'filters' => $filters,
            'data'    => $data,
        ]);
    }

    private function extractFilters(Request $request): array
    {
        return [
            'date_range'     => $request->input('date_range', 'mtd'),
            'start_date'     => $request->input('start_date'),
            'end_date'       => $request->input('end_date'),
            'payment_status' => $request->input('payment_status', 'paid'),
        ];
    }
}

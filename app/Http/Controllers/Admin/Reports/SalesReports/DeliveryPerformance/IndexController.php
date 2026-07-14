<?php

namespace App\Http\Controllers\Admin\Reports\SalesReports\DeliveryPerformance;

use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Services\Reports\DeliveryPerformanceEngine;
use App\Services\Reports\SalesReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IndexController extends Controller
{
    public function __construct(
        private DeliveryPerformanceEngine $engine,
        private SalesReportingService     $reporting,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $filters = $this->extractFilters($request);
            $data    = $this->engine->reportData($filters);

            if ($request->ajax()) {
                return response()->json([
                    'success'          => true,
                    'data'             => $data,
                    'date_range_label' => $this->reporting->dateRangeLabel($filters),
                ]);
            }

            return view('admin.reports.sales_reports.delivery_performance.index', [
                'data'           => $data,
                'filters'        => $filters,
                'stores'         => Store::orderBy('store_name')->get(['id', 'store_name']),
                'dateRangeLabel' => $this->reporting->dateRangeLabel($filters),
            ]);

        } catch (\Throwable $e) {
            Log::error('Delivery Performance report error', [
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
            'date_range' => $request->input('date_range', 'mtd'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
            'month'      => $request->input('month') ? (int) $request->input('month') : null,
            'year'       => $request->input('year')  ? (int) $request->input('year')  : null,
            'store'      => $request->input('store') ? (int) $request->input('store') : null,
        ];
    }
}

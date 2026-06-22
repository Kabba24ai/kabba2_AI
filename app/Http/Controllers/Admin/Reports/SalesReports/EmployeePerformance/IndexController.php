<?php

namespace App\Http\Controllers\Admin\Reports\SalesReports\EmployeePerformance;

use App\Helpers\CustomHelper;
use App\Http\Controllers\Controller;
use App\Models\Stores\Store;
use App\Services\Reports\EmployeePerformanceEngine;
use App\Services\Reports\SalesReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IndexController extends Controller
{
    public function __construct(
        private EmployeePerformanceEngine $engine,
        private SalesReportingService     $reporting,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $filters     = $this->extractFilters($request);
            $employeeIds = $this->extractEmployeeIds($request);
            $storeMode   = ($filters['store'] === 'all_individually') ? 'all_individually' : 'single';

            // Temporarily clear store filter for all_individually — engine handles store iteration
            if ($storeMode === 'all_individually') {
                $filters['store'] = null;
            }

            $data = $this->engine->reportData($filters, $employeeIds, $storeMode);

            if ($request->ajax()) {
                return response()->json([
                    'success'          => true,
                    'data'             => $this->formatData($data),
                    'date_range_label' => $this->reporting->dateRangeLabel($filters),
                ]);
            }

            return view('admin.reports.sales_reports.employee_performance.index', [
                'data'           => $this->formatData($data),
                'filters'        => $filters,
                'employeeIds'    => $employeeIds,
                'storeMode'      => $storeMode,
                'stores'         => Store::orderBy('store_name')->get(['id', 'store_name']),
                'employees'      => $this->engine->availableEmployees(),
                'categories'     => $this->reporting->availableCategories(),
                'products'       => $this->reporting->availableProducts($filters['category'] ?? null),
                'dateRangeLabel' => $this->reporting->dateRangeLabel($filters),
            ]);

        } catch (\Throwable $e) {
            Log::error('Employee Performance report error', [
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

    // ─── Private Helpers ──────────────────────────────────────────────────────

    private function extractFilters(Request $request): array
    {
        return [
            'date_range'  => $request->input('date_range', 'mtd'),
            'start_date'  => $request->input('start_date'),
            'end_date'    => $request->input('end_date'),
            'month'       => $request->input('month') ? (int) $request->input('month') : null,
            'year'        => $request->input('year')  ? (int) $request->input('year')  : null,
            'store'       => $request->input('store') ?: null,
            'sale_type'   => $request->input('sale_type', 'all'),
            'category'    => $request->input('category') ? (int) $request->input('category') : null,
            'product'     => $request->input('product')  ? (int) $request->input('product')  : null,
        ];
    }

    /**
     * Extract and deduplicate up to 3 employee IDs from the request.
     */
    private function extractEmployeeIds(Request $request): array
    {
        $ids = [];
        foreach (['employee_1', 'employee_2', 'employee_3'] as $key) {
            $v = $request->input($key);
            if ($v && (int) $v > 0) {
                $ids[] = (int) $v;
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Format all raw float values for display and build chart-ready arrays.
     */
    private function formatData(array $data): array
    {
        $employees = array_map(fn($emp) => [
            'id'                => $emp['id'],
            'name'              => $emp['name'],
            'employee_code'     => $emp['employee_code'],
            'rank'              => $emp['rank'] ?? 0,
            // Raw floats for chart rendering
            'qualified_revenue_raw' => round($emp['qualified_revenue'], 2),
            'orders_closed_raw'     => $emp['orders_closed'],
            'avg_order_value_raw'   => round($emp['avg_order_value'], 2),
            'paid_revenue_raw'      => round($emp['paid_revenue'], 2),
            'account_revenue_raw'   => round($emp['account_revenue'], 2),
            'revenue_at_risk_raw'   => round($emp['revenue_at_risk'], 2),
            'pod_total'             => $emp['pod_total'],
            'pod_converted'         => $emp['pod_converted'],
            'pod_rate'              => $emp['pod_rate'],
            'monthly'               => $emp['monthly'],
            'categories'            => $emp['categories'],
            // Formatted for KPI cards
            'qualified_revenue' => CustomHelper::formatCurrency($emp['qualified_revenue']),
            'orders_closed'     => number_format($emp['orders_closed']),
            'avg_order_value'   => CustomHelper::formatCurrency($emp['avg_order_value']),
            'paid_revenue'      => CustomHelper::formatCurrency($emp['paid_revenue']),
            'account_revenue'   => CustomHelper::formatCurrency($emp['account_revenue']),
            'revenue_at_risk'   => CustomHelper::formatCurrency($emp['revenue_at_risk']),
        ], $data['employees']);

        return [
            'employees'  => $employees,
            'months'     => $data['months'],
            'store_mode' => $data['store_mode'],
            'stores'     => $data['stores'],
            'store_data' => $this->formatStoreData($data['store_data']),
        ];
    }

    private function formatStoreData(array $storeData): array
    {
        return array_map(function ($store) {
            $store['employees'] = array_map(fn($emp) => array_merge($emp, [
                'revenue_raw' => round($emp['revenue'], 2),
                'revenue'     => CustomHelper::formatCurrency($emp['revenue']),
            ]), $store['employees']);
            return $store;
        }, $storeData);
    }
}

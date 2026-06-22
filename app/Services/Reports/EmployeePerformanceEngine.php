<?php

namespace App\Services\Reports;

use App\Models\Iam\Personnel\User;
use App\Models\Stores\Store;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * EmployeePerformanceEngine — orchestrates all employee-level sales reporting.
 *
 * All revenue calculations are delegated to SalesReportEngineV2 via the same
 * employee_id filter path injected into SalesReportingService::applyFilters().
 * This guarantees that sum(employee qualified revenues) reconciles with
 * Pure Sales Summary for the same date range and store filter.
 */
class EmployeePerformanceEngine
{
    private const MONTHS = [
        1 => 'Jan', 2 => 'Feb',  3 => 'Mar',  4 => 'Apr',
        5 => 'May', 6 => 'Jun',  7 => 'Jul',  8 => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    public function __construct(
        private SalesReportEngineV2   $engine,
        private SalesReportingService $reporting,
    ) {}

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Return all users who have created at least one non-deleted order.
     */
    public function availableEmployees(): Collection
    {
        return DB::table('users')
            ->join('orders', function ($join) {
                $join->on('orders.created_by_id', '=', 'users.id')
                     ->where('orders.created_by_type', User::class);
            })
            ->whereNull('orders.deleted_at')
            ->whereNull('users.deleted_at')
            ->select(
                'users.id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS name"),
                'users.employee_code',
            )
            ->distinct()
            ->orderBy('users.first_name')
            ->get();
    }

    /**
     * Build the complete report payload for the controller/view.
     *
     * $employeeIds — up to 3 IDs to compare (deduped, empty = show all with data).
     * $storeMode   — 'single' | 'all_individually'
     */
    public function reportData(array $filters, array $employeeIds, string $storeMode): array
    {
        $allEmployees = $this->availableEmployees();

        // Resolve which employees to show
        $employees = $this->resolveEmployees($allEmployees, $employeeIds);

        $employeeRows = [];
        foreach ($employees as $emp) {
            $employeeRows[] = $this->buildEmployeeRow($filters, $emp);
        }

        // Sort by qualified revenue desc
        usort($employeeRows, fn($a, $b) => $b['qualified_revenue'] <=> $a['qualified_revenue']);

        // Assign rank
        foreach ($employeeRows as $i => &$row) {
            $row['rank'] = $i + 1;
        }
        unset($row);

        $result = [
            'employees'  => $employeeRows,
            'months'     => array_values(self::MONTHS),
            'store_mode' => $storeMode,
            'stores'     => [],
            'store_data' => [],
        ];

        // Chart 6 — per-store per-employee breakdown
        if ($storeMode === 'all_individually') {
            $stores = Store::orderBy('store_name')->get(['id', 'store_name']);
            $result['stores'] = $stores->map(fn($s) => ['id' => $s->id, 'name' => $s->store_name])->toArray();
            $result['store_data'] = $this->buildStoreBreakdown($filters, $employees, $stores);
        }

        return $result;
    }

    // ─── Private: Per-employee data ───────────────────────────────────────────

    private function buildEmployeeRow(array $filters, object $emp): array
    {
        $empId = $emp->id;

        // Qualified revenue: paid + account + converted COD
        $qualFilters = array_merge($filters, ['employee_id' => $empId, 'payment_status' => 'paid_and_account']);
        $qualKpis    = $this->engine->kpis($qualFilters);

        // Paid-only revenue
        $paidFilters = array_merge($filters, ['employee_id' => $empId, 'payment_status' => 'paid']);
        $paidKpis    = $this->engine->kpis($paidFilters);

        // Account-only revenue
        $acctFilters = array_merge($filters, ['employee_id' => $empId, 'payment_status' => 'account']);
        $acctKpis    = $this->engine->kpis($acctFilters);

        // Revenue at risk: open/unconverted COD
        $podFilters  = array_merge($filters, ['employee_id' => $empId, 'payment_status' => 'pod']);
        $podKpis     = $this->engine->kpis($podFilters);

        $podStats = $this->podStats($filters, $empId);

        return [
            'id'                => $empId,
            'name'              => $emp->name,
            'employee_code'     => $emp->employee_code ?? '',
            'qualified_revenue' => $qualKpis['net_sales'],
            'orders_closed'     => $qualKpis['transaction_count'],
            'avg_order_value'   => $qualKpis['average_ticket'],
            'paid_revenue'      => $paidKpis['net_sales'],
            'account_revenue'   => $acctKpis['net_sales'],
            'pod_total'         => $podStats['total'],
            'pod_converted'     => $podStats['converted'],
            'pod_rate'          => $podStats['rate'],
            'revenue_at_risk'   => $podKpis['net_sales'],
            'monthly'           => $this->monthlyTrend($filters, $empId),
            'categories'        => $this->categoryBreakdown($qualFilters),
        ];
    }

    /**
     * Build 12 monthly net-sales values for the current calendar year.
     * These power Chart 4 (Monthly Revenue Trend).
     */
    private function monthlyTrend(array $filters, int $empId): array
    {
        $year   = Carbon::now()->year;
        $values = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfDay()->toDateString();
            $end   = Carbon::create($year, $m, 1)->endOfMonth()->toDateString();
            $values[] = $this->engine->netSalesForPeriod($start, $end, [
                'employee_id'    => $empId,
                'payment_status' => 'paid_and_account',
                'store'          => $filters['store'] ?? null,
                'sale_type'      => $filters['sale_type'] ?? 'all',
                'category'       => $filters['category'] ?? null,
                'product'        => $filters['product'] ?? null,
            ]);
        }
        return $values;
    }

    /**
     * Top 10 categories by revenue for this employee (Chart 5).
     * Uses gross sub_total (consistent with Product Analytics approach).
     */
    private function categoryBreakdown(array $empFilters): array
    {
        $rows = $this->reporting->baseQuery($empFilters)
            ->selectRaw("
                COALESCE(pc.title, 'Uncategorized') AS category_name,
                SUM(order_products.sub_total)        AS revenue,
                SUM(order_products.quantity)         AS qty
            ")
            ->groupByRaw("COALESCE(pc.title, 'Uncategorized')")
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return $rows->map(fn($r) => [
            'category' => $r->category_name,
            'revenue'  => (float) ($r->revenue ?? 0),
            'qty'      => (int) ($r->qty ?? 0),
        ])->toArray();
    }

    /**
     * Count of all COD orders vs converted COD orders for this employee in the date window.
     */
    private function podStats(array $filters, int $empId): array
    {
        [$start, $end] = $this->reporting->resolveDateRange($filters);

        $base = DB::table('orders')
            ->join('order_payments', 'order_payments.order_id', '=', 'orders.id')
            ->whereNull('orders.deleted_at')
            ->where('orders.created_by_id', $empId)
            ->where('orders.created_by_type', User::class)
            ->where('order_payments.payment_method', 'COD')
            ->whereRaw('order_payments.id = (SELECT MAX(op2.id) FROM order_payments op2 WHERE op2.order_id = orders.id)');

        if ($start && $end) {
            $base->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()]);
        }

        $total     = (clone $base)->count();
        $converted = (clone $base)->where('order_payments.status', 'Paid')->count();

        return [
            'total'     => $total,
            'converted' => $converted,
            'rate'      => $total > 0 ? round(($converted / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * For Chart 6 (all_individually): qualified revenue per store per employee.
     * Returns array keyed by store_id with per-employee revenue values.
     */
    private function buildStoreBreakdown(array $filters, Collection $employees, Collection $stores): array
    {
        $data = [];
        foreach ($stores as $store) {
            $storeRow = ['store_id' => $store->id, 'store_name' => $store->store_name, 'employees' => []];
            foreach ($employees as $emp) {
                $sf = array_merge($filters, [
                    'employee_id'    => $emp->id,
                    'payment_status' => 'paid_and_account',
                    'store'          => $store->id,
                ]);
                $kpis = $this->engine->kpis($sf);
                $storeRow['employees'][] = [
                    'id'      => $emp->id,
                    'name'    => $emp->name,
                    'revenue' => $kpis['net_sales'],
                ];
            }
            $data[] = $storeRow;
        }
        return $data;
    }

    // ─── Private: Utilities ───────────────────────────────────────────────────

    /**
     * Resolve the employee list to show.
     * If $employeeIds is provided and non-empty, filter to just those IDs (deduped, max 3).
     * If empty, show all employees who have orders.
     */
    private function resolveEmployees(Collection $allEmployees, array $employeeIds): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $employeeIds))));

        if (empty($ids)) {
            return $allEmployees;
        }

        return $allEmployees->whereIn('id', array_slice($ids, 0, 3))->values();
    }
}
